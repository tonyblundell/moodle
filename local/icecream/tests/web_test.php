<?php

// use the Client and Request classes
use Symfony\Component\HttpKernel\Client;
use Symfony\Component\HttpFoundation\Request;

defined('MOODLE_INTERNAL') || die();

class web_test extends advanced_testcase {

    /**
     * @var Silex\Application
     */
    protected $_app;

    /**
     * @var array
     */
    protected $_initial_icecream_data;

    /**
     * @var array
     */
    protected $_initial_user_icecream_data;

    /**
     * setUp
     */
    public function setUp() {
        // create test data
        $this->_initial_icecream_data = array(
            1 => 'Chocolate',
            2 => 'Vanilla',
            3 => 'Strawberry',
            4 => 'Caramel',
        );
        $this->_initial_user_icecream_data = array(
            'local_user_icecream' => array(
                array('userid', 'icecreamid'),
                array(3, 2), // Vanilla
                array(3, 3), // Strawberry
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
     * tests the / route
     */
    public function test_home_route() {
        $client = new Client($this->_app);
        $crawler = $client->request('GET', '/');
        $this->assertTrue($client->getResponse()->isOk());
        $this->assertCount(count($this->_initial_icecream_data), $crawler->filter('ul li.icecream'));
        $content = $client->getResponse()->getContent();
        foreach ($this->_initial_icecream_data as $icecream) {
            $this->assertRegExp("/{$icecream}/", $content);
        }
    }

    /**
     * tests the /manage route
     */
    public function test_manage_route() {
        // set up a new admin
        global $CFG;
        $user = $this->getDataGenerator()->create_user();
        self::setUser($user);
        $CFG->siteadmins .= ',' . $user->id;

        // request the page
        $client = new Client($this->_app);
        $crawler = $client->request('GET', '/manage');
        $this->assertTrue($client->getResponse()->isOk());
        $this->assertCount(count($this->_initial_icecream_data), $crawler->filter('ul li.icecream'));
        $content = $client->getResponse()->getContent();
        foreach ($this->_initial_icecream_data as $icecream) {
            $this->assertRegExp("/{$icecream}/", $content);
        }
        $this->assertCount(4, $crawler->filter('li.icecream a:contains("' . get_string('edit') . '")'));
        $this->assertCount(4, $crawler->filter('li.icecream a:contains("' . get_string('delete') . '")'));
    }

    /**
     * tests the /create route
     * @global moodle_database $DB
     */
    public function test_create_route() {
        global $DB;

        // set up a new admin
        global $CFG;
        $user = $this->getDataGenerator()->create_user();
        self::setUser($user);
        $CFG->siteadmins .= ',' . $user->id;

        // request the page
        $client = new Client($this->_app);
        $crawler = $client->request('GET', '/create');
        $this->assertTrue($client->getResponse()->isOk());
        $this->assertCount(1, $crawler->filter('#id_title'));

        // post the form
        $form = $crawler->selectButton(get_string('savechanges'))->form();
        $crawler = $client->submit($form, array('title' => 'Banana'));
        $this->assertTrue($DB->record_exists('local_icecream', array('title' => 'Banana')));
    }

    /**
     * tests the /update route
     * @global moodle_database $DB
     */
    public function test_update_route() {
        global $DB;

        // set up a new admin
        global $CFG;
        $user = $this->getDataGenerator()->create_user();
        self::setUser($user);
        $CFG->siteadmins .= ',' . $user->id;

        // request the page
        $client = new Client($this->_app);
        $crawler = $client->request('GET', '/update/1');
        $this->assertTrue($client->getResponse()->isOk());
        $this->assertCount(1, $crawler->filter('#id_title'));

        // post the form
        $form = $crawler->selectButton(get_string('savechanges'))->form();
        $client->submit($form, array('title' => 'Chocolate (renamed)'));
        $this->assertTrue($DB->record_exists('local_icecream', array('title' => 'Chocolate (renamed)')));
        $this->assertFalse($DB->record_exists('local_icecream', array('title' => 'Chocolate')));
    }

    /**
     * tests the /delete route
     * @global moodle_database $DB
     */
    public function test_delete_route() {
        global $DB;

        // set up a new admin
        global $CFG;
        $user = $this->getDataGenerator()->create_user();
        self::setUser($user);
        $CFG->siteadmins .= ',' . $user->id;

        // sanity check
        global $USER;
        $this->assertSame($USER->sesskey, sesskey());

        // request the page
        $client = new Client($this->_app);
        $client->request('GET', '/delete/1', array('sesskey' => sesskey()));
        $this->assertTrue($client->getResponse()->isRedirect());
        $this->assertFalse($DB->record_exists('local_icecream', array('id' => 1)));
    }

    /**
     * tests the /user route
     * @global moodle_database $DB
     */
    public function test_user_route() {
        global $DB;

        // set up a new user
        $user = $this->getDataGenerator()->create_user();
        self::setUser($user);

        // load initial user icecream data
        $this->loadDataSet($this->createArrayDataSet($this->_initial_user_icecream_data));

        // request the page
        $client = new Client($this->_app);
        $crawler = $client->request('GET', '/user');
        $this->assertTrue($client->getResponse()->isOk());
        $this->assertCount(4, $crawler->filter('input[type="checkbox"]'));

        // pick new selection of icecreams and submit the form
        $form = $crawler->selectButton(get_string('submit'))->form();
        $form['user_icecreams[1]']->tick(); // Chocolate (since checkboxes are ordered alphabetically)
        $form['user_icecreams[3]']->untick(); // Vanilla (since checkboxes are ordered alphabetically)
        $client->submit($form);
        $user_icecreams = $DB->get_fieldset_select('local_user_icecream', 'icecreamid', 'userid = :userid', array('userid' => $user->id));
        $user_icecreams = array_map(function($icecreamid) {
            return (integer)$icecreamid;
        }, $user_icecreams);
        sort($user_icecreams);
        $this->assertSame(array(1, 3), $user_icecreams);
    }

}
