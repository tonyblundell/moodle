<?php

defined('MOODLE_INTERNAL') || die();

require_once dirname(dirname(dirname(__FILE__))) . '/moodleblock.class.php';
require_once dirname(dirname(__FILE__)) . '/block_icecream.php';

class block_icecream_test extends advanced_testcase {

    /**
     * @var block_icecream
     */
    protected $_cut;

    /**
     * @var array
     */
    protected $_initial_user_icecream_data;

    /**
     * setUp
     */
    protected function setUp() {
        $this->_cut = new block_icecream();
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
     * tests initialization
     */
    public function test_init() {
        $this->_cut->init();
        $this->assertEquals('Moodle Icecream', $this->_cut->title);
        $this->assertInstanceOf('Twig_Environment', $this->_cut->get_twig());
    }

    /**
     * tests content generation
     */
    public function test_get_content() {
        $this->loadDataSet($this->createArrayDataSet($this->_initial_user_icecream_data));
        $this->_cut->init();
        $this->_cut->set_userid(2);
        $content = $this->_cut->get_content();
        $this->assertRegExp('/vanilla/i', $content->text);
        $this->assertRegExp('/strawberry/i', $content->text);
        $this->assertNotRegExp('/chocolate/i', $content->text);
        $this->assertNotRegExp('/caramel/i', $content->text);
        $this->assertEquals(date('d/m/Y'), $content->footer);
    }

    /**
     * tests block instantiation
     * @global moodle_database $DB
     */
    public function test_block_instantiation() {
        global $DB;

        $before = $DB->count_records('block_instances');

        $generator = $this->getDataGenerator()->get_plugin_generator('block_icecream');
        $this->assertInstanceOf('block_icecream_generator', $generator);
        $this->assertEquals('icecream', $generator->get_blockname());

        foreach (range(1, 5) as $i) {
            $generator->create_instance();
            $this->assertEquals($before + $i, $DB->count_records('block_instances'));
        }
    }

}
