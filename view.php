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

require_once __DIR__ . '/../../config.php';

global $CFG, $DB, $PAGE, $OUTPUT;

require_once $CFG->dirroot . '/mod/recalluser/lib.php';
require_once $CFG->dirroot . '/mod/recalluser/mailing_form.php';

$id = required_param('id', PARAM_INT);

[$course, $cm] = get_course_and_cm_from_cmid($id, 'recalluser');
$recalluser = $DB->get_record("recalluser", ['id' => $cm->instance]);
$context = context_module::instance($cm->id);

require_login($course, false, $cm);

$PAGE->set_url('/mod/recalluser/view.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($course->shortname . ': ' . $recalluser->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

$form = new mailing_form();

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($recalluser->name));

echo
    '<div id="addMailingAccordion">
  <div class="card">
    <div class="card-header" id="addMailingHeading">
      <h5 class="mb-0">
        <button class="btn btn-primary" data-toggle="collapse" data-target="#addMailing" aria-expanded="false" aria-controls="addMailing">
          ' . get_string('createnewmailing', 'mod_recalluser') . '
        </button>
      </h5>
    </div>

    <div id="addMailing" class="collapse" aria-labelledby="addMailingHeading" data-parent="#addMailingAccordion">
      <div class="card-body">
        ' . $form->render() . '
      </div>
    </div>
  </div>';

echo $OUTPUT->footer();
