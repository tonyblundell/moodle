<?php

defined('MOODLE_INTERNAL') || die();

class block_icecream extends block_base {

    /**
     * @var Twig_Environment
     */
    protected $_twig;

    /**
     * @var integer
     */
    protected $_userid;

    /**
     * @var array
     */
    protected $_icecreams;

    /**
     * bootstraps Twig, initializes the block
     */
    public function init() {
        global $CFG;

        // bootstrap Twig
        require_once "{$CFG->dirroot}/vendor/autoload.php";
        $loader = new Twig_Loader_Filesystem(__DIR__ . '/templates');
        $this->_twig = new Twig_Environment($loader, array(
            'cache' => empty($CFG->disable_twig_cache) ? "{$CFG->dataroot}/twig_cache" : false,
            'auto_reload' => debugging('', DEBUG_MINIMAL),
        ));

        // set the block's title
        $this->title = get_string('pluginname', 'block_icecream');
    }

    /**
     * hook invoked when configuration (specialization) has been made available
     */
    public function specialization() {
        // get the icecreams
        $this->_get_icecreams();

        // for each icecream, get its colour from block instance config, defaulting to black
        foreach ($this->_icecreams as $icecream) {
            $colour = '000';
            if (isset($this->config) && isset($this->config->{$icecream->id})) {
                $colour = $this->config->{$icecream->id};
            }
            $this->_icecreams[$icecream->id]->colour = $colour;
        }
    }

    /**
     * allow multiple instances
     * @return bool
     */
    public function instance_allow_multiple() {
        return true;
    }

    /**
     * accessor
     * @return Twig_Environment
     */
    public function get_twig() {
        return $this->_twig;
    }

    /**
     * accessor
     * @param integer $userid
     */
    public function set_userid($userid) {
        $this->_userid = $userid;
    }

    /**
     * gets the block's content
     * @return object
     */
    public function get_content() {
        global $CFG, $USER;

        // if no user has been set, use the logged in user by default
        if (empty($this->_userid)) {
            $this->set_userid($USER->id);
        }

        // if there's cached content, return it immediately
        if (!empty($this->content)) {
            return $this->content;
        }

        // get the model
        require_once "{$CFG->dirroot}/local/icecream/models/icecream_model.php";
        $model = new icecream_model();

        // render the templates
        $text = $this->_twig->render('text.twig', array(
            'icecreams' => $this->_get_icecreams(),
            'user_icecreams' => $model->get_user_icecreams($this->_userid),
        ));
        $footer = $this->_twig->render('footer.twig');

        // return the content
        $this->content = (object)array(
            'text' => $text,
            'footer' => $footer,
        );
        return $this->content;
    }

    /**
     * @return array
     */
    protected function _get_icecreams() {
        global $CFG;

        // return icecreams if they've already been fetched
        if (isset($this->_icecreams)) {
            return $this->_icecreams;
        }

        // get the model
        require_once "{$CFG->dirroot}/local/icecream/models/icecream_model.php";
        $model = new icecream_model();
        $this->_icecreams = $model->all();
        return $this->_icecreams;
    }

}
