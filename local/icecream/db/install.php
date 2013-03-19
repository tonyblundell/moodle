<?php

defined('MOODLE_INTERNAL') || die;

/**
 * populates the database with some initial data
 * @return boolean
 */
function xmldb_local_icecream_install() {
    require_once dirname(dirname(__FILE__)) . '/models/icecream_model.php';
    $model = new icecream_model();
    foreach (array('Chocolate', 'Vanilla', 'Strawberry', 'Caramel') as $title) {
        $model->save((object)array('title' => $title));
    }
    return true;
}
