<?php
/**
 * This script is owned by CBlue SPRL, please contact CBlue regarding any licences issues.
 *
 * @package:     mod_recalluser
 * @author:      jeanfrancois@cblue.be
 * @copyright:   CBlue SPRL, 2021
 */

/**
 * @param $recalluser
 * @return bool|int
 * @throws coding_exception
 * @throws dml_exception
 */
function recalluser_add_instance($recalluser) {
    global $DB;

    $recalluser->timecreated = time();

    // Check course has completion enabled, and enable it if not, and user has permission to do so.
    $course = $DB->get_record('course', array('id' => $recalluser->course));
    if (empty($course->enablecompletion)) {
        $coursecontext = context_course::instance($course->id);
        if (has_capability('moodle/course:update', $coursecontext)) {
            $data = array('id' => $course->id, 'enablecompletion' => '1');
            $DB->update_record('course', $data);
            rebuild_course_cache($course->id);
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

    if (!$recalluser = $DB->get_record('recalluser', array('id' => $id))) {
        return false;
    }

    $result = true;

    // Delete any dependent records here.
    if ($mailings = $DB->get_records('recalluser_mailing', array('cmid' => $recalluser->id))) {
        foreach ($mailings as $mailing) {
            if (!$DB->delete_records('recalluser_logs', array('mailingid' => $mailing->id))) {
                $result = false;
            }
        }
        $DB->delete_records('recalluser_mailing', array('cmid' => $recalluser->id));
    }

    if (!$DB->delete_records('recalluser', array('id' => $recalluser->id))) {
        $result = false;
    }

    return $result;
}