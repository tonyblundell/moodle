<?php

defined('MOODLE_INTERNAL') || die();

require_once $CFG->libdir . '/formslib.php';

class icecream_form extends moodleform {

    /**
     * definition
     */
    protected function definition() {
        $mform = $this->_form;
        $mform->addElement('text', 'title', get_string('icecream_title', 'local_icecream'), array('maxlength' => 100, 'size' => 50));
        $mform->addRule('title', get_string('missingname'), 'required', null, 'client');
        $mform->setType('title', PARAM_TEXT);
        $this->add_action_buttons();
    }

}
