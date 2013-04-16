<?php

// use the Client and Request classes
use Symfony\Component\HttpKernel\Client;
use Symfony\Component\HttpFoundation\Request;

defined('MOODLE_INTERNAL') || die();

class web_test extends advanced_testcase {

    const API = 'v1/user';
    const WSTOKEN = 'a62314890a9af4f20b6aae7380553f42';

    /**
     * @var Silex\Application
     */
    protected $_app;

    /**
     * @var array
     */
    protected $_wstoken_dataset;

    /**
     * setUp
     */
    public function setUp() {
        // web service token dataset
        $this->_wstoken_dataset = array(
            'external_tokens' => array(
                array('token', 'tokentype', 'userid', 'externalserviceid', 'contextid', 'creatorid', 'timecreated'),
                array(self::WSTOKEN, 0, 2, 1, 1, 2, time()),
            ),
        );

        // create Silex app
        $this->_app = require dirname(dirname(__FILE__)) . '/app.php';
        $this->_app['debug'] = true;
        $this->_app['exception_handler']->disable();

        // add middleware to work around Moodle expecting non-empty $_GET or $_POST
        $this->_app->before(function(Request $request) {
            if (empty($_GET) && 'GET' == $request->getMethod()) {
                $_GET = $request->query->all();
            }
            if (empty($_POST) && 'POST' == $request->getMethod()) {
                $_POST = $request->request->all();
            }
        });

        // tell Moodle to reset the database after every test
        $this->resetAfterTest();
    }

    /**
     * tearDown
     */
    public function tearDown() {
        $_GET = array();
        $_POST = array();
    }

    /**
     * tests requesting the home route without an XHR
     */
    public function test_home_route_without_xhr() {
        // load wstoken dataset
        $this->loadDataSet($this->createArrayDataSet($this->_wstoken_dataset));

        // request the collection
        $client = new Client($this->_app);
        $client->request('GET', self::API, array(), array(), array(
            'HTTP_AUTHORIZATION' => 'Bearer ' . self::WSTOKEN,
        ));
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode());
        $content = json_decode($client->getResponse()->getContent());
        $this->assertEquals(get_string('accessexception', 'webservice'), $content->error);
    }

    /**
     * tests requesting the home route without a valid wstoken
     */
    public function test_home_route_without_wstoken() {
        // request the collection
        $client = new Client($this->_app);
        $client->request('GET', self::API, array(), array(), array(
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
            'HTTP_AUTHORIZATION' => 'Bearer invalid_token',
        ));
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode());
        $content = json_decode($client->getResponse()->getContent());
        $this->assertEquals(get_string('accessexception', 'webservice'), $content->error);
    }

    /**
     * tests requesting the home route with a deleted user
     */
    public function test_home_route_with_deleted_user() {
        global $DB;

        // load wstoken dataset
        $this->loadDataSet($this->createArrayDataSet($this->_wstoken_dataset));
        $DB->set_field('user', 'deleted', 1, array('id' => 2));

        // request the collection
        $client = new Client($this->_app);
        $client->request('GET', self::API, array(), array(), array(
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
            'HTTP_AUTHORIZATION' => 'Bearer ' . self::WSTOKEN,
        ));
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode());
        $content = json_decode($client->getResponse()->getContent());
        $this->assertEquals(get_string('accessexception', 'webservice'), $content->error);
    }

    /**
     * tests requesting the home route with a suspended user
     */
    public function test_home_route_with_suspended_user() {
        global $DB;

        // load wstoken dataset
        $this->loadDataSet($this->createArrayDataSet($this->_wstoken_dataset));
        $DB->set_field('user', 'suspended', 1, array('id' => 2));

        // request the collection
        $client = new Client($this->_app);
        $client->request('GET', self::API, array(), array(), array(
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
            'HTTP_AUTHORIZATION' => 'Bearer ' . self::WSTOKEN,
        ));
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode());
        $content = json_decode($client->getResponse()->getContent());
        $this->assertEquals(get_string('accessexception', 'webservice'), $content->error);
    }

    /**
     * tests requesting the home route with an unconfirmed user
     */
    public function test_home_route_with_unconfirmed_user() {
        global $DB;

        // load wstoken dataset
        $this->loadDataSet($this->createArrayDataSet($this->_wstoken_dataset));
        $DB->set_field('user', 'confirmed', 0, array('id' => 2));

        // request the collection
        $client = new Client($this->_app);
        $client->request('GET', self::API, array(), array(), array(
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
            'HTTP_AUTHORIZATION' => 'Bearer ' . self::WSTOKEN,
        ));
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode());
        $content = json_decode($client->getResponse()->getContent());
        $this->assertEquals(get_string('accessexception', 'webservice'), $content->error);
    }

    /**
     * tests requesting the home route with a user with an auth method of 'nologin'
     */
    public function test_home_route_with_nologin_user() {
        global $DB;

        // load wstoken dataset
        $this->loadDataSet($this->createArrayDataSet($this->_wstoken_dataset));
        $DB->set_field('user', 'auth', 'nologin', array('id' => 2));

        // request the collection
        $client = new Client($this->_app);
        $client->request('GET', self::API, array(), array(), array(
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
            'HTTP_AUTHORIZATION' => 'Bearer ' . self::WSTOKEN,
        ));
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode());
        $content = json_decode($client->getResponse()->getContent());
        $this->assertEquals(get_string('accessexception', 'webservice'), $content->error);
    }

    /**
     * tests the / route
     */
    public function test_home_route() {
        // load wstoken dataset
        $this->loadDataSet($this->createArrayDataSet($this->_wstoken_dataset));

        // create a couple of users
        $users = array();
        $users[] = $this->getDataGenerator()->create_user();
        $users[] = $this->getDataGenerator()->create_user();

        // request the collection
        $client = new Client($this->_app);
        $client->request('GET', self::API, array(), array(), array(
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
            'HTTP_AUTHORIZATION' => 'Bearer ' . self::WSTOKEN,
        ));
        $this->assertTrue($client->getResponse()->isOk());
        $content = json_decode($client->getResponse()->getContent());
        $this->assertCount(2, $users);
        $this->assertCount(2, $content);

        // test the JSON response
        foreach ($content as $key => $user) {
            $this->assertEquals($user->id, $users[$key]->id);
            $this->assertSame($user->username, $users[$key]->username);
            $this->assertSame($user->firstname, $users[$key]->firstname);
            $this->assertSame($user->lastname, $users[$key]->lastname);
            $this->assertSame($user->email, $users[$key]->email);
        }
    }

    /**
     * tests that trying to fetch info about a non-existent user returns a 404
     */
    public function test_get_user_route_non_existing() {
        // load wstoken dataset
        $this->loadDataSet($this->createArrayDataSet($this->_wstoken_dataset));

        // pick an arbitrary invalid userid
        $invalid_userid = 999;

        // request the user
        $client = new Client($this->_app);
        $client->request('GET', self::API . '/' . $invalid_userid, array(), array(), array(
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
            'HTTP_AUTHORIZATION' => 'Bearer ' . self::WSTOKEN,
        ));
        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }

    /**
     * tests fetching info about a given user
     */
    public function test_get_user_route() {
        // load wstoken dataset
        $this->loadDataSet($this->createArrayDataSet($this->_wstoken_dataset));

        // create a user
        $user = $this->getDataGenerator()->create_user();

        // request the user
        $client = new Client($this->_app);
        $client->request('GET', self::API . '/' . $user->id, array(), array(), array(
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
            'HTTP_AUTHORIZATION' => 'Bearer ' . self::WSTOKEN,
        ));
        $this->assertTrue($client->getResponse()->isOk());
        $content = json_decode($client->getResponse()->getContent());
        $this->assertEquals($user->id, $content->id);
        $this->assertSame($user->username, $content->username);
        $this->assertSame($user->firstname, $content->firstname);
        $this->assertSame($user->lastname, $content->lastname);
        $this->assertSame($user->email, $content->email);
    }

    /**
     * tests creating a new user
     * @global moodle_database $DB
     */
    public function test_post_user_route() {
        global $DB;

        // load wstoken dataset
        $this->loadDataSet($this->createArrayDataSet($this->_wstoken_dataset));

        // pick an arbitrary username
        $username = 'bob.smith';

        // ensure user doesn't exist to begin with
        $this->assertFalse($DB->record_exists('user', array('username' => $username)));

        // raw JSON data to post
        $content = '{"username": "' . $username . '", "email": "bob@kineo-no-email.com", "firstname": "Bob", "lastname": "Smith"}';

        // post the user
        $client = new Client($this->_app);
        $client->request('POST', self::API, array(), array(), array(
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
            'HTTP_AUTHORIZATION' => 'Bearer ' . self::WSTOKEN,
        ), $content);
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertEquals(201, $client->getResponse()->getStatusCode());

        // get the newly created record
        try {
            $user = $DB->get_record('user', array('username' => $username));
            $this->assertEquals('bob@kineo-no-email.com', $user->email);
            $this->assertEquals('Bob', $user->firstname);
            $this->assertEquals('Smith', $user->lastname);
        } catch (Exception $e) {
            $this->fail($e->getMessage());
        }
    }

    /**
     * tests updating an existing user
     * @global moodle_database $DB
     */
    public function test_put_user_route() {
        global $DB;

        // load wstoken dataset
        $this->loadDataSet($this->createArrayDataSet($this->_wstoken_dataset));

        // create a user
        $user = $this->getDataGenerator()->create_user();

        // raw JSON data to post
        $content = '{"username": "' . $user->username . '-renamed", "email": "' . $user->email . '-renamed", "firstname": "' . $user->firstname . '-renamed", "lastname": "' . $user->lastname . '-renamed"}';

        // update the user
        $client = new Client($this->_app);
        $client->request('PUT', self::API . '/' . $user->id, array(), array(), array(
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
            'HTTP_AUTHORIZATION' => 'Bearer ' . self::WSTOKEN,
        ), $content);
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertEquals(204, $client->getResponse()->getStatusCode());

        // get the updated record
        try {
            $new_user = $DB->get_record('user', array('id' => $user->id));
            $this->assertEquals($user->username . '-renamed', $new_user->username);
            $this->assertEquals($user->email . '-renamed', $new_user->email);
            $this->assertEquals($user->firstname . '-renamed', $new_user->firstname);
            $this->assertEquals($user->lastname . '-renamed', $new_user->lastname);
        } catch (Exception $e) {
            $this->fail($e->getMessage());
        }
    }

    /**
     * tests that trying to update info about a non-existent user returns a 404
     */
    public function test_put_user_route_non_existing() {
        // load wstoken dataset
        $this->loadDataSet($this->createArrayDataSet($this->_wstoken_dataset));

        // pick an arbitrary invalid userid
        $invalid_userid = 999;

        // raw JSON data to post
        $user = (object)array(
            'username' => 'bob.smith',
            'firstname' => 'Bob',
            'lastname' => 'Smith',
            'email' => 'bob.smith@kineo-no-email.com',
        );
        $content = '{"username": "' . $user->username . '-renamed", "email": "' . $user->email . '-renamed", "firstname": "' . $user->firstname . '-renamed", "lastname": "' . $user->lastname . '-renamed"}';

        // update the user
        $client = new Client($this->_app);
        $client->request('PUT', self::API . '/' . $invalid_userid, array(), array(), array(
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
            'HTTP_AUTHORIZATION' => 'Bearer ' . self::WSTOKEN,
        ), $content);
        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }

    /**
     * tests deleting an existing user
     * @global moodle_database $DB
     */
    public function test_delete_route() {
        global $DB;

        // load wstoken dataset
        $this->loadDataSet($this->createArrayDataSet($this->_wstoken_dataset));

        // create a user
        $user = $this->getDataGenerator()->create_user();

        // delete the user
        $client = new Client($this->_app);
        $client->request('DELETE', self::API . '/' . $user->id, array(), array(), array(
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
            'HTTP_AUTHORIZATION' => 'Bearer ' . self::WSTOKEN,
        ));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertEquals(204, $client->getResponse()->getStatusCode());
        $this->assertFalse($DB->record_exists('user', array('id' => $user->id)));
    }

    /**
     * tests that trying to delete a non-existent user returns a 404
     */
    public function test_delete_route_non_existing() {
        // load wstoken dataset
        $this->loadDataSet($this->createArrayDataSet($this->_wstoken_dataset));

        // pick an arbitrary invalid userid
        $invalid_userid = 999;

        // delete the user
        $client = new Client($this->_app);
        $client->request('DELETE', self::API . '/' . $invalid_userid, array(), array(), array(
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
            'HTTP_AUTHORIZATION' => 'Bearer ' . self::WSTOKEN,
        ));
        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }

}
