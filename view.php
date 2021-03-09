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

use mod_recalluser\Mailing;

require_once __DIR__ . '/../../config.php';

global $CFG, $DB, $PAGE, $OUTPUT;

require_once $CFG->dirroot . '/mod/recalluser/lib.php';
require_once $CFG->dirroot . '/mod/recalluser/mailing_form.php';

$id = required_param('id', PARAM_INT);

[$course, $cm] = get_course_and_cm_from_cmid($id, 'recalluser');
$recalluser = $DB->get_record("recalluser", ['id' => $cm->instance]);
$context = context_module::instance($cm->id);

require_login($course, false, $cm);
require_capability('mod/recalluser:manage', $context);

$url = new moodle_url('/mod/recalluser/view.php', ['id' => $cm->id]);

$PAGE->set_url($url);
$PAGE->set_title(format_string($course->shortname . ': ' . $recalluser->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

$mailings = Mailing::getAll($recalluser->id);
$activities = recalluser_get_activities();

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($recalluser->name));

echo '<hr>';
echo '<a class="btn btn-primary" href="' . (new moodle_url('/mod/recalluser/upsert.php', ['id' => $id]))->out(false) . '">' . get_string('createnewmailing', 'mod_recalluser') . '</a>';
echo '<hr>';
echo '<div id="mailingsList">';
foreach ($mailings as $mailing) {
    $mailing->mailingmodestr = '';
    if ($mailing->mailingmode == MAILING_MODE_FIRSTLAUNCH) {
        $mailing->mailingmodestr = get_string('atfirstlaunch', 'mod_recalluser');
    } elseif ($mailing->mailingmode == MAILING_MODE_REGISTRATION) {
        $mailing->mailingmodestr = get_string('atcourseenrol', 'mod_recalluser');
    } elseif ($mailing->mailingmode == MAILING_MODE_COMPLETE) {
        $mailing->mailingmodestr = get_string('atactivitycompleted', 'mod_recalluser');
    } elseif ($mailing->mailingmode == MAILING_MODE_DAYSFROMINSCRIPTIONDATE) {
        $mailing->mailingmodestr = $mailing->mailingdelay . ' ' . get_string('daysafter', 'mod_recalluser') . ' ' . get_string('courseenroldate', 'mod_recalluser');
    } elseif ($mailing->mailingmode == MAILING_MODE_DAYSFROMLASTCONNECTION) {
        $mailing->mailingmodestr = $mailing->mailingdelay . ' ' . get_string('daysafter', 'mod_recalluser') . ' ' . get_string('courselastaccess', 'mod_recalluser');
    } elseif ($mailing->mailingmode == MAILING_MODE_DAYSFROMFIRSTLAUNCH) {
        $mailing->mailingmodestr = $mailing->mailingdelay . ' ' . get_string('daysafter', 'mod_recalluser') . ' ' . get_string('firstlaunch', 'mod_recalluser');
    } elseif ($mailing->mailingmode == MAILING_MODE_DAYSFROMLASTLAUNCH) {
        $mailing->mailingmodestr = $mailing->mailingdelay . ' ' . get_string('daysafter', 'mod_recalluser') . ' ' . get_string('lastlaunch', 'mod_recalluser');
    }
    echo
        '<div class="card">
           <div class="card-header" id="mailing_' . $mailing->id . '">
             <h5 class="mb-0">
               <div data-toggle="collapse" data-target="#mailing_' . $mailing->id . '_content" aria-expanded="false" aria-controls="mailing_' . $mailing->id . '_content">
                 <strong>' . $mailing->mailingname . '</strong> (#' . $mailing->id . ')
                 <div class="pull-right">
                   <span class="disabled btn btn-sm ' . ($mailing->mailingstatus == MAILING_STATUS_ENABLED ? 'btn-success' : 'btn-warning') . '">' .
                    ($mailing->mailingstatus == MAILING_STATUS_ENABLED ? get_string('enabled', 'mod_recalluser') : get_string('disabled', 'mod_recalluser')) .
                  '</span>
                   <a class="btn btn-sm btn-info " href="' . (new moodle_url('/mod/recalluser/upsert.php', ['id' => $id, 'mailingid' => $mailing->id]))->out(false) . '">' . get_string('edit') . '</a>
                   <a class="btn btn-sm btn-danger " href="' . (new moodle_url('/mod/recalluser/delete.php', ['id' => $id, 'mailingid' => $mailing->id]))->out(false) . '">' . get_string('delete') . '</a>
                 </div>
                </div>
             </h5>
           </div>
        
           <div id="mailing_' . $mailing->id . '_content" class="collapse" aria-labelledby="mailing_' . $mailing->id . '" data-parent="#mailingsList">
             <div class="card-body">
                <p><strong>ID</strong> : ' . $mailing->id . '</p>
                <p><strong>' . get_string('recallusername', 'recalluser') . '</strong> : ' . $mailing->mailingname . '</p>
                <p><strong>' . get_string('mailinglang', 'recalluser') . '</strong> : ' . $mailing->mailinglang . '</p>';
    if (empty($mailing->targetmoduleid)) {
        echo    '<p><strong>' . get_string('targetmoduleid', 'recalluser') . '</strong> : - </p>';
    } else {
        echo    '<p><strong>' . get_string('targetmoduleid', 'recalluser') . '</strong> : ' . (isset($activities[$mailing->targetmoduleid]) ? $activities[$mailing->targetmoduleid] : 'not found') . '</p>';
    }
    echo        '<p><strong>' . get_string('sendmailing', 'recalluser') . '</strong> : ' . $mailing->mailingmodestr . '</p>
                <p><strong>' . get_string('mailingsubject', 'recalluser') . '</strong> : ' . $mailing->mailingsubject . '</p>
                <p><strong>' . get_string('mailingcontent', 'recalluser') . '</strong> : ' . $mailing->mailingcontent . '</p>
                <p><strong>' . get_string('starttime', 'recalluser') . '</strong> : ' .  str_pad(floor($mailing->starttime / 3600), 2, '0', STR_PAD_LEFT) . ' : ' . str_pad(floor(($mailing->starttime / 60) % 60), 2, '0', STR_PAD_LEFT) . '</p>';
    echo     '</div>
           </div>
        </div>';
}
echo '</div>'; // mailingsList
echo '<hr>';

echo $OUTPUT->footer();
