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
 * Mailing logs output table
 *
 * @package    mod_custommailing
 * @author     jeanfrancois@cblue.be
 * @copyright  2021 CBlue SPRL
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_custommailing\MailingLog;

require_once __DIR__ . '/../../config.php';

global $CFG, $DB, $PAGE, $OUTPUT;

require_once $CFG->dirroot . '/mod/custommailing/lib.php';

$id = required_param('id', PARAM_INT);

[$course, $cm] = get_course_and_cm_from_cmid($id, 'custommailing');
$custommailing = $DB->get_record("custommailing", ['id' => $cm->instance]);
$context = context_module::instance($cm->id);

require_login($course, false, $cm);
require_capability('mod/custommailing:manage', $context);

$PAGE->set_url('/mod/custommailing/logs.php', ['id' => $id]);
$PAGE->set_pagelayout('incourse');

// Print the header.
$PAGE->set_title(format_string(get_string('modulename', 'custommailing')));
$PAGE->set_heading(format_string($course->fullname) . ' : ' . get_string('logtable', 'custommailing'));
echo $OUTPUT->header();

// Get all the appropriate data.
if (!$logs = MailingLog::getAllForTable($custommailing->id)) {
    notice('There are no instances of custommailing', "../../course/view.php?id=$course->id");
    die;
}

// Print the list of instances.
$table = new html_table();
$table->attributes['class'] = 'generaltable mod_index';
$table->head = [
    get_string('mailingname', 'custommailing'),
    get_string('user'),
    get_string('date'),
    get_string('status'),
];
$table->data = [];

$string_statuses = [
    MAILING_LOG_FAILED => get_string('log_mailing_failed', 'custommailing'),
    MAILING_LOG_SENT => get_string('log_mailing_sent', 'custommailing'),
    MAILING_LOG_PROCESSING => get_string('log_mailing_processing', 'custommailing'),
    MAILING_LOG_IDLE => get_string('log_mailing_idle', 'custommailing'),
];
$string_unknown_status = get_string('log_mailing_unknown', 'custommailing');

foreach ($logs as $log) {
    $srow = new html_table_row();

    $srow->cells = [
        $log->mailingname,
        $log->user,
        userdate($log->timecreated),
    ];
    if (isset($string_statuses[$log->emailstatus])) {
        $srow->cells[] = $string_statuses[$log->emailstatus];
    }
    else {
        $srow->cells[] = $string_unknown_status;
    }
    $table->data[] = $srow;
}

echo html_writer::table($table);
echo html_writer::link(new moodle_url('/mod/custommailing/view.php', ['id' => $cm->id]), get_string('back'), ['class' => 'btn btn-primary']);

echo $OUTPUT->footer();
