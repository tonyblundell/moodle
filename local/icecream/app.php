<?php

// use the Request/Response classes
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

// bootstrap Moodle
require_once dirname(dirname(dirname(__FILE__))) . '/config.php';
global $CFG;

// require the icecream model and form
require_once dirname(__FILE__) . '/models/icecream_model.php';
require_once dirname(__FILE__) . '/forms/icecream_form.php';

// create Silex app
require_once "{$CFG->dirroot}/vendor/autoload.php";
$app = new Silex\Application();
$app['debug'] = debugging('', DEBUG_MINIMAL);

// enable UrlGenerator service provider
$app->register(new Silex\Provider\UrlGeneratorServiceProvider());

// enable Twig service provider
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => dirname(__FILE__) . '/templates',
    'twig.options' => array(
        'cache' => empty($CFG->disable_twig_cache) ? "{$CFG->dataroot}/twig_cache" : false,
        'auto_reload' => debugging('', DEBUG_MINIMAL),
    ),
));

// require Twig library functions
require dirname(__FILE__) . '/twiglib.php';

// unrestricted route showing all icecream flavours
$app->get('/', function() use ($app) {
    // set up Moodle page
    global $PAGE;
    $PAGE->set_context(context_system::instance());
    $PAGE->set_pagelayout('standard');
    $PAGE->set_url($app['url_generator']->generate('home'));

    // create model and render
    $model = new icecream_model();
    return $app['twig']->render('all_icecreams.twig', array(
        'plugin' => 'local_icecream',
        'icecreams' => $model->all(),
    ));
})->bind('home');

// route accessible by admins allowing CRUD operations on icecream flavours
$app->get('/manage', function() use ($app) {
    $model = new icecream_model();
    return $app['twig']->render('manage_icecreams.twig', array(
        'plugin' => 'local_icecream',
        'icecreams' => $model->all(),
    ));
})->bind('manage');

// route accessible by admins allowing an icecream to be created
$app->match('/create', function(Request $request) use ($app) {
    // set up Moodle page
    global $PAGE;
    $PAGE->set_context(context_system::instance());
    $PAGE->set_pagelayout('standard');
    $PAGE->set_url($app['url_generator']->generate('create'));

    // check capabilities
    require_capability('moodle/site:config', context_system::instance());

    // handle form submission
    $form = new icecream_form();
    if ('POST' == $request->getMethod()) {
        if ($form->is_cancelled()) {
            return $app->redirect($app['url_generator']->generate('manage'));
        }
        $model = new icecream_model();
        $model->save($form->get_data());
        return $app->redirect($app['url_generator']->generate('manage'));
    }

    // render
    return $app['twig']->render('icecream_form.twig', array(
        'plugin' => 'local_icecream',
        'form' => $form,
    ));
})->bind('create');

// route accessible by admins allowing an icecream to be updated
$app->match('/update/{id}', function(Request $request, $id) use ($app) {
    // set up Moodle page
    global $PAGE;
    $PAGE->set_context(context_system::instance());
    $PAGE->set_pagelayout('standard');
    $PAGE->set_url($app['url_generator']->generate('create'));

    // check capabilities
    require_capability('moodle/site:config', context_system::instance());

    // sanity check
    assert($id == $request->get('id'));

    // handle form submission
    $form = new icecream_form(new moodle_url($app['url_generator']->generate('update', array('id' => $id)), array('id' => $id)));
    $model = new icecream_model();
    $form->set_data($model->get($id));
    if ('POST' == $request->getMethod()) {
        if ($form->is_cancelled()) {
            return $app->redirect($app['url_generator']->generate('manage'));
        }
        $formdata = $form->get_data();
        $formdata->id = $id;
        $model->save($formdata);
        return $app->redirect($app['url_generator']->generate('manage'));
    }

    // render
    return $app['twig']->render('icecream_form.twig', array(
        'plugin' => 'local_icecream',
        'form' => $form,
    ));
})->bind('update');

// route accessible by admins allowing an icecream to be deleted
$app->get('/delete/{id}', function($id) use ($app) {
    // check capabilities and session key
    require_capability('moodle/site:config', context_system::instance());
    require_sesskey();

    // delete and redirect
    $model = new icecream_model();
    $model->delete($id);
    return $app->redirect($app['url_generator']->generate('manage'));
})->bind('delete');

// route accessible by a logged in user allowing the user to update their preferred flavours
$app->match('/user', function(Request $request) use ($app) {
    // set up Moodle page
    global $PAGE;
    $PAGE->set_context(context_system::instance());
    $PAGE->set_pagelayout('standard');
    $PAGE->set_url($app['url_generator']->generate('user'));

    // require login
    global $USER;
    require_login();

    // handle form submission
    $model = new icecream_model();
    $icecreams = $model->all();
    $user_icecreams = $model->get_user_icecreams($USER->id);
    if ('POST' == $request->getMethod()) {
        $user_icecreams = optional_param_array('user_icecreams', array(), PARAM_INT);
        $model->set_user_icecreams($USER->id, $user_icecreams);
        return $app->redirect($app['url_generator']->generate('user'));
    }

    // render
    return $app['twig']->render('user_icecreams.twig', array(
        'plugin' => 'local_icecream',
        'icecreams' => $icecreams,
        'user_icecreams' => $user_icecreams,
    ));
})->bind('user');

// return the app
return $app;
