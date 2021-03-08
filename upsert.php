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
 * Prints an instance of mod_recall_user.
 *
 * @package    mod_recalluser
 * @author     olivier@cblue.be
 * @copyright  2021 CBlue SPRL
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\output\notification;
use mod_recalluser\Mailing;

require_once __DIR__ . '/../../config.php';

global $CFG, $DB, $PAGE, $OUTPUT;

require_once $CFG->dirroot . '/mod/recalluser/lib.php';
require_once $CFG->dirroot . '/mod/recalluser/mailing_form.php';
require_once $CFG->dirroot . '/lib/completionlib.php';

$id = required_param('id', PARAM_INT);
$mailing_id = optional_param('mailingid', 0, PARAM_INT);

[$course, $cm] = get_course_and_cm_from_cmid($id, 'recalluser');
$recalluser = $DB->get_record("recalluser", ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);

require_login($course, false, $cm);

$PAGE->set_title(format_string($course->shortname . ': ' . $recalluser->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

if (!empty($mailing_id)) {
    $action = 'update';
    $mailing = $DB->get_record("recalluser_mailing", ['id' => $mailing_id], '*', MUST_EXIST);
    $url = new moodle_url('/mod/recalluser/upsert.php', ['id' => $cm->id, 'mailingid' => $mailing->id]);
    $form = new mailing_form(null, ['mailingid' => $mailing->id]);
} else {
    $action = 'create';
    $url = new moodle_url('/mod/recalluser/upsert.php', ['id' => $cm->id]);
    $form = new mailing_form();
}
$PAGE->set_url($url);

if ($form->is_cancelled()) {
    redirect(new moodle_url('/mod/recalluser/view.php', ['id' => $id]));
} elseif ($data = $form->get_data()) {
    if ($action == 'create') {
        $mailing = new stdClass();
    }

    $mailing->recalluserid = (int) $recalluser->id;
    $mailing->mailingname = $data->mailingname;
    $mailing->mailinglang = $data->mailinglang;
    $mailing->mailingsubject = $data->mailingsubject;
    $mailing->mailingcontent = $data->mailingcontent['text'];
    $mailing->mailingcontentformat = $data->mailingcontent['format'];
    if ($data->mailingmode == 'option') {
        $mailing->mailingmode = $data->mailingmodeoption;
        $mailing->mailingdelay = (int) $data->mailingdelay;
    } else {
        $mailing->mailingmode = (int) $data->mailingmode;
        $mailing->mailingdelay = null;
    }
    $mailing->mailingstatus = (bool) $data->mailingstatus;
    $mailing->targetmoduleid = (int) $data->targetmoduleid;
    $mailing->targetmodulestatus = (bool) $data->targetmodulestatus;
    $mailing->starttime = $data->starttimehour * 3600 + $data->starttimeminute * 60;
    if (!empty($data->customcert)) {
        $mailing->customcertmoduleid = (int) $data->customcert;
    } else {
        $mailing->customcertmoduleid = null;
    }
    if ($action == 'create') {
        Mailing::create($mailing);
        redirect(new moodle_url('/mod/recalluser/view.php', ['id' => $cm->id]), get_string('mailingadded', 'mod_recalluser'), null, notification::NOTIFY_SUCCESS);
    } else {
        Mailing::update($mailing);
        redirect(new moodle_url('/mod/recalluser/view.php', ['id' => $cm->id]), get_string('mailingupdated', 'mod_recalluser'), null, notification::NOTIFY_SUCCESS);
    }
} else {
    echo $OUTPUT->header();
    echo $OUTPUT->heading(format_string($recalluser->name));

    if ($action == 'update') {
        $data = clone $mailing;
        $data->id = $id;
        $data->mailingid = $mailing->id;
        $mailingcontenteditor = [
            'text' => $data->mailingcontent,
            'format' => $data->mailingcontentformat
        ];
        $data->mailingcontent = $mailingcontenteditor;
        $data->starttimehour = floor($data->starttime / 3600);
        $data->starttimeminute = floor(($data->starttime / 60) % 60);
        if (in_array($data->mailingmode, [MAILING_MODE_DAYSFROMINSCRIPTIONDATE, MAILING_MODE_DAYSFROMLASTCONNECTION, MAILING_MODE_DAYSFROMFIRSTLAUNCH, MAILING_MODE_DAYSFROMLASTLAUNCH])) {
            $data->mailingmode = 'option';
            $data->mailingmodeoption = $mailing->mailingmode;
        }
        $form->set_data($data);
    }
    $form->display();

    echo $OUTPUT->footer();
}
