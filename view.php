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

[$course, $cm] = get_course_and_cm_from_cmid($id, 'recalluser');
$recalluser = $DB->get_record("recalluser", ['id' => $cm->instance]);
$context = context_module::instance($cm->id);

require_login($course, false, $cm);

$url = new moodle_url('/mod/recalluser/view.php', ['id' => $cm->id]);

$PAGE->set_url($url);
$PAGE->set_title(format_string($course->shortname . ': ' . $recalluser->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

$mailings = Mailing::getAll($recalluser->id);

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($recalluser->name));

echo '<hr>';
echo '<a class="btn btn-primary" href="'.(new moodle_url('/mod/recalluser/upsert.php', ['id' => $id]))->out(false).'">'.get_string('createnewmailing', 'mod_recalluser').'</a>';
echo '<hr>';
echo '<div id="mailingsList">';
foreach ($mailings as $mailing) {
    // TODO continue l'affichage d'informations
    echo
        '<div class="card">
           <div class="card-header" id="mailing_'.$mailing->id.'">
             <h5 class="mb-0">
               <div data-toggle="collapse" data-target="#mailing_'.$mailing->id.'_content" aria-expanded="false" aria-controls="mailing_'.$mailing->id.'_content">
                 <strong>'.$mailing->mailingname.'</strong> (#'.$mailing->id.')
                 <div class="pull-right">
                    <a class="btn btn-sm btn-info " href="'.(new moodle_url('/mod/recalluser/upsert.php', ['id' => $id, 'mailingid' => $mailing->id]))->out(false).'">'.get_string('edit').'</a>
                   <span class="btn btn-sm '.($mailing->mailingstatus == MAILING_STATUS_ENABLED ? 'btn-success' : 'btn-danger').'">'.
                    ($mailing->mailingstatus == MAILING_STATUS_ENABLED ? get_string('enabled', 'mod_recalluser') : get_string('disabled', 'mod_recalluser')).
                  '</span>
                   </div>
               </div>
             </h5>
           </div>
        
           <div id="mailing_'.$mailing->id.'_content" class="collapse" aria-labelledby="mailing_'.$mailing->id.' data-parent="#mailingsList">
             <div class="card-body">
                <p><strong>ID</strong> : '.$mailing->id.'</p>
                <p><strong>'.get_string('recallusername', 'recalluser').'</strong> : '.$mailing->mailingname.'</p>
              </div>
           </div>
        </div>';
}
echo '</div>'; // mailingsList

echo $OUTPUT->footer();
