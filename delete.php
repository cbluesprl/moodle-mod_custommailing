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

$id = required_param('id', PARAM_INT);
$mailing_id = required_param('mailingid', PARAM_INT);
$delete = optional_param('delete', false, PARAM_BOOL);
$confirm = optional_param('confirm', '', PARAM_ALPHANUM);

[$course, $cm] = get_course_and_cm_from_cmid($id, 'recalluser');
$recalluser = $DB->get_record("recalluser", ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);
$mailing = $DB->get_record("recalluser_mailing", ['id' => $mailing_id], '*', MUST_EXIST);

require_login($course, false, $cm);
require_capability('mod/recalluser:manage', $context);

$url = new moodle_url('/mod/recalluser/delete.php', ['id' => $id, 'mailingid' => $mailing_id]);
$return_url = new moodle_url('/mod/recalluser/view.php', ['id' => $cm->id]);

$PAGE->set_url($url);
$PAGE->set_title(format_string($course->shortname . ': ' . $recalluser->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

if ($delete == true && $confirm == md5($mailing_id) && confirm_sesskey()) {
    Mailing::delete($mailing_id);
    redirect($return_url, get_string('mailingdeleted', 'mod_recalluser'), null, notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($recalluser->name));

echo $OUTPUT->confirm(
    get_string('confirmdelete', 'mod_recalluser', $mailing->mailingname),
    new single_button(
        new moodle_url($url, ['delete' => true, 'confirm' => md5($mailing_id), 'sesskey' => sesskey()]),
        get_string('delete'),
        'post'
    ),
    $return_url
);

echo $OUTPUT->footer();
