<?php

defined('MOODLE_INTERNAL') || die;

class icecream_model {

    /**
     * c'tor
     */
    public function __construct() {
        // empty
    }

    /**
     * gets all icecreams
     * @global moodle_database $DB
     * @return array
     */
    public function all() {
        global $DB;
        return $DB->get_records('local_icecream', null, 'title');
    }

    /**
     * gets one icecream with the given id
     * @global moodle_database $DB
     * @param integer $id
     * @return object
     */
    public function get($id) {
        global $DB;
        return $DB->get_record('local_icecream', array('id' => $id));
    }

    /**
     * saves an icecream
     * @global moodle_database $DB
     * @param $obj
     */
    public function save($obj) {
        global $DB;
        if (isset($obj->id)) {
            $obj->timemodified = time();
            $DB->update_record('local_icecream', $obj);
        } else {
            $obj->timecreated = $obj->timemodified = time();
            $DB->insert_record('local_icecream', $obj);
        }
    }

    /**
     * deletes an existing icecream
     * @global moodle_database $DB
     * @param integer $id
     */
    public function delete($id) {
        global $DB;
        $DB->delete_records('local_user_icecream', array('icecreamid' => $id));
        $DB->delete_records('local_icecream', array('id' => $id));
    }

    /**
     * get user icecreams
     * @global moodle_database $DB
     * @param $userid
     * @return array
     */
    public function get_user_icecreams($userid) {
        global $DB;
        return $DB->get_fieldset_select('local_user_icecream', 'icecreamid', 'userid = :userid', array('userid' => $userid));
    }

    /**
     * set user icecreams
     * @global moodle_database $DB
     * @param $userid
     * @param array $user_icecreams
     */
    public function set_user_icecreams($userid, array $user_icecreams) {
        $old_user_icecreams = $this->get_user_icecreams($userid);
        if (!empty($old_user_icecreams)) {
            foreach ($old_user_icecreams as $user_icecream) {
                if (!in_array($user_icecream, $user_icecreams)) {
                    $this->remove_user_icecream($userid, $user_icecream);
                }
            }
        }
        if (empty($user_icecreams)) {
            return;
        }
        foreach ($user_icecreams as $user_icecream) {
            $this->add_user_icecream($userid, $user_icecream);
        }
    }

    /**
     * add user icecream
     * @global moodle_database $DB
     * @param $userid
     * @param $icecreamid
     */
    public function add_user_icecream($userid, $icecreamid) {
        global $DB;
        $a = array('userid' => $userid, 'icecreamid' => $icecreamid);
        if ($DB->record_exists('local_user_icecream', $a)) {
            return;
        }
        $DB->insert_record('local_user_icecream', (object)$a);
    }

    /**
     * remove user icecream
     * @global moodle_database $DB
     * @param $userid
     * @param $icecreamid
     */
    public function remove_user_icecream($userid, $icecreamid) {
        global $DB;
        $a = array('userid' => $userid, 'icecreamid' => $icecreamid);
        if (!$DB->record_exists('local_user_icecream', $a)) {
            return;
        }
        $DB->delete_records('local_user_icecream', $a);
    }

}
