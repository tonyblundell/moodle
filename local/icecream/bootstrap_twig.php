<?php

defined('MOODLE_INTERNAL') || die;

// enable Twig service provider
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => dirname(__FILE__) . '/templates',
    'twig.options' => array(
        'cache' => empty($CFG->disable_twig_cache) ? "{$CFG->dataroot}/twig_cache" : false,
        'auto_reload' => debugging('', DEBUG_MINIMAL),
    ),
));

// wrapper around Moodle's get_string() function
$function = new Twig_SimpleFunction('trans', function($identifier, $component = '', $a = null) {
    return s(get_string($identifier, $component, $a));
});
$app['twig']->addFunction($function);

// wrapper around initializing an admin page
$function = new Twig_SimpleFunction('adminpage', function($section) {
    global $CFG;
    require_once("{$CFG->libdir}/adminlib.php");
    admin_externalpage_setup($section);
});
$app['twig']->addFunction($function);

// wrapper around printing a Moodle header
$function = new Twig_SimpleFunction('header', function() {
    global $PAGE, $OUTPUT;
    $PAGE->set_context(context_system::instance());
    return $OUTPUT->header();
});
$app['twig']->addFunction($function);

// wrapper around printing a Moodle footer
$function = new Twig_SimpleFunction('footer', function() {
    global $OUTPUT;
    return $OUTPUT->footer();
});
$app['twig']->addFunction($function);

// wrapper around displaying the user's session key
$function = new Twig_SimpleFunction('sesskey', function() {
    global $USER;
    sesskey();
    return $USER->sesskey;
});
$app['twig']->addFunction($function);

// wrapper around displaying a moodle form
$function = new Twig_SimpleFunction('form', function (moodleform $form) {
    ob_start();
    $form->display();
    return ob_get_clean();
});
$app['twig']->addFunction($function);

// wrapper around getting the wwwroot
$function = new Twig_SimpleFunction('wwwroot', function() {
    global $CFG;
    return $CFG->wwwroot;
});
$app['twig']->addFunction($function);

// wrapper around Moodle's isloggedin() function
$function = new Twig_SimpleFunction('isloggedin', function() {
    return isloggedin();
});
$app['twig']->addFunction($function);

// wrapper around requiring js
$function = new Twig_SimpleFunction('js', function($path) {
    global $PAGE;
    $PAGE->requires->js($path);
});
$app['twig']->addFunction($function);

// wrapper around requiring css
$function = new Twig_SimpleFunction('css', function($path) {
    global $PAGE;
    $PAGE->requires->css($path);
});
$app['twig']->addFunction($function);
