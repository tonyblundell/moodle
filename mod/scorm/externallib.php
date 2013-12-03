<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * External SCORM API
 *
 * @package    mod_scorm
 * @copyright  
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once("$CFG->libdir/externallib.php");

class mod_scorm_external extends external_api {

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function get_attempt_status_parameters() {
        return new external_function_parameters(
            array('cmid' => new external_value(PARAM_INT, 'Course module id'))
        );
    }

    /**
     * Returns the attempt status info for the current user
     * @param int $cmid the course module id
     * @return array the attempt status info
     */
    public static function get_attempt_status($cmid) {
        global $CFG, $DB, $USER;
        require_once($CFG->dirroot . "/mod/scorm/locallib.php");
        $cm = get_coursemodule_from_id('scorm', $cmid, 0, true);
        $scorm = $DB->get_record('scorm', array('id'=>$cm->instance));
        $attemptstatus = array();

        // Num attempts allowed
        $attemptstatus['num_attempts_allowed'] = $scorm->maxattempt;

        // Num attempts made
        $attempts = scorm_get_attempt_count($USER->id, $scorm, true);
        if (empty($attempts)) {
            $attemptcount = 0;
        } else {
            $attemptcount = count($attempts);
        }
        $attemptstatus['num_attempts_made'] = $attemptcount;

        // Grading method
        if ($scorm->maxattempt == 1) {
            switch ($scorm->grademethod) {
                case GRADEHIGHEST:
                    $grademethod = get_string('gradehighest', 'scorm');
                break;
                case GRADEAVERAGE:
                    $grademethod = get_string('gradeaverage', 'scorm');
                break;
                case GRADESUM:
                    $grademethod = get_string('gradesum', 'scorm');
                break;
                case GRADESCOES:
                    $grademethod = get_string('gradescoes', 'scorm');
                break;
            }
        } else {
            switch ($scorm->whatgrade) {
                case HIGHESTATTEMPT:
                    $grademethod = get_string('highestattempt', 'scorm');
                break;
                case AVERAGEATTEMPT:
                    $grademethod = get_string('averageattempt', 'scorm');
                break;
                case FIRSTATTEMPT:
                    $grademethod = get_string('firstattempt', 'scorm');
                break;
                case LASTATTEMPT:
                    $grademethod = get_string('lastattempt', 'scorm');
                break;
            }
        }
        $attemptstatus['grading_method'] = $grademethod;

        // Attempt grades
        $attemptstatus['attempt_grades'] = array(); 
        if (!empty($attempts)) {
            $i = 1;
            foreach ($attempts as $attempt) {
                $gradereported = scorm_grade_user_attempt($scorm, $USER->id, $attempt->attemptnumber);
                if ($scorm->grademethod !== GRADESCOES && !empty($scorm->maxgrade)) {
                    $gradereported = $gradereported/$scorm->maxgrade;
                    $gradereported = number_format($gradereported*100, 0) .'%';
                }
                $attemptstatus['attempt_grades'][] = $gradereported;
                $i++;
            }
        }

        // Grade reported
        $calculatedgrade = scorm_grade_user($scorm, $USER->id);
        if ($scorm->grademethod !== GRADESCOES && !empty($scorm->maxgrade)) {
            $calculatedgrade = $calculatedgrade/$scorm->maxgrade;
            $calculatedgrade = number_format($calculatedgrade*100, 0) .'%';
        }
        if (empty($attempts)) {
            $attemptstatus['grade_reported'] = get_string('none');
        } else {
            $attemptstatus['grade_reported'] = $calculatedgrade;
        }

        return $attemptstatus;
    }

    /**
     * Returns description of method return value 
     * @return external_single_structure
     */ 
    public static function get_attempt_status_returns() {
        return new external_single_structure(
            array(
                'num_attempts_allowed'  => new external_value(PARAM_INT,    'Number of attempts allowed'),
                'num_attempts_made'     => new external_value(PARAM_INT,    'Number of attempts made'),
                'grading_method'        => new external_value(PARAM_TEXT,   'Grading method'),
                'grade_reported'        => new external_value(PARAM_TEXT,   'Grade reported'),
                'attempt_grades'        => new external_multiple_structure( 
                    new external_value(PARAM_TEXT, 'Attempt grade')
                )
            )
        );
    }

}
