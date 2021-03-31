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
 * Display information about all the mod_custommailing modules in the requested course.
 *
 * @package    mod_custommailing
 * @author     jeanfrancois@cblue.be,olivier@cblue.be
 * @copyright  2021 CBlue SPRL
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_custommailing\event\course_module_instance_list_viewed;

require_once __DIR__ . '/../../config.php';

global $CFG, $DB, $PAGE, $OUTPUT;

require_once $CFG->dirroot . '/mod/custommailing/lib.php';

$id = required_param('id', PARAM_INT);

if (!empty($id)) {
    if (!$course = $DB->get_record('course', ['id' => $id])) {
        print_error('invalidcourseid');
    }
} else {
    print_error('missingparameter');
}

require_course_login($course);

$PAGE->set_url('/mod/custommailing/index.php', ['id' => $id]);
$PAGE->set_pagelayout('incourse');

// Add the page view to the Moodle log.
$event = course_module_instance_list_viewed::create(
    [
        'context' => context_course::instance($course->id)
    ]
);
$event->add_record_snapshot('course', $course);
$event->trigger();

// Print the header.
$PAGE->set_title(format_string(get_string('modulename', 'custommailing')));
$PAGE->set_heading(format_string($course->fullname));
echo $OUTPUT->header();

// Get all the appropriate data.
if (!$mailings = get_all_instances_in_course('custommailing', $course)) {
    notice('There are no instances of custommailing', "../../course/view.php?id=$course->id");
    die;
}

// Print the list of instances.
$table = new html_table();
$table->attributes['class'] = 'generaltable mod_index';

$usesections = course_format_uses_sections($course->format);
$modinfo = get_fast_modinfo($course);
$currentsection = '';
foreach ($mailings as $mailing) {
    $cm = $modinfo->cms[$mailing->coursemodule];
    if ($usesections) {
        $printsection = '';
        if ($mailing->section !== $currentsection) {
            if ($mailing->section) {
                $printsection = get_section_name($course, $mailing->section);
            }
            if ($currentsection !== '') {
                $table->data[] = 'hr';
            }
            $currentsection = $mailing->section;
        }
    } else {
        $printsection = userdate($mailing->timemodified);
    }

    $class = $mailing->visible ? '' : 'class="dimmed"'; // Hidden modules are dimmed.

    $table->data[] = [
        $printsection, html_writer::link('view.php?id=' . $cm->id, format_string($mailing->name))
    ];
}

echo html_writer::table($table);

echo $OUTPUT->footer();

