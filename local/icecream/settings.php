<?php

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {
    $url = "{$CFG->wwwroot}/local/icecream/manage";
    $page = new admin_externalpage('local_icecream_manage', get_string('pluginname', 'local_icecream'), $url);
    $ADMIN->add('localplugins', $page);
}
