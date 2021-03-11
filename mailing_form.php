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
class mailing_form extends moodleform
{

    /**
     * @throws coding_exception
     * @throws moodle_exception
     */
    public function definition()
    {
        global $COURSE, $PAGE;

        $mform =& $this->_form;
        $course_module_context = $PAGE->context;
        $custom_cert = core_plugin_manager::instance()->get_plugin_info('mod_customcert');

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

        $source = [];
        $source[0] = get_string('select', 'mod_recalluser');
        $source[1] = get_string('module', 'mod_recalluser');
        $source[2] = get_string('course', 'mod_recalluser');
        if ($custom_cert) {
            $source[3] = get_string('certificate', 'mod_recalluser');
        }

        // Add id
        $mform->addElement('hidden', 'id', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id', $course_module_context->instanceid);

        if (!empty($this->_customdata['mailingid'])) {
            // Add mailingid
            $mform->addElement('hidden', 'mailingid', 'mailingid');
            $mform->setType('mailingid', PARAM_INT);
            $mform->setDefault('mailingid', $this->_customdata['mailingid']);
        }

        // Add name
        $mform->addElement('text', 'mailingname', get_string('mailingname', 'mod_recalluser'), 'maxlength="255" size="32"');
        $mform->setType('mailingname', PARAM_RAW_TRIMMED);
        $mform->addRule('mailingname', get_string('required'), 'required');

        // Add lang
        $mform->addElement('select', 'mailinglang', get_string('mailinglang', 'mod_recalluser'), get_string_manager()->get_list_of_translations());
        $mform->setType('mailinglang', PARAM_LANG);
        $mform->addRule('mailinglang', get_string('required'), 'required');

        // Select Source
        $mform->addElement('select', 'source', get_string('selectsource', 'mod_recalluser'), $source);
        $mform->setType('source', PARAM_INT);
        $mform->addRule('source', get_string('required'), 'required');

        // Add target activity
        $mform->addElement('select', 'targetmoduleid', get_string('targetmoduleid', 'mod_recalluser'), recalluser_get_activities());
        $mform->setType('targetmoduleid', PARAM_INT);
        $mform->addRule('targetmoduleid', get_string('required'), 'required');
        $mform->hideIf('targetmoduleid', 'source', 'noteq', 1);

        // Add custom cert
        if ($custom_cert) {
            $mform->addElement('select', 'customcert', get_string('certificate', 'mod_recalluser'), recalluser_getcustomcertsfromcourse($COURSE->id));
            $mform->setType('customcert', PARAM_INT);
            $mform->hideIf('customcert', 'source', 'noteq', 3);
        }

        // Add mode
        $mailing_mode_module = [];

        $mailing_mode_module[] =& $mform->createElement('radio', 'mailingmodemodule', null, '', 'option');
        $mailing_mode_module[] =& $mform->createElement('select', 'mailingdelaymodule', null, $days);
//        $mailing_mode_module[] =& $mform->createElement('static', '', null, '&nbsp;' . get_string('daysafter', 'mod_recalluser') . '&nbsp;');
        $mailing_mode_module[] =& $mform->createElement(
            'select', 'mailingmodemoduleoption', null, [
                MAILING_MODE_DAYSFROMFIRSTLAUNCH => get_string('firstlaunch', 'mod_recalluser'),
                MAILING_MODE_DAYSFROMLASTLAUNCH => get_string('lastlaunch', 'mod_recalluser'),
            ]
        );
//        $mailing_mode_module[] =& $mform->createElement('static', '', null, get_string('andtargetactivitynotcompleted', 'mod_recalluser'));
        $mform->addGroup($mailing_mode_module, 'mailingmodemodulegroup', get_string('sendmailing', 'mod_recalluser'), ' ', false);
        $mform->setType('mailingmodemodule', PARAM_INT);
        $mform->setDefault('mailingmodemodule', 0);
        $mform->hideIf('mailingmodemodule', 'source', 'noteq', 1);
        $mform->hideIf('mailingdelaymodule', 'source', 'noteq', 1);
        $mform->hideIf('mailingmodemoduleoption', 'source', 'noteq', 1);


        $mailing_mode[] =& $mform->createElement('radio', 'mailingmode', null, '', 'option');
        $mailing_mode[] =& $mform->createElement('select', 'mailingdelay', null, $days);
//        $mailing_mode[] =& $mform->createElement('static', 'mailingmodetext', null, '&nbsp;' . get_string('daysafter', 'mod_recalluser') . '&nbsp;');
        $mailing_mode[] =& $mform->createElement(
            'select', 'mailingmodeoption', null, [
                MAILING_MODE_DAYSFROMINSCRIPTIONDATE => get_string('courseenroldate', 'mod_recalluser'),
                MAILING_MODE_DAYSFROMLASTCONNECTION => get_string('courselastaccess', 'mod_recalluser'),
            ]
        );
//        $mailing_mode[] =& $mform->createElement('static', '', null, get_string('andtargetactivitynotcompleted', 'mod_recalluser'));
        $mform->addGroup($mailing_mode, 'mailingmodegroup', get_string('sendmailing', 'mod_recalluser'), ' ', false);
        $mform->addElement('radio', 'mailingmode', null, get_string('atcourseenrol', 'mod_recalluser'), MAILING_MODE_REGISTRATION);
        $mform->setType('mailingmode', PARAM_INT);
        $mform->setDefault('mailingmode', 0);
        $mform->hideIf('mailingmode', 'source', 'noteq', 2);
        $mform->hideIf('mailingdelay', 'source', 'noteq', 2);
        $mform->hideIf('mailingmodeoption', 'source', 'noteq', 2);

        // Add subject
        $mform->addElement('text', 'mailingsubject', get_string('mailingsubject', 'mod_recalluser'));
        $mform->setType('mailingsubject', PARAM_RAW_TRIMMED);
        $mform->addRule('mailingsubject', get_string('required'), 'required');

        // Add body
        $mform->addElement('editor', 'mailingcontent', get_string('mailingcontent', 'mod_recalluser'), '', ['enable_filemanagement' => false]);
        $mform->setType('mailingcontent', PARAM_RAW);
        $mform->addRule('mailingcontent', get_string('required'), 'required');

        // Add start time
        $start_time = [];
        $start_time[] =& $mform->createElement('select', 'starttimehour', '', $hours);
        $start_time[] =& $mform->createElement('static', '', null, '&nbsp:&nbsp;');
        $start_time[] =& $mform->createElement('select', 'starttimeminute', '', $minutes);
        $mform->addGroup($start_time, 'starttime', get_string('starttime', 'mod_recalluser'), ' ', false);
        $mform->addRule('starttime', get_string('required'), 'required');

        // Add status
        $mform->addElement('selectyesno', 'mailingstatus', get_string('enabled', 'mod_recalluser'));
        $mform->setType('mailingstatus', PARAM_BOOL);
        $mform->setDefault('mailingstatus', 1);

        // Add standard buttons, common to all modules.
        if (!empty($this->_customdata['mailingid'])) {
            $submitlabel = get_string('updatemailing', 'mod_recalluser');
        } else {
            $submitlabel = get_string('createmailing', 'mod_recalluser');
        }
        $this->add_action_buttons(true, $submitlabel);
    }

    /**
     * @param array $data
     * @param array $files
     * @return array
     * @throws coding_exception
     * @throws moodle_exception
     */
    public function validation($data, $files)
    {
        $errors = parent::validation($data, $files);
        if (empty(recalluser_get_activities()[(int) $data['targetmoduleid']])) {
            $errors['targetmoduleid'] = get_string('targetactivitynotfound', 'mod_recalluser');
        }

        // TODO : validate custom cert (/!\ option "mode")

        return $errors;
    }
}
