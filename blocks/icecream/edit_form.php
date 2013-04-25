<?php

defined('MOODLE_INTERNAL') || die();

class block_icecream_edit_form extends block_edit_form {

    /**
     * @param object $mform
     */
    protected function specific_definition($mform) {
        global $CFG;

        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

        // get the model
        require_once "{$CFG->dirroot}/local/icecream/models/icecream_model.php";
        $model = new icecream_model();
        $icecreams = $model->all();

        // for each icecream flavour, show a text field allowing the admin to configure a colour
        foreach ($icecreams as $icecream) {
            $mform->addElement('text', "config_{$icecream->id}", get_string('colour', 'block_icecream') . ' ' . $icecream->title . ' #', array(
                'maxlength' => 3,
                'size' => 5,
            ));
            $mform->setDefault("config_{$icecream->id}", '000');
            $mform->setType("config_{$icecream->id}", PARAM_ALPHANUM);
        }
    }

}
