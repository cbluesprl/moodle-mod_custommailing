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
 * @package    mod_custommailing
 * @author     jeanfrancois@cblue.be,olivier@cblue.be
 * @copyright  2021 CBlue SPRL
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\notification;
use mod_custommailing\Mailing;
use mod_custommailing\MailingLog;

define('MAILING_MODE_NONE', 0);
define('MAILING_MODE_FIRSTLAUNCH', 1);
define('MAILING_MODE_REGISTRATION', 2);
define('MAILING_MODE_COMPLETE', 3);
define('MAILING_MODE_DAYSFROMINSCRIPTIONDATE', 4);
define('MAILING_MODE_DAYSFROMLASTCONNECTION', 5);
define('MAILING_MODE_DAYSFROMFIRSTLAUNCH', 6);
define('MAILING_MODE_DAYSFROMLASTLAUNCH', 7);
define('MAILING_MODE_SEND_CERTIFICATE', 8);

define('MAILING_STATUS_DISABLED', 0);
define('MAILING_STATUS_ENABLED', 1);

define('MAILING_LOG_IDLE', 0);
define('MAILING_LOG_PROCESSING', 1);
define('MAILING_LOG_SENT', 2);
define('MAILING_LOG_FAILED', 3);

define('MAILING_SOURCE_MODULE', 1);
define('MAILING_SOURCE_COURSE', 2);
define('MAILING_SOURCE_CERT', 3);

/**
 * @param $custommailing
 * @return bool|int
 * @throws coding_exception
 * @throws dml_exception
 */
function custommailing_add_instance($custommailing) {
    global $CFG, $DB;

    $custommailing->timecreated = time();
    $custommailing->timemodified = time();

    // Check if course has completion enabled, and enable it if not (and user has permission to do so)
    $course = $DB->get_record('course', ['id' => $custommailing->course]);
    if (empty($course->enablecompletion)) {
        if (empty($CFG->enablecompletion)) {
            // Completion tracking is disabled in Moodle
            notification::error(get_string('coursecompletionnotenabled', 'custommailing'));
        } else {
            // Completion tracking is enabled in Moodle
            if (has_capability('moodle/course:update', context_course::instance($course->id))) {
                $data = ['id' => $course->id, 'enablecompletion' => '1'];
                $DB->update_record('course', $data);
                rebuild_course_cache($course->id);
                notification::warning(get_string('coursecompletionenabled', 'custommailing'));
            } else {
                notification::error(get_string('coursecompletionnotenabled', 'custommailing'));
            }
        }

    }

    return $DB->insert_record('custommailing', $custommailing);
}

/**
 * @param $custommailing
 * @return bool
 * @throws dml_exception
 */
function custommailing_update_instance($custommailing) {
    global $DB;

    $custommailing->timemodified = time();
    $custommailing->id = $custommailing->instance;

    return $DB->update_record('custommailing', $custommailing);
}

/**
 * @param $id
 * @return bool
 * @throws dml_exception
 */
function custommailing_delete_instance($id) {
    global $DB;

    if (!$custommailing = $DB->get_record('custommailing', ['id' => $id])) {
        return false;
    }

    $result = true;

    // Delete any dependent mailing here.
    Mailing::deleteAll($custommailing->id);

    if (!$DB->delete_records('custommailing', ['id' => $custommailing->id])) {
        $result = false;
    }

    return $result;
}

/**
 * @param $feature
 * @return bool|null
 */
function custommailing_supports($feature) {
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
 * @param mixed $only [false for all OR modname (scorm, quiz, etc...)]
 * @return array
 * @throws moodle_exception
 */
function custommailing_get_activities ($only= false) {
    global $COURSE, $PAGE;
    $course_module_context = $PAGE->context;

    $activities = [];
    foreach ($modinfo = get_fast_modinfo($COURSE)->get_cms() as $cm) {
        if ($cm->id != $course_module_context->instanceid) {
            if (!$only || $cm->modname == $only) {
                $activities[(int) $cm->id] = format_string($cm->name);
            }
        }
    }

    return $activities;
}

/**
 * @param int $courseid
 * @return array
 * @throws dml_exception
 */
function custommailing_getcustomcertsfromcourse($courseid)
{
    global $DB;

    $certs = [];
    $result = $DB->get_records('customcert', ['course' => $courseid]);
    foreach ($result as $cert) {
        $certs[(int) $cert->id] = format_string($cert->name);
    }

    return $certs;
}

/**
 * @param int $customcertmoduleid
 * @return false|mixed|stdClass
 * @throws dml_exception
 */
function custommailing_getCmFromCustomcertInstance($customcertmoduleid)
{
    global $DB;

    $module = $DB->get_record('modules', ['name' => 'customcert']);

    if (!empty($module->id)) {
        return $DB->get_record('course_modules', ['instance' => $customcertmoduleid, 'module' => $module->id]);
    } else {
        return false;
    }
}

/**
 * @param int $customcertid
 * @return false|mixed|stdClass
 * @throws dml_exception
 */
function custommailing_getCustomcert($customcertid)
{
    global $DB;

    return $DB->get_record('customcert', ['id' => $customcertid]);
}

/**
 * @throws coding_exception
 * @throws dml_exception
 * @throws moodle_exception
 */
function custommailing_logs_generate()
{
    global $DB;

    $mailings = Mailing::getAllToSend();
    foreach ($mailings as $mailing) {
        $sql = custommailing_getsql($mailing);
        if ($sql) {
            $users = $DB->get_records_sql($sql);
            if (is_array($users)) {
                foreach ($users as $user) {
                    if (!empty($user->email) && !$DB->get_record('custommailing_logs', ['custommailingmailingid' => $mailing->id, 'emailtouserid' => $user->id])) {
                        $record = new stdClass();
                        $record->custommailingmailingid = (int) $mailing->id;
                        $record->emailtouserid = (int) $user->id;
                        $record->emailstatus = MAILING_LOG_PROCESSING;
                        $record->timecreated = time();
                        MailingLog::create($record);
                    }
                }
            }
        }
    }
}

/**
 * @param object $mailing
 *
 * @return false|string
 * @throws coding_exception
 * @throws dml_exception
 * @throws moodle_exception
 */
function custommailing_getsql($mailing)
{
    $sql = false;

    $config = get_config('custommailing');

    if (!empty($config->debugmode)) {
        $delay_range = 'MINUTE';
    } else {
        $delay_range = 'DAY';
    }

    // target module completion
    if (!empty($mailing->targetmoduleid)) {
        if (!empty($mailing->targetmodulestatus)) {
            $sql_where = " AND (cmc.completionstate IN (0,3) OR cmc.completionstate IS NULL)";
        } else {
            $sql_where = " AND cmc.completionstate IN (1,2)";
        }
    } else {
        $sql_where = '';
    }

    // mailing modes
    if ($mailing->mailingmode == MAILING_MODE_FIRSTLAUNCH && !empty($mailing->targetmoduleid)) {
        $sql = "SELECT DISTINCT u.*
                FROM {user} u
                JOIN {logstore_standard_log} lsl ON lsl.userid = u.id AND lsl.contextlevel = 70 AND lsl.contextinstanceid = $mailing->targetmoduleid AND lsl.action = 'viewed'
                ";
    } elseif ($mailing->mailingmode == MAILING_MODE_REGISTRATION && !empty($mailing->courseid)) {
        // retroactive mode
        if (!$mailing->retroactive) {
            $sql_retro = " AND ue.timecreated >= " . $mailing->timecreated . " AND (ue.timestart = 0 OR ue.timestart >= " . $mailing->timecreated . ") AND (ue.timeend = 0 OR ue.timeend >= " . $mailing->timecreated . ")";
        } else {
            $sql_retro = '';
        }
        //ToDo : check if user enrolled with different enrol methods to same course
        $sql = "SELECT DISTINCT u.*
                FROM {user} u
                JOIN {user_enrolments} ue ON ue.userid = u.id
                JOIN {enrol} e ON e.id = ue.enrolid 
                WHERE e.courseid = $mailing->courseid $sql_retro
                ";
    } elseif ($mailing->mailingmode == MAILING_MODE_COMPLETE && !empty($mailing->targetmoduleid)) {
        $sql = "SELECT DISTINCT u.*
                FROM {user} u
                JOIN {course_modules_completion} cmc ON cmc.userid = u.id AND cmc.coursemoduleid = $mailing->targetmoduleid
                ";
    } elseif ($mailing->mailingmode == MAILING_MODE_DAYSFROMINSCRIPTIONDATE && !empty($mailing->mailingdelay)) {
        // retroactive mode
        if (!$mailing->retroactive) {
            $sql_retro = " AND ue.timecreated >= " . $mailing->timecreated . " AND (ue.timestart = 0 OR ue.timestart >= " . $mailing->timecreated . ") AND (ue.timeend = 0 OR ue.timeend >= " . $mailing->timecreated . ")";
        } else {
            $sql_retro = '';
        }

        $start = new \DateTime();
        $interval_duration = "P" . $mailing->mailingdelay . "D";
        if ($delay_range == 'MINUTE') {
            $interval_duration = "PT" . $mailing->mailingdelay . "M";
        }
        $start->sub(new DateInterval($interval_duration));

        $sql = "SELECT DISTINCT u.*
                FROM {user} u
                JOIN {user_enrolments} ue ON ue.userid = u.id
                JOIN {enrol} e ON e.id = ue.enrolid 
                WHERE e.courseid = $mailing->courseid AND ue.timestart < " . $start->getTimestamp() . " $sql_retro
                ";
    } elseif ($mailing->mailingmode == MAILING_MODE_DAYSFROMLASTCONNECTION && !empty($mailing->courseid)) {
        // retroactive mode
        if (!$mailing->retroactive) {
            $sql_retro = " AND lsl.timecreated >= " . $mailing->timecreated;
        } else {
            $sql_retro = '';
        }

        $start = new \DateTime();
        $interval_duration = "P" . $mailing->mailingdelay . "D";
        if ($delay_range == 'MINUTE') {
            $interval_duration = "PT" . $mailing->mailingdelay . "M";
        }
        $start->sub(new DateInterval($interval_duration));

        $sql = "SELECT DISTINCT u.*
                FROM {user} u
                JOIN {course} c ON c.id = $mailing->courseid
                JOIN {logstore_standard_log} lsl ON lsl.userid = u.id AND lsl.contextlevel = 50 AND lsl.action = 'viewed' AND lsl.courseid = c.id
                WHERE lsl.timecreated < " . $start->getTimestamp() . " $sql_retro
                ";
    } elseif ($mailing->mailingmode == MAILING_MODE_DAYSFROMFIRSTLAUNCH && !empty($mailing->targetmoduleid) && !empty($mailing->mailingdelay)) {
        // retroactive mode
        if (!$mailing->retroactive) {
            $join_retro = " JOIN {user_enrolments} ue ON ue.userid = u.id
                            JOIN {enrol} e ON e.id = ue.enrolid
                            JOIN {course} c ON c.id = e.courseid ";
            $sql_where .= " AND c.id = " . $mailing->courseid . " AND ue.timecreated >= " . $mailing->timecreated . " AND (ue.timestart = 0 OR ue.timestart >= " . $mailing->timecreated . ") AND (ue.timeend = 0 OR ue.timeend >= " . $mailing->timecreated . ")";
        } else {
            $join_retro = '';
        }

        $start = new \DateTime();
        $interval_duration = "P" . $mailing->mailingdelay . "D";
        if ($delay_range == 'MINUTE') {
            $interval_duration = "PT" . $mailing->mailingdelay . "M";
        }
        $start->sub(new DateInterval($interval_duration));

        //ToDo : other modules than scorm
        $sql = "SELECT DISTINCT u.*
                FROM {user} u
                $join_retro
                JOIN {logstore_standard_log} lsl ON lsl.userid = u.id AND lsl.contextlevel = 70 AND lsl.contextinstanceid = $mailing->targetmoduleid AND lsl.action = 'launched' AND lsl.target = 'sco' 
                LEFT JOIN {course_modules_completion} cmc ON cmc.userid = u.id AND cmc.coursemoduleid = $mailing->targetmoduleid
                WHERE lsl.timecreated < " . $start->getTimestamp() . " $sql_where
                ";
    } elseif ($mailing->mailingmode == MAILING_MODE_DAYSFROMLASTLAUNCH && !empty($mailing->targetmoduleid) && !empty($mailing->mailingdelay)) {
        // retroactive mode
        if (!$mailing->retroactive) {
            $join_retro = " JOIN {user_enrolments} ue ON ue.userid = u.id
                            JOIN {enrol} e ON e.id = ue.enrolid 
                            JOIN {course} c ON c.id = e.courseid ";
            $sql_where .= " c.id = " . $mailing->courseid . " AND ue.timecreated >= " . $mailing->timecreated . " AND (ue.timestart = 0 OR ue.timestart >= " . $mailing->timecreated . ") AND (ue.timeend = 0 OR ue.timeend >= " . $mailing->timecreated . ")";
        } else {
            $join_retro = '';
        }

        $start = new \DateTime();
        $interval_duration = "P" . $mailing->mailingdelay . "D";
        if ($delay_range == 'MINUTE') {
            $interval_duration = "PT" . $mailing->mailingdelay . "M";
        }
        $start->sub(new DateInterval($interval_duration));

        //ToDo : other modules than scorm
        $sql = "SELECT DISTINCT u.*
                FROM {user} u
                $join_retro
                JOIN {logstore_standard_log} lsl ON lsl.userid = u.id AND lsl.contextlevel = 70 AND lsl.contextinstanceid = $mailing->targetmoduleid AND lsl.action = 'launched' AND lsl.target = 'sco' 
                LEFT JOIN {course_modules_completion} cmc ON cmc.userid = u.id AND cmc.coursemoduleid = $mailing->targetmoduleid
                WHERE lsl.timecreated < " . $start->getTimestamp() . " $sql_where
                ";
    } elseif ($mailing->mailingmode == MAILING_MODE_SEND_CERTIFICATE && !empty($mailing->customcertmoduleid)) {
        custommailing_certifications($mailing->customcertmoduleid, $mailing->courseid);
        // retroactive mode
        if (!$mailing->retroactive) {
            $join_retro = " JOIN {user_enrolments} ue ON ue.userid = u.id
                            JOIN {enrol} e ON e.id = ue.enrolid
                            JOIN {course} c ON c.id = e.courseid ";
            $sql_where = " WHERE c.id = " . $mailing->courseid . " AND ue.timecreated >= " . $mailing->timecreated . " AND (ue.timestart = 0 OR ue.timestart >= " . $mailing->timecreated . ") AND (ue.timeend = 0 OR ue.timeend >= " . $mailing->timecreated . ")";
        } else {
            $join_retro = '';
            $sql_where = '';
        }
        $sql = "SELECT DISTINCT u.*
                FROM {user} u
                $join_retro
                JOIN {customcert_issues} ci ON ci.userid = u.id AND ci.customcertid = $mailing->customcertmoduleid
                $sql_where
                ";
    }
    return $sql;
}

/**
 * Process custommailing_logs MAILING_LOG_SENT records
 * Send email to each user
 *
 * @throws dml_exception
 */
function custommailing_crontask() {

    global $DB;

    custommailing_logs_generate();

    $ids_to_update = [];

    $sql = "SELECT u.*, u.id as userid, rm.mailingsubject, rm.mailingcontent, rl.id as logid, rm.customcertmoduleid
            FROM {user} u
            JOIN {custommailing_logs} rl ON rl.emailtouserid = u.id 
            JOIN {custommailing_mailing} rm ON rm.id = rl.custommailingmailingid
            WHERE rl.emailstatus < " . MAILING_LOG_SENT;
    $logs = $DB->get_recordset_sql($sql);

    foreach ($logs as $log) {
        if (!empty($log->customcertmoduleid)) {
            $attachment = custommailing_getcertificate($log->userid, $log->customcertmoduleid);
        } else {
            $attachment = new stdClass();
            $attachment->file = '';
            $attachment->filename = '';
        }
        $log->mailingcontent = str_replace(['%firstname%', '%lastname%'], [$log->firstname, $log->lastname], $log->mailingcontent);
        if (email_to_user($log, core_user::get_support_user(), $log->mailingsubject, strip_tags($log->mailingcontent), $log->mailingcontent, $attachment->file, $attachment->filename)) {
            $ids_to_update[] = $log->logid;
        }
    }
    $logs->close();

    // Set emailstatus to MAILING_LOG_SENT on each sended email
    if (is_array($ids_to_update) && count($ids_to_update)) {
        $ids = implode(",", array_unique($ids_to_update));
        $DB->execute("UPDATE {custommailing_logs} SET emailstatus = " . MAILING_LOG_SENT . " WHERE id IN ($ids)");
    }
}

/**
 * get certificate (REQUIRE mod/customcert)
 *
 * @param int $userid
 * @param int $customcertid
 * @return stdClass
 * @throws dml_exception
 */
function custommailing_getcertificate($userid, $customcertid) {

    global $DB;

    $sql = "SELECT c.*, ct.id as templateid, ct.name as templatename, ct.contextid, co.id as courseid,
                       co.fullname as coursefullname, co.shortname as courseshortname
            FROM {customcert} c
            JOIN {customcert_templates} ct ON c.templateid = ct.id
            JOIN {course} co ON c.course = co.id
            JOIN {customcert_issues} ci ON ci.customcertid = c.id
            WHERE ci.userid = :userid AND c.id = :certid";
    $customcert = $DB->get_record_sql($sql, ['userid' => $userid, 'certid' => $customcertid]);

    $template = new \stdClass();
    $template->id = $customcert->templateid;
    $template->name = $customcert->templatename;
    $template->contextid = $customcert->contextid;
    $template = new \mod_customcert\template($template);
    $filecontents = $template->generate_pdf(false, $userid, true);

    // Set the name of the file we are going to send.
    $filename = $customcert->courseshortname . '_' . $customcert->name;
    $filename = \core_text::entities_to_utf8($filename);
    $filename = strip_tags($filename);
    $filename = rtrim($filename, '.');
    $filename = str_replace('&', '_', $filename) . '.pdf';

    // Create the file we will be sending.
    $tempdir = make_temp_directory('certificate/attachment');
    $tempfile = $tempdir . '/' . md5(microtime() . $userid) . '.pdf';
    file_put_contents($tempfile, $filecontents);

    $attach = new stdClass();
    $attach->file = $tempfile;
    $attach->filename = $filename;

    return $attach;
}

/**
 * @param int $customcertid
 * @param int $courseid
 * @throws coding_exception
 * @throws dml_exception
 * @throws moodle_exception
 */
function custommailing_certifications($customcertid, $courseid)
{
    global $DB;

    $cm = $DB->get_record_sql(
        "SELECT cm.*, md.name AS modname 
        FROM {course_modules} cm
        JOIN {modules} md ON md.id = cm.module
        WHERE cm.instance = :cminstance AND md.name = :modulename AND cm.course = :courseid",
        ['cminstance' => $customcertid, 'modulename' => 'customcert', 'courseid' => $courseid]
    );
    $users = $DB->get_records_sql(
        "SELECT DISTINCT u.*
        FROM {course} c
        JOIN {enrol} e ON e.courseid = c.id
        JOIN {user_enrolments} ue ON ue.enrolid = e.id
        JOIN {user} u ON u.id = ue.userid
        LEFT JOIN {customcert_issues} ci ON ci.userid = u.id AND ci.customcertid = :customcertid
        WHERE c.id = :courseid AND ci.id IS NULL",
        ['courseid' => $courseid, 'customcertid' => $customcertid]
    );
    foreach ($users as $user) {
        custommailing_certification($user->id, $customcertid, $courseid, $cm);
    }
}

/**
 * @param int $userid
 * @param int $customcertid
 * @param int $courseid
 * @throws coding_exception
 * @throws dml_exception
 * @throws moodle_exception
 */
function custommailing_certification($userid, $customcertid, $courseid, $cm)
{
    global $DB;

    $modinfo = get_fast_modinfo($courseid, $userid);
    $cminfo = $modinfo->get_cm($cm->id);
    $ainfomod = new \core_availability\info_module($cminfo);

    if ($ainfomod->is_available($availabilityinfo, false, $userid)) {
        if (!$DB->record_exists('customcert_issues', ['userid' => $userid, 'customcertid' => $customcertid])) {
            $customcertissue = new stdClass();
            $customcertissue->customcertid = $customcertid;
            $customcertissue->userid = $userid;
            $customcertissue->code = \mod_customcert\certificate::generate_code();
            $customcertissue->timecreated = time();
            $DB->insert_record('customcert_issues', $customcertissue);
        }
    }
}
