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
 * This file manages the public functions of this module
 *
 * @package    mod_recalluser
 * @author     jeanfrancois@cblue.be
 * @copyright  2021 CBlue SPRL
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\notification;

/**
 * @param $recalluser
 * @return bool|int
 * @throws coding_exception
 * @throws dml_exception
 */
function recalluser_add_instance($recalluser) {
    global $CFG, $DB;

    $recalluser->timecreated = time();
    $recalluser->timemodified = time();

    // Check if course has completion enabled, and enable it if not (and user has permission to do so)
    $course = $DB->get_record('course', ['id' => $recalluser->course]);
    if (empty($course->enablecompletion)) {
        if (empty($CFG->enablecompletion)) {
            // Completion tracking is disabled in Moodle
            notification::error(get_string('coursecompletionnotenabled', 'recalluser'));
        } else {
            // Completion tracking is enabled in Moodle
            if (has_capability('moodle/course:update', context_course::instance($course->id))) {
                $data = ['id' => $course->id, 'enablecompletion' => '1'];
                $DB->update_record('course', $data);
                rebuild_course_cache($course->id);
                notification::warning(get_string('coursecompletionenabled', 'recalluser'));
            } else {
                notification::error(get_string('coursecompletionnotenabled', 'recalluser'));
            }
        }

    }

    return $DB->insert_record('recalluser', $recalluser);
}

/**
 * @param $recalluser
 * @return bool
 * @throws dml_exception
 */
function recalluser_update_instance($recalluser) {
    global $DB;

    $recalluser->timemodified = time();
    $recalluser->id = $recalluser->instance;

    return $DB->update_record('recalluser', $recalluser);
}

/**
 * @param $id
 * @return bool
 * @throws dml_exception
 */
function recalluser_delete_instance($id) {
    global $DB;

    if (!$recalluser = $DB->get_record('recalluser', ['id' => $id])) {
        return false;
    }

    $result = true;

    // Delete any dependent records here.
    if ($mailings = $DB->get_records('recalluser_mailing', ['cmid' => $recalluser->id])) {
        foreach ($mailings as $mailing) {
            if (!$DB->delete_records('recalluser_logs', ['mailingid' => $mailing->id])) {
                $result = false;
            }
        }
        $DB->delete_records('recalluser_mailing', ['cmid' => $recalluser->id]);
    }

    if (!$DB->delete_records('recalluser', ['id' => $recalluser->id])) {
        $result = false;
    }

    return $result;
}
