<?php

require_once($CFG->dirroot . '/mod/custommailing/backup/moodle2/backup_custommailing_stepslib.php'); // Because it exists (must)
require_once($CFG->dirroot . '/mod/custommailing/backup/moodle2/backup_custommailing_settingslib.php'); // Because it exists (optional)

/**
 * custommailing backup task that provides all the settings and steps to perform one
 * complete backup of the activity
 */
class backup_custommailing_activity_task extends backup_activity_task
{

    /**
     * Define (add) particular settings this activity can have
     */
    protected function define_my_settings()
    {
        // No particular settings for this activity
    }

    /**
     * Define (add) particular steps this activity can have
     */
    protected function define_my_steps()
    {
        $this->add_step(new backup_custommailing_activity_structure_step('custommailing_structure', 'custommailing.xml'));
    }

    /**
     * Code the transformations to perform in the activity in
     * order to get transportable (encoded) links
     */
    static public function encode_content_links($content)
    {
        global $CFG;

        $base = preg_quote($CFG->wwwroot,"/");

        // Link to the list of choices
        $search="/(".$base."\/mod\/custommailing\/index.php\?id\=)([0-9]+)/";
        $content= preg_replace($search, '$@CUSTOMMAILINGINDEX*$2@$', $content);

        // Link to choice view by moduleid
        $search="/(".$base."\/mod\/choice\/view.php\?id\=)([0-9]+)/";
        $content= preg_replace($search, '$@CUSTOMMAILINGVIEWBYID*$2@$', $content);

        return $content;
    }
}
