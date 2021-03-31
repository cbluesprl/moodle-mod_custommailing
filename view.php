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
 * @author     olivier@cblue.be
 * @copyright  2021 CBlue SPRL
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_custommailing\Mailing;

require_once __DIR__ . '/../../config.php';

global $CFG, $DB, $PAGE, $OUTPUT;

require_once $CFG->dirroot . '/mod/custommailing/lib.php';
require_once $CFG->dirroot . '/mod/custommailing/mailing_form.php';

$id = required_param('id', PARAM_INT);

[$course, $cm] = get_course_and_cm_from_cmid($id, 'custommailing');
$custommailing = $DB->get_record("custommailing", ['id' => $cm->instance]);
$context = context_module::instance($cm->id);

require_login($course, false, $cm);
require_capability('mod/custommailing:manage', $context);

$url = new moodle_url('/mod/custommailing/view.php', ['id' => $cm->id]);

$PAGE->set_url($url);
$PAGE->set_title(format_string($course->shortname . ': ' . $custommailing->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

$mailings = Mailing::getAll($custommailing->id);
$activities = custommailing_get_activities(true);

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($custommailing->name));

echo '<hr>';
echo '<a class="btn btn-primary" href="' . (new moodle_url('/mod/custommailing/upsert.php', ['id' => $id]))->out(false) . '">' . get_string('createnewmailing', 'mod_custommailing') . '</a>';
echo '<hr>';
echo '<div id="mailingsList">';
foreach ($mailings as $mailing) {
    $mailing->mailingmodestr = '';
    if ($mailing->mailingmode == MAILING_MODE_FIRSTLAUNCH) {
        $mailing->mailingmodestr = get_string('atfirstlaunch', 'mod_custommailing');
    } elseif ($mailing->mailingmode == MAILING_MODE_REGISTRATION) {
        $mailing->mailingmodestr = get_string('atcourseenrol', 'mod_custommailing');
    } elseif ($mailing->mailingmode == MAILING_MODE_COMPLETE) {
        $mailing->mailingmodestr = get_string('atactivitycompleted', 'mod_custommailing');
    } elseif ($mailing->mailingmode == MAILING_MODE_DAYSFROMINSCRIPTIONDATE) {
        $mailing->mailingmodestr = $mailing->mailingdelay . ' ' . get_string('daysafter', 'mod_custommailing') . ' ' . get_string('courseenroldate', 'mod_custommailing');
    } elseif ($mailing->mailingmode == MAILING_MODE_DAYSFROMLASTCONNECTION) {
        $mailing->mailingmodestr = $mailing->mailingdelay . ' ' . get_string('daysafter', 'mod_custommailing') . ' ' . get_string('courselastaccess', 'mod_custommailing');
    } elseif ($mailing->mailingmode == MAILING_MODE_DAYSFROMFIRSTLAUNCH) {
        $mailing->mailingmodestr = $mailing->mailingdelay . ' ' . get_string('daysafter', 'mod_custommailing') . ' ' . get_string('firstlaunch', 'mod_custommailing');
    } elseif ($mailing->mailingmode == MAILING_MODE_DAYSFROMLASTLAUNCH) {
        $mailing->mailingmodestr = $mailing->mailingdelay . ' ' . get_string('daysafter', 'mod_custommailing') . ' ' . get_string('lastlaunch', 'mod_custommailing');
    }
    echo
        '<div class="card">
           <div class="card-header" id="mailing_' . $mailing->id . '">
             <h5 class="mb-0">
               <div data-toggle="collapse" data-target="#mailing_' . $mailing->id . '_content" aria-expanded="false" aria-controls="mailing_' . $mailing->id . '_content">
                 <strong>' . $mailing->mailingname . '</strong> (#' . $mailing->id . ')
                 <div class="pull-right">
                   <span class="disabled btn btn-sm ' . ($mailing->mailingstatus == MAILING_STATUS_ENABLED ? 'btn-success' : 'btn-warning') . '">' .
                    ($mailing->mailingstatus == MAILING_STATUS_ENABLED ? get_string('enabled', 'mod_custommailing') : get_string('disabled', 'mod_custommailing')) .
                  '</span>
                   <a class="btn btn-sm btn-info " href="' . (new moodle_url('/mod/custommailing/upsert.php', ['id' => $id, 'mailingid' => $mailing->id]))->out(false) . '">' . get_string('edit') . '</a>
                   <a class="btn btn-sm btn-danger " href="' . (new moodle_url('/mod/custommailing/delete.php', ['id' => $id, 'mailingid' => $mailing->id]))->out(false) . '">' . get_string('delete') . '</a>
                 </div>
                </div>
             </h5>
           </div>
        
           <div id="mailing_' . $mailing->id . '_content" class="collapse" aria-labelledby="mailing_' . $mailing->id . '" data-parent="#mailingsList">
             <div class="card-body">
                <p><strong>' . get_string('custommailingname', 'custommailing') . '</strong> : ' . $mailing->mailingname . '</p>';
    if (!empty($mailing->customcertmoduleid)) {
        echo '<p><strong>' . get_string('targetmoduleid', 'custommailing') . '</strong> : ' . custommailing_getCustomcert($mailing->customcertmoduleid)->name . ' </p>';
        $mailing->mailingmodestr = get_string('customcert_help', 'custommailing');
    } elseif (empty($mailing->targetmoduleid)) {
        echo    '<p><strong>' . get_string('targetmoduleid', 'custommailing') . '</strong> : - </p>';
    } else {
        echo    '<p><strong>' . get_string('targetmoduleid', 'custommailing') . '</strong> : ' . (isset($activities[$mailing->targetmoduleid]) ? $activities[$mailing->targetmoduleid] : 'not found') . '</p>';
    }
    echo        '<p><strong>' . get_string('sendmailing', 'custommailing') . '</strong> : ' . $mailing->mailingmodestr . '</p>
                <p><strong>' . get_string('mailingsubject', 'custommailing') . '</strong> : ' . $mailing->mailingsubject . '</p>
                <p><strong>' . get_string('mailingcontent', 'custommailing') . '</strong> : ' . $mailing->mailingcontent . '</p>
                <p><strong>' . get_string('timecreated', 'custommailing') . '</strong> : ' . userdate($mailing->timecreated) . '</p>
                <p><strong>' . get_string('timemodified', 'custommailing') . '</strong> : ' . userdate($mailing->timemodified) . '</p>
                ';
    echo     '</div>
           </div>
        </div>';
}
echo '</div>'; // mailingsList
echo '<hr>';
echo '<a class="btn btn-primary" href="' . (new moodle_url('/mod/custommailing/logs.php', ['id' => $id]))->out(false) . '">' . get_string('logtable', 'mod_custommailing') . '</a>';

echo $OUTPUT->footer();
