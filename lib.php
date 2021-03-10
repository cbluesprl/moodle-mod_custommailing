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
use mod_recalluser\MailingLog;

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

/**
 * @throws dml_exception
 */
function recalluser_logs_generate() {

    global $DB;

    $mailings = Mailing::getAllToSend();
    foreach ($mailings as $mailing) {
        if ($mailing->mailingmode == MAILING_MODE_FIRSTLAUNCH) {
            //ToDo : specific Scorm & Quiz action instead of course_module 'viewed'
            $sql = "SELECT * 
                FROM {user} u
                JOIN {logstore_standard_log} lsl ON lsl.userid = u.id AND lsl.contextlevel = 70 AND lsl.contextinstanceid = $mailing->cmid AND lsl.action = 'viewed'
                ORDER BY lsl.id
                ";
        } elseif ($mailing->mailingmode == MAILING_MODE_REGISTRATION) {
            $sql = "SELECT * 
                FROM {user} u
                JOIN {course_modules} cm ON cm.id = $mailing->cmid
                JOIN {course} c ON c.id = cm.course
                JOIN {enrol} e ON e.courseid = c.id
                JOIN {user_enrolments} ue ON ue.userid = u.id AND ue.enrolid = e.id
                WHERE ue.timestart > UNIX_TIMESTAMP(DATE(NOW() - INTERVAL $mailing->mailingdelay DAY))
                ";
        } elseif ($mailing->mailingmode == MAILING_MODE_COMPLETE) {
            $sql = "SELECT * 
                FROM {user} u
                JOIN {course_modules_completion} cmc ON cmc.userid = u.id AND cmc.coursemoduleid = $mailing->cmid
                ";
        } elseif ($mailing->mailingmode == MAILING_MODE_DAYSFROMINSCRIPTIONDATE) {
            $sql = "SELECT * 
                FROM {user} u
                JOIN {course_modules} cm ON cm.id = $mailing->cmid
                JOIN {course} c ON c.id = cm.course
                JOIN {enrol} e ON e.courseid = c.id
                JOIN {user_enrolments} ue ON ue.userid = u.id AND ue.enrolid = e.id
                WHERE ue.timestart > UNIX_TIMESTAMP(DATE(NOW() - INTERVAL $mailing->mailingdelay DAY))
                ";
        } elseif ($mailing->mailingmode == MAILING_MODE_DAYSFROMLASTCONNECTION) {
            $sql = "SELECT * 
                FROM {user} u
                JOIN {course_modules} cm ON cm.id = $mailing->cmid
                JOIN {course} c ON c.id = cm.course
                JOIN {logstore_standard_log} lsl ON lsl.userid = u.id AND lsl.contextlevel = 50 AND lsl.action = 'viewed' AND lsl.courseid = c.id
                ";
        } elseif ($mailing->mailingmode == MAILING_MODE_DAYSFROMFIRSTLAUNCH) {
            //ToDo : specific Scorm & Quiz action instead of course_module 'viewed'
            $sql = "SELECT * 
                FROM {user} u
                JOIN {logstore_standard_log} lsl ON lsl.userid = u.id AND lsl.contextlevel = 70 AND lsl.contextinstanceid = $mailing->cmid AND lsl.action = 'viewed'
                WHERE lsl.timecreated > UNIX_TIMESTAMP(DATE(NOW() - INTERVAL $mailing->mailingdelay DAY))
                ORDER BY lsl.id
                ";
        } elseif ($mailing->mailingmode == MAILING_MODE_DAYSFROMLASTLAUNCH) {
            //ToDo : specific Scorm & Quiz action instead of course_module 'viewed'
            $sql = "SELECT * 
                FROM {user} u
                JOIN {logstore_standard_log} lsl ON lsl.userid = u.id AND lsl.contextlevel = 70 AND lsl.contextinstanceid = $mailing->cmid AND lsl.action = 'viewed'
                WHERE lsl.timecreated > UNIX_TIMESTAMP(DATE(NOW() - INTERVAL $mailing->mailingdelay DAY))
                ORDER BY lsl.id ASC
                ";
        }
        $users = $DB->get_records_sql($sql);
        foreach ($users as $user) {
            if (!$DB->get_record('recalluser_logs', ['mailingid' => $mailing->id, 'emailto' => $user->id])) {
                $record = new stdClass();
                $record->recallusermailingid = (int) $mailing->id;
                $record->emailtouserid = (int) $user->id;
                $record->emailstatus = MAILING_LOG_PROCESSING;
                $record->timecreated = time();
                MailingLog::create($record);
            }
        }
    }
}

/**
 * Process recalluser_logs MAILING_LOG_SENT records
 * Send email to each user
 *
 * @throws dml_exception
 */
function recalluser_crontask() {

    global $DB;

    recalluser_logs_generate();

    $ids_to_update = [];

    $sql = "SELECT u.*, rm.mailingsubject, rm.mailingcontent, rl.id as logid
            FROM {user} u
            JOIN {recalluser_logs} rl ON rl.emailto = u.id 
            JOIN {recalluser_mailing} rm ON rm.id = rl.mailingid
            WHERE rl.emailstatus < " . MAILING_LOG_SENT;
    $users = $DB->get_recordset_sql($sql);
    foreach ($users as $user) {
        //ToDo : manage attachments
        email_to_user($user, core_user::get_support_user(), $user->mailingsubject, strip_tags($user->mailingcontent), $user->mailingcontent);
        $ids_to_update[] = $user->logid;
    }
    $users->close();

    // Set emailstatus to MAILING_LOG_SENT on each sended email
    $ids = implode(",", array_unique($ids_to_update));
    $DB->execute("UPDATE {recalluser_logs} SET emailstatus = " . MAILING_LOG_SENT . " WHERE id IN ($ids)");

}

function generatecertificate() {

    global $DB;

    $sql = 'select * from mdl_customcert_issues where userid = :userid'; //check if user click on "view cert button"

    $emailotherslengthsql = $DB->sql_length('c.emailothers');
    $sql = "SELECT c.*, ct.id as templateid, ct.name as templatename, ct.contextid, co.id as courseid,
                       co.fullname as coursefullname, co.shortname as courseshortname
                  FROM {customcert} c
                  JOIN {customcert_templates} ct
                    ON c.templateid = ct.id
                  JOIN {course} co
                    ON c.course = co.id
                 WHERE (c.emailstudents = :emailstudents
                        OR c.emailteachers = :emailteachers
                        OR $emailotherslengthsql >= 3)";
    if (!$customcerts = $DB->get_records_sql($sql, array('emailstudents' => 1, 'emailteachers' => 1))) {
        return;
    }

    $template = new \stdClass();
    $template->id = $customcert->templateid;
    $template->name = $customcert->templatename;
    $template->contextid = $customcert->contextid;
    $template = new \mod_customcert\template($template);
    $filecontents = $template->generate_pdf(false, $user->id, true);

}
