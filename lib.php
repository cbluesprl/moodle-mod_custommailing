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
 * @author     jeanfrancois@cblue.be,olivier@cblue.be
 * @copyright  2021 CBlue SPRL
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\notification;
use mod_recalluser\Mailing;

define('MAILING_MODE_NONE', 0);
define('MAILING_MODE_FIRSTLAUNCH', 1);
define('MAILING_MODE_REGISTRATION', 2);
define('MAILING_MODE_COMPLETE', 3);
define('MAILING_MODE_DAYSFROMINSCRIPTIONDATE', 4);
define('MAILING_MODE_DAYSFROMLASTCONNECTION', 5);
define('MAILING_MODE_DAYSFROMFIRSTLAUNCH', 6);
define('MAILING_MODE_DAYSFROMLASTLAUNCH', 7);

define('MAILING_STATUS_DISABLED', 0);
define('MAILING_STATUS_ENABLED', 1);

define('MAILING_LOG_IDLE', 0);
define('MAILING_LOG_PROCESSING', 1);
define('MAILING_LOG_SENT', 2);
define('MAILING_LOG_FAILED', 3);

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

    // Delete any dependent mailing here.
    Mailing::deleteAll($recalluser->id);

    if (!$DB->delete_records('recalluser', ['id' => $recalluser->id])) {
        $result = false;
    }

    return $result;
}

/**
 * @param $feature
 * @return bool|null
 */
function recalluser_supports($feature) {
    switch($feature) {
        case FEATURE_GRADE_HAS_GRADE:
            return false;
        case FEATURE_GRADE_OUTCOMES:
            return false;
        case FEATURE_ADVANCED_GRADING:
            return false;
        case FEATURE_CONTROLS_GRADE_VISIBILITY:
            return false;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return false;
        case FEATURE_COMPLETION_HAS_RULES:
            return false;
        case FEATURE_NO_VIEW_LINK:
            return false;
        case FEATURE_IDNUMBER:
            return true;
        case FEATURE_GROUPS:
            return false;
        case FEATURE_GROUPINGS:
            return false;
        case FEATURE_MOD_INTRO:
            return false;
        case FEATURE_MODEDIT_DEFAULT_COMPLETION:
            return false;
        case FEATURE_COMMENT:
            return false;
        case FEATURE_RATE:
            return false;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return false;
        case FEATURE_USES_QUESTIONS:
            return false;
        default:
            return false;
    }
}

/**
 * @return array
 * @throws moodle_exception
 */
function recalluser_get_activities () {
    global $COURSE, $PAGE;
    $course_module_context = $PAGE->context;

    $activities = [];
    foreach ($modinfo = get_fast_modinfo($COURSE)->get_cms() as $cm) {
        if ($cm->id != $course_module_context->instanceid) {
            $activities[(int) $cm->id] = format_string($cm->name);
        }
    }

    return $activities;
}
