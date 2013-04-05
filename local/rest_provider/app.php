<?php

define('NO_MOODLE_COOKIES', true);

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

// enable UrlGenerator service provider
$app->register(new Silex\Provider\UrlGeneratorServiceProvider());

// authentication middleware
$app->before(function(Request $request) use ($app) {
    global $DB;
    if (!$request->isXmlHttpRequest()) {
        return new Response(json_encode((object)array('error' => get_string('accessexception', 'webservice'))), 403, array(
            'Content-Type' => 'application/json',
        ));
    }

    // needs the following line/entry in .htaccess in order to pick up the 'Authorization' request header
    // RewriteRule .? - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
    $wstoken = trim(substr($request->headers->get('Authorization'), strlen('Bearer ')));

    if (!$token = $DB->get_record('external_tokens', array('token' => $wstoken, 'tokentype' => EXTERNAL_TOKEN_PERMANENT))) {
        return new Response(json_encode((object)array('error' => get_string('accessexception', 'webservice'))), 403, array(
            'Content-Type' => 'application/json',
        ));
    }

    if ($token->validuntil && $token->validuntil < time()) {
        $DB->delete_records('external_tokens', array('token' => $wstoken, 'tokentype' => EXTERNAL_TOKEN_PERMANENT));
        return new Response(json_encode((object)array('error' => get_string('accessexception', 'webservice'))), 403, array(
            'Content-Type' => 'application/json',
        ));
    }

    // log token access
    $DB->set_field('external_tokens', 'lastaccess', time(), array('id' => $token->id));
});

// get a single non-guest, non-admin, non-deleted user
$app->get('/v1/user/{id}', function(Request $request, $id) use ($app) {
    global $DB;
    try {
        $sql = "SELECT id, username, firstname, lastname, email FROM {user} WHERE id = :id AND username NOT IN ('guest', 'admin') AND deleted = 0";
        $user = $DB->get_record_sql($sql, array('id' => $id), MUST_EXIST);
        $user->id = (integer)$user->id;
        return new Response(json_encode((object)$user), 200, array(
            'Content-Type' => 'application/json',
        ));
    } catch (dml_missing_record_exception $e) {
        return new Response('', 404);
    } catch (dml_multiple_records_exception $e) {
        return new Response('', 404);
    } catch (Exception $e) {
        return new Response(json_encode((object)array('error' => $e->getMessage())), 500);
    }
});

// get non-guest, non-admin, non-deleted users
$app->get('/v1/user', function(Request $request) use ($app) {
    global $DB;
    try {
        $sql = "SELECT id, username, firstname, lastname, email FROM {user} WHERE username NOT IN ('guest', 'admin') AND deleted = 0";
        $users = $DB->get_records_sql($sql);
        $retval = array_values(array_map(function($user) {
            $user->id = (integer)$user->id;
            return $user;
        }, $users));
        return new Response(json_encode($retval), 200, array(
            'Content-Type' => 'application/json',
        ));
    } catch (Exception $e) {
        return new Response(json_encode((object)array('error' => $e->getMessage())), 500);
    }
});

// create a new user
$app->post('/v1/user', function(Request $request) use ($app) {
    global $CFG, $DB;
    try {
        $user = json_decode($request->getContent());
        $fields = array(
            'auth' => 'manual',
            'confirmed' => 1,
            'password' => 'changeme',
            'mnethostid' => $CFG->mnet_localhost_id,
            'timecreated' => time(),
            'timemodified' => time(),
            'lang' => $CFG->lang,
            'city' => '',
        );
        $user = (object)array_merge((array)$user, $fields);
        $id = $DB->insert_record('user', $user);
        return new Response('', 201, array(
            'Location' => "/user/{$id}/",
        ));
    } catch (Exception $e) {
        return new Response(json_encode((object)array('error' => $e->getMessage())), 500);
    }
});

// update a non-guest, non-admin, non-deleted user
$app->put('/v1/user/{id}', function(Request $request, $id) use ($app) {
    global $DB;
    try {
        $existing = $DB->get_record('user', array('id' => $id), '*', MUST_EXIST);
        $user = json_decode($request->getContent());
        $user->id = $id;
        $user = (object)array_merge((array)$existing, (array)$user);
        $DB->update_record('user', $user);
        return new Response('', 204);
    } catch (dml_missing_record_exception $e) {
        return new Response('', 404);
    } catch (Exception $e) {
        return new Response(json_encode((object)array('error' => $e->getMessage())), 500);
    }
});

// delete a non-guest, non-admin, non-deleted user
$app->delete('/v1/user/{id}', function(Request $request, $id) use ($app) {
    global $DB;
    try {
        $DB->delete_records('user', array('id' => $id));
        return new Response('', 204);
    } catch (Exception $e) {
        return new Response(json_encode((object)array('error' => $e->getMessage())), 500);
    }
});

// return the app
return $app;
