<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Prints an instance of mod_custommailing.
 *
 * @package    mod_custommailing
 * @author     olivier@cblue.be, jeanfrancois@cblue.be
 * @copyright  2021 CBlue SPRL
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\output\notification;
use mod_custommailing\Mailing;

require_once __DIR__ . '/../../config.php';

global $CFG, $DB, $PAGE, $OUTPUT;

require_once $CFG->dirroot . '/mod/custommailing/lib.php';
require_once $CFG->dirroot . '/mod/custommailing/mailing_form.php';
require_once $CFG->dirroot . '/lib/completionlib.php';

$id = required_param('id', PARAM_INT);
$mailing_id = optional_param('mailingid', 0, PARAM_INT);

[$course, $cm] = get_course_and_cm_from_cmid($id, 'custommailing');
$custommailing = $DB->get_record("custommailing", ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);

require_login($course, false, $cm);
require_capability('mod/custommailing:manage', $context);

$PAGE->set_title(format_string($course->shortname . ': ' . $custommailing->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

if (!empty($mailing_id)) {
    $action = 'update';
    $mailing = $DB->get_record("custommailing_mailing", ['id' => $mailing_id], '*', MUST_EXIST);
    $url = new moodle_url('/mod/custommailing/upsert.php', ['id' => $cm->id, 'mailingid' => $mailing->id]);
    $form = new mailing_form(null, ['mailingid' => $mailing->id]);
} else {
    $action = 'create';
    $url = new moodle_url('/mod/custommailing/upsert.php', ['id' => $cm->id]);
    $form = new mailing_form();
}
$PAGE->set_url($url);

if ($form->is_cancelled()) {
    redirect(new moodle_url('/mod/custommailing/view.php', ['id' => $id]));
} elseif ($data = $form->get_data()) {
    if ($action == 'create') {
        $mailing = new stdClass();
    }

    $mailing->custommailingid = (int) $custommailing->id;
    $mailing->mailingname = $data->mailingname;
    $mailing->mailinglang = 'en'; //disabled in v1
    $mailing->mailingsubject = $data->mailingsubject;
    $mailing->mailingcontent = $data->mailingcontent['text'];
    $mailing->mailingcontentformat = $data->mailingcontent['format'];
    if (empty($data->mailingmodecompletion)) {
        $data->mailingmodecompletion = 0;
    }
    $mailing->targetmodulestatus = $data->mailingmodecompletion;
    if ($data->mailingmode == 'option' && !empty($data->mailingmodeoption)) {
        $mailing->mailingmode = $data->mailingmodeoption;
        $mailing->mailingdelay = (int) $data->mailingdelay;
    } elseif ($data->mailingmodemodule == 'option' && !empty($data->mailingmodemoduleoption)) {
        $mailing->mailingmode = $data->mailingmodemoduleoption;
        $mailing->mailingdelay = (int) $data->mailingdelaymodule;
    } else {
        $mailing->mailingmode = (int) empty($data->mailingmode) ? $data->mailingmode : 0;
        $mailing->mailingdelay = null;
    }
    $mailing->mailingstatus = (bool) $data->mailingstatus;
    $mailing->retroactive = (bool) $data->retroactive;
    if (empty($data->targetmoduleid)) {
        $data->targetmoduleid = 0;
    }
    $mailing->targetmoduleid = (int) $data->targetmoduleid;
    //Todo v2 : starttime
    $mailing->starttime = 0; //$data->starttimehour * 3600 + $data->starttimeminute * 60;
    if (!empty($data->customcert)) {
        $mailing->mailingmode = MAILING_MODE_SEND_CERTIFICATE;
        $mailing->customcertmoduleid = (int) $data->customcert;
    } else {
        $mailing->customcertmoduleid = null;
    }
    if ($action == 'create') {
        Mailing::create($mailing);
        redirect(new moodle_url('/mod/custommailing/view.php', ['id' => $cm->id]), get_string('mailingadded', 'mod_custommailing'), null, notification::NOTIFY_SUCCESS);
    } else {
        Mailing::update($mailing);
        redirect(new moodle_url('/mod/custommailing/view.php', ['id' => $cm->id]), get_string('mailingupdated', 'mod_custommailing'), null, notification::NOTIFY_SUCCESS);
    }
} else {
    echo $OUTPUT->header();
    echo $OUTPUT->heading(format_string($custommailing->name));

    if ($action == 'update') {
        $data = clone $mailing;
        $data->id = $id;
        $data->mailingid = $mailing->id;
        $mailingcontenteditor = [
            'text' => $data->mailingcontent,
            'format' => $data->mailingcontentformat
        ];
        $data->mailingcontent = $mailingcontenteditor;
        //Todo v2 : starttime
        $data->starttimehour = 0; //floor($data->starttime / 3600);
        $data->starttimeminute = 0; //floor(($data->starttime / 60) % 60);
        if (empty($data->customcertmoduleid) && in_array($data->mailingmode, [MAILING_MODE_DAYSFROMINSCRIPTIONDATE, MAILING_MODE_DAYSFROMLASTCONNECTION, MAILING_MODE_DAYSFROMFIRSTLAUNCH, MAILING_MODE_DAYSFROMLASTLAUNCH])) {
            $data->mailingmode = 'option';
            $data->mailingmodeoption = $mailing->mailingmode;
        }
        if (!empty($data->targetmoduleid)) {
            $data->source = MAILING_SOURCE_MODULE;
        } elseif (!empty($data->customcertmoduleid)) {
            $data->source = MAILING_SOURCE_CERT;
        } else {
            $data->source = MAILING_SOURCE_COURSE;
        }
        $data->retroactive = $mailing->retroactive;
        $form->set_data($data);
    }
    $form->display();

    echo $OUTPUT->footer();
}
