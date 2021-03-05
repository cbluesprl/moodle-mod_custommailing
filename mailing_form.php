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

defined('MOODLE_INTERNAL') || die;

global $CFG;
require_once $CFG->libdir . '/formslib.php';

/**
 * Class mailing_form
 */
class mailing_form extends moodleform {

    /**
     * Define this form.
     */
    public function definition() {
        global $COURSE, $PAGE;

        $mform =& $this->_form;
        $course_module_context = $PAGE->context;

        $days = [];
        for ($i = 1; $i <= 30; $i++) {
            $days[$i] = $i;
        }

        $hours = [];
        for ($i = 0; $i < 24; $i++) {
            if ($i < 10) {
                $hours[$i] = "0$i";
            } else {
                $hours[$i] = $i;
            }
        }

        $minutes = [];
        for ($i = 0; $i < 60; $i += 5) {
            if ($i < 10) {
                $minutes[$i] = "0$i";
            } else {
                $minutes[$i] = $i;
            }
        }

        // Add name
        $mform->addElement('text', 'name', get_string('mailingname', 'mod_recalluser'));
        $mform->setType('name', PARAM_RAW_TRIMMED);

        // Add lang
        $mform->addElement('select', 'lang', get_string('mailinglang', 'mod_recalluser'), get_string_manager()->get_list_of_translations());
        $mform->setType('lang', PARAM_LANG);

        // Add target activity
        $activities = [];
        foreach ($modinfo = get_fast_modinfo($COURSE)->get_cms() as $cm) {
            if ($cm->id != $course_module_context->instanceid) {
                $activities[$cm->id] = format_string($cm->name);
            }
        }
        $mform->addElement('select', 'targetactivity', get_string('mailingtargetactivity', 'mod_recalluser'), $activities);
        $mform->setType('targetactivity', PARAM_LANG);

        // Add mode
        $mailing_mode = [];
        $mailing_mode[] =& $mform->createElement('radio', 'mailingmoderadio', null, '', 'option');
        $mailing_mode[] =& $mform->createElement('select', 'mailingmodenumber', null, $days);
        $mailing_mode[] =& $mform->createElement('static', '', null, '&nbsp;' . get_string('daysafter', 'mod_recalluser') . '&nbsp;');
        $mailing_mode[] =& $mform->createElement(
            'select', 'mailingmodeoption', null, [
                MAILING_MODE_DAYSFROMINSCRIPTIONDATE => get_string('courseenroldate', 'mod_recalluser'),
                MAILING_MODE_DAYSFROMLASTCONNECTION => get_string('courselastaccess', 'mod_recalluser'),
                MAILING_MODE_DAYSFROMFIRSTLAUNCH => get_string('firstlaunch', 'mod_recalluser'),
                MAILING_MODE_DAYSFROMLASTLAUNCH => get_string('lastlaunch', 'mod_recalluser'),
            ]
        );
        $mform->addGroup($mailing_mode, 'mailingmode', get_string('sendmailing', 'mod_recalluser'), ' ', false);
        $mform->addElement('radio', 'mailingmodefirstlaunch', null, get_string('atfirstlaunch', 'mod_recalluser'), MAILING_MODE_FIRSTLAUNCH);
        $mform->setType('mailingmodefirstlaunch', PARAM_BOOL);
        $mform->addElement('radio', 'mailingmodeenrol', null, get_string('atcourseenrol', 'mod_recalluser'), MAILING_MODE_REGISTRATION);
        $mform->setType('mailingmodeenrol', PARAM_BOOL);
        $mform->addElement('radio', 'mailingmodecomplete', null, get_string('atactivitycompleted', 'mod_recalluser'), MAILING_MODE_COMPLETE);
        $mform->setType('mailingmodecomplete', PARAM_BOOL);
        $mform->setDefault('mailingmode', 0);

        // Add subject
        $mform->addElement('text', 'subject', get_string('mailingsubject', 'mod_recalluser'));
        $mform->setType('subject', PARAM_RAW_TRIMMED);

        // Add body
        $mform->addElement('editor', 'body', get_string('mailingbody', 'mod_recalluser'), '', ['enable_filemanagement' => false]);
        $mform->setType('body', PARAM_RAW);

        // Add start time
        $start_time = [];
        $start_time[] =& $mform->createElement('select', 'starttimehour', '', $hours);
        $start_time[] =& $mform->createElement('static', '', null, '&nbsp:&nbsp;');
        $start_time[] =& $mform->createElement('select', 'starttimeminute', '', $minutes);
        $mform->addGroup($start_time, 'starttime', get_string('sendingtime', 'mod_recalluser'), ' ', false);

        // Add status
        $mform->addElement('selectyesno', 'enabled', get_string('enabled', 'mod_recalluser'));
        $mform->setType('enabled', PARAM_BOOL);
        $mform->setDefault('enabled', 1);

        // Add standard buttons, common to all modules.
        $this->add_action_buttons(true, get_string('createmailing', 'mod_recalluser'));
    }
}
