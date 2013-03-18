<?php

// bootstrap Moodle
require_once dirname(dirname(dirname(__FILE__))) . '/config.php';

// require the icecream models and forms and bootstrap Silex and Twig
require_once dirname(__FILE__) . '/models/icecream_model.php';
require_once dirname(__FILE__) . '/forms/icecream_form.php';
require_once dirname(__FILE__) . '/bootstrap_silex.php';
require_once dirname(__FILE__) . '/bootstrap_twig.php';

// use the Request/Response classes
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

// unrestricted route showing all icecream flavours
$app->get('/', function() use ($app) {
    global $PAGE;
    $PAGE->set_pagelayout('standard');
    $model = new icecream_model();
    return $app['twig']->render('all_icecreams.twig', array('icecreams' => $model->all()));
});

// route accessible by admins allowing CRUD operations on icecream flavours
$app->get('/manage', function() use ($app) {
    require_capability('moodle/site:config', context_system::instance());
    $model = new icecream_model();
    return $app['twig']->render('manage_icecreams.twig', array('icecreams' => $model->all()));
})->bind('manage');

// route accessible by admins allowing an icecream to be created
$app->match('/create', function(Request $request) use ($app) {
    global $PAGE;
    require_capability('moodle/site:config', context_system::instance());
    $PAGE->set_context(context_system::instance());
    $form = new icecream_form();
    if ('POST' == $request->getMethod()) {
        if ($form->is_cancelled()) {
            redirect($app['url_generator']->generate('manage'));
        }
        $model = new icecream_model();
        $model->save($form->get_data());
        redirect($app['url_generator']->generate('manage'), get_string('changessaved'));
    }
    return $app['twig']->render('icecream_form.twig', array('form' => $form));
})->bind('create');

// route accessible by admins allowing an icecream to be updated
$app->match('/update/{id}', function(Request $request, $id) use ($app) {
    global $PAGE;
    require_capability('moodle/site:config', context_system::instance());
    $PAGE->set_context(context_system::instance());
    assert($id == $request->get('id'));
    $form = new icecream_form(new moodle_url($app['url_generator']->generate('update', array('id' => $id)), array('id' => $id)));
    $model = new icecream_model();
    $form->set_data($model->get($id));
    if ('POST' == $request->getMethod()) {
        if ($form->is_cancelled()) {
            redirect($app['url_generator']->generate('manage'));
        }
        $formdata = $form->get_data();
        $formdata->id = $id;
        $model->save($formdata);
        redirect($app['url_generator']->generate('manage'), get_string('changessaved'));
    }
    return $app['twig']->render('icecream_form.twig', array('form' => $form));
})->bind('update');

// route accessible by admins allowing an icecream to be deleted
$app->get('/delete/{id}', function($id) use ($app) {
    require_capability('moodle/site:config', context_system::instance());
    require_sesskey();
    $model = new icecream_model();
    $model->delete($id);
    redirect($app['url_generator']->generate('manage'), get_string('changessaved'));
})->bind('delete');

// route accessible by a logged in user allowing the user to update their preferred flavours
$app->match('/user', function(Request $request) use ($app) {
    global $USER, $PAGE;
    require_login();
    $PAGE->set_pagelayout('standard');
    $model = new icecream_model();
    $icecreams = $model->all();
    $user_icecreams = $model->get_user_icecreams($USER->id);
    if ('POST' == $request->getMethod()) {
        $user_icecreams = optional_param_array('user_icecreams', array(), PARAM_INT);
        $model->set_user_icecreams($USER->id, $user_icecreams);
        redirect($app['url_generator']->generate('user'), get_string('changessaved'));
    }
    return $app['twig']->render('user_icecreams.twig', array(
        'icecreams' => $icecreams,
        'user_icecreams' => $user_icecreams,
    ));
})->bind('user');

// run the app
$app->run();
