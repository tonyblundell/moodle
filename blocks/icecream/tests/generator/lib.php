<?php

defined('MOODLE_INTERNAL') || die();

class block_icecream_generator extends phpunit_block_generator {

    /**
     * creates new block instance
     * @global moodle_database $DB
     * @global object $CFG
     * @param array $record
     * @param array $options
     * @return object
     */
    public function create_instance($record = null, array $options = null) {
        global $DB, $CFG;
        require_once "{$CFG->dirroot}/mod/page/locallib.php";

        $this->instancecount++;

        $record = (object)(array)$record;
        $record = $this->prepare_record($record);

        $id = $DB->insert_record('block_instances', $record);
        context_block::instance($id);

        return $DB->get_record('block_instances', array('id' => $id), '*', MUST_EXIST);
    }

}
