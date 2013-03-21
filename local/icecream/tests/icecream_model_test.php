<?php

defined('MOODLE_INTERNAL') || die();

require_once dirname(dirname(__FILE__)) . '/models/icecream_model.php';

class icecream_model_test extends advanced_testcase {

    /**
     * @var icecream_model
     */
    protected $_cut;

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
    protected function setUp() {
        $this->_cut = new icecream_model();
        $this->_initial_icecream_data = array(
            1 => 'Chocolate',
            2 => 'Vanilla',
            3 => 'Strawberry',
            4 => 'Caramel',
        );
        $this->_initial_user_icecream_data = array(
            'local_user_icecream' => array(
                array('userid', 'icecreamid'),
                array(2, 2), // admin, Vanilla
                array(2, 3), // admin, Strawberry
            ),
        );
        $this->resetAfterTest();
    }

    /**
     * tests instantiation
     */
    public function test_instantiation() {
        $this->assertInstanceOf('icecream_model', $this->_cut);
    }

    /**
     * tests getting all icecreams from the database
     */
    public function test_all() {
        $all = $this->_cut->all();
        $this->assertSame(4, count($all));
        asort($this->_initial_icecream_data);
        foreach ($all as $id => $obj) {
            $this->assertSame($this->_initial_icecream_data[$id], $obj->title);
        }
    }

    /**
     * tests getting one icecream from the database
     */
    public function test_get() {
        foreach ($this->_initial_icecream_data as $key => $title) {
            $obj = $this->_cut->get($key);
            $this->assertSame($this->_initial_icecream_data[$key], $obj->title);
        }
    }

    /**
     * tests saving a new icecream
     * @global moodle_database $DB
     */
    public function test_save_new() {
        global $DB;
        $before = $DB->count_records('local_icecream');
        $this->_cut->save((object)array('title' => 'Raspberry'));
        $after = $DB->count_records('local_icecream');
        $this->assertSame($before + 1, $after);
        $this->assertTrue($DB->record_exists('local_icecream', array('title' => 'Raspberry')));
    }

    /**
     * tests saving an existing icecream
     * @global moodle_database $DB
     */
    public function test_save_existing() {
        global $DB;
        $vanilla = $this->_cut->get(2);
        $vanilla->title = 'Vanilla (renamed)';
        $before = $DB->count_records('local_icecream');
        $this->_cut->save($vanilla);
        $after = $DB->count_records('local_icecream');
        $this->assertSame($before, $after);
        $this->assertTrue($DB->record_exists('local_icecream', array('title' => 'Vanilla (renamed)')));
        $this->assertFalse($DB->record_exists('local_icecream', array('title' => 'Vanilla')));
    }

    /**
     * tests deleting an existing icecream
     * @global moodle_database $DB
     */
    public function test_delete() {
        global $DB;
        $before = $DB->count_records('local_icecream');
        $this->_cut->delete(1);
        $after = $DB->count_records('local_icecream');
        $this->assertSame($before - 1, $after);
        $this->assertFalse($DB->record_exists('local_icecream', array('id' => 1)));
    }

    /**
     * tests getting user icecreams
     */
    public function test_get_user_icecreams() {
        $this->loadDataSet($this->createArrayDataSet($this->_initial_user_icecream_data));
        $user_icecreams = $this->_cut->get_user_icecreams(2);
        $user_icecreams = array_map(function($icecreamid) {
            return (integer)$icecreamid;
        }, $user_icecreams);
        $this->assertSame(array(2, 3), $user_icecreams);
    }

    /**
     * tests setting user icecreams
     * @global moodle_database $DB
     */
    public function test_set_user_icecreams() {
        global $DB;
        $this->loadDataSet($this->createArrayDataSet($this->_initial_user_icecream_data));
        $before = $DB->count_records('local_user_icecream', array('userid' => 2));
        $this->_cut->set_user_icecreams(2, array(1, 3));
        $after = $DB->count_records('local_user_icecream', array('userid' => 2));
        $this->assertSame($before, $after);
        $user_icecreams = $DB->get_fieldset_select('local_user_icecream', 'icecreamid', 'userid = ?', array(2));
        $user_icecreams = array_map(function($icecreamid) {
            return (integer)$icecreamid;
        }, $user_icecreams);
        sort($user_icecreams);
        $this->assertSame(array(1, 3), $user_icecreams);
    }

    /**
     * tests adding a new user icecream
     * @global moodle_database $DB
     */
    public function test_add_user_icecream() {
        global $DB;
        $this->loadDataSet($this->createArrayDataSet($this->_initial_user_icecream_data));
        $before = $DB->count_records('local_user_icecream', array('userid' => 2));
        $this->_cut->add_user_icecream(2, 4);
        $after = $DB->count_records('local_user_icecream', array('userid' => 2));
        $this->assertSame($before + 1, $after);
        $user_icecreams = $DB->get_fieldset_select('local_user_icecream', 'icecreamid', 'userid = ?', array(2));
        $user_icecreams = array_map(function($icecreamid) {
            return (integer)$icecreamid;
        }, $user_icecreams);
        sort($user_icecreams);
        $this->assertSame(array(2, 3, 4), $user_icecreams);
    }

    /**
     * tests removing an existing user icecream
     * @global moodle_database $DB
     */
    public function test_remove_user_icecream() {
        global $DB;
        $this->loadDataSet($this->createArrayDataSet($this->_initial_user_icecream_data));
        $before = $DB->count_records('local_user_icecream', array('userid' => 2));
        $this->_cut->remove_user_icecream(2, 3);
        $after = $DB->count_records('local_user_icecream', array('userid' => 2));
        $this->assertSame($before - 1, $after);
        $user_icecreams = $DB->get_fieldset_select('local_user_icecream', 'icecreamid', 'userid = ?', array(2));
        $user_icecreams = array_map(function($icecreamid) {
            return (integer)$icecreamid;
        }, $user_icecreams);
        sort($user_icecreams);
        $this->assertSame(array(2), $user_icecreams);
    }

}
