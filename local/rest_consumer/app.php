<?php

// use the Request/Response classes
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

// bootstrap Moodle
require_once dirname(dirname(dirname(__FILE__))) . '/config.php';
global $CFG;

// create Silex app
require_once "{$CFG->dirroot}/vendor/autoload.php";
$app = new Silex\Application();
$app['debug'] = debugging('', DEBUG_MINIMAL);

// enable Twig service provider
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => dirname(__FILE__) . '/templates',
    'twig.options' => array(
        'cache' => empty($CFG->disable_twig_cache) ? "{$CFG->dataroot}/twig_cache" : false,
        'auto_reload' => debugging('', DEBUG_MINIMAL),
    ),
));

// single route which launches the JavaScript SPA
$app->get('/', function() use ($app) {
    global $CFG;
    return $app['twig']->render('launch.twig', array(
        'base_url' => $CFG->wwwroot . '/local/rest_consumer',
        'api_url' => $CFG->wwwroot . '/local/rest_provider/v1/user',
        'wstoken' => 'a62314890a9af4f20b6aae7380553f42',
    ));
});

// return the app
return $app;
