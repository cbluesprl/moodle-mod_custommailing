<?php

/**
 * Define the complete custommailing structure for backup, with file and id annotations
 */
class backup_custommailing_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {

        // To know if we are including userinfo
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated
        $custommailing= new backup_nested_element('custommailing', array('id'), array(
            'course', 'name', 'intro', 'introformat', 'timecreated', 'timemodified'));

        $custommailing_mailings = new backup_nested_element('custommailing_mailings');

        $custommailing_mailing = new backup_nested_element('custommailing_mailing', array('id'), array(
            'custommailingid', 'mailingname', 'mailinglang', 'mailingsubject',
            'mailingcontent', 'mailingcontentformat', 'mailingmode', 'mailingdelay',
            'mailingstatus', 'retroactive', 'targetmoduleid','targetmodulestatus', 'customcertmoduleid','starttime',
            'timecreated', 'timemodified'));

        $custommailing_logs = new backup_nested_element('custommailing_logs');

        $custommailing_log = new backup_nested_element('custommailing_log', array('id'), array(
            'custommailingmailingid', 'emailtouserid', 'emailstatus','timecreated', 'timemodified'));

        // Build the tree
        $custommailing->add_child($custommailing_mailings);
        $custommailing_mailings->add_child($custommailing_mailing);

        $custommailing->add_child($custommailing_logs);
        $custommailing_logs->add_child($custommailing_log);
        // Define sources
        $custommailing->set_source_table('custommailing', array('id' => backup::VAR_ACTIVITYID));

        $custommailing_mailing->set_source_sql('
            SELECT *
            FROM {custommailing_mailing}
            WHERE custommailingid = ?',
            array(backup::VAR_PARENTID));

        // All the rest of elements only happen if we are including user info
        if ($userinfo) {
            $custommailing_log->set_source_table('custommailing_logs', array('custommailingmailingid' => '../../id'));
        }
        // Define id annotations
        $custommailing_log->annotate_ids('user','emailtouserid');

        // Define file annotations

        // Return the root element (choice), wrapped into standard activity structure
        return $this->prepare_activity_structure($custommailing);
    }
}