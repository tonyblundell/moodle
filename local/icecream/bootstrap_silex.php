<?php

defined('MOODLE_INTERNAL') || die;

require_once "{$CFG->dirroot}/vendor/autoload.php";
$app = new Silex\Application();
$app['debug'] = debugging('', DEBUG_MINIMAL);
$app->register(new Silex\Provider\UrlGeneratorServiceProvider());
