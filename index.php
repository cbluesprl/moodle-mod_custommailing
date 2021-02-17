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
 * This page lists all the instances of recalluser in a particular course
 *
 * @package    mod_recalluser
 * @author     jeanfrancois@cblue.be
 * @copyright  2021 CBlue SPRL
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once '../../config.php';
require_once 'lib.php';

global $DB, $PAGE, $OUTPUT;

$id = required_param('id', PARAM_INT);

if (!empty($id)) {
    if (!$course = $DB->get_record('course', array('id' => $id))) {
        print_error('invalidcourseid');
    }
} else {
    print_error('missingparameter');
}

require_course_login($course);

$PAGE->set_url('/mod/recalluser/index.php', array('id' => $id));
$PAGE->set_pagelayout('incourse');

// Add the page view to the Moodle log.
$event = \mod_recalluser\event\course_module_instance_list_viewed::create(array(
    'context' => context_course::instance($course->id)
));
$event->add_record_snapshot('course', $course);
$event->trigger();

// Print the header.

$PAGE->set_title(format_string(get_string('modulename', 'reengagement')));
$PAGE->set_heading(format_string($course->fullname));

echo $OUTPUT->header();
// Get all the appropriate data.

if (!$recalls = get_all_instances_in_course('recalluser', $course)) {
    notice('There are no instances of recalluser', "../../course/view.php?id=$course->id");
    die;
}

// Print the list of instances.
$table = new html_table();
$table->attributes['class'] = 'generaltable mod_index';

$usesections = course_format_uses_sections($course->format);
$modinfo = get_fast_modinfo($course);
$currentsection = '';
foreach ($recalls as $recall) {
    $cm = $modinfo->cms[$recall->coursemodule];
    if ($usesections) {
        $printsection = '';
        if ($recall->section !== $currentsection) {
            if ($recall->section) {
                $printsection = get_section_name($course, $recall->section);
            }
            if ($currentsection !== '') {
                $table->data[] = 'hr';
            }
            $currentsection = $recall->section;
        }
    } else {
        $printsection = userdate($recall->timemodified);
    }

    $class = $recall->visible ? '' : 'class="dimmed"'; // Hidden modules are dimmed.

    $table->data[] = array (
        $printsection, html_writer::link('view.php?id='.$cm->id, format_string($recall->name))
    );
}

echo html_writer::table($table);

echo $OUTPUT->footer();

