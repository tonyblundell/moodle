<?php
defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/externallib.php");

class mod_feedback_external extends external_api {

    public static function get_questions_parameters() {
        return new external_function_parameters(
            array(
                'coursemoduleid' => new external_value(
                    PARAM_INT, 'id of the course module id, assumes that it\'s a feedback id', VALUE_REQUIRED
                )
            )
        );
    }

    public static function get_questions_returns() {
        return new external_single_structure(
            array(
                'feedback' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'id' => new external_value(
                                PARAM_INT, 'Id of the feedback retrieved'
                            ),
                            'name' => new external_value(
                                PARAM_TEXT, 'Name of the feedback'
                            ),
                            'intro' => new external_value(
                                PARAM_RAW, 'Introduction to the feedback'
                            ),
                            'page_after_submit' => new external_value(
                                PARAM_RAW, 'Message to show after submission'
                            )
                        )
                    )
                ),
                'questions' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'id' => new external_value(PARAM_INT, 'Question id'),
                            'name' => new external_value(PARAM_TEXT, 'Question name'),
                            'label' => new external_value(PARAM_TEXT, 'Question label'),
                            'presentation' => new external_value(PARAM_RAW, 'Question presentation'),
                            'typ' => new external_value(PARAM_TEXT, 'Question type'),
                            'position' => new external_value(
                                PARAM_INT, 'Position of question within feedback'
                            ),
                            'required' => new external_value(
                                PARAM_INT, 'Whether the answering of this question is required before submission is allowed'
                            ),
                            'feedback' => new external_value(
                                PARAM_INT, 'Feedback id this question relates to.'
                            ),
                            'dependent_item' => new external_value(
                                PARAM_INT, 'Can only be answered after dependent item with specified id. 0 means no dependency.'
                            )
                        )
                    )
                ),
                'answers' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'id' => new external_value(PARAM_INT, 'answer id'),
                            'item' => new external_value(PARAM_INT, 'related question'),
                            'value' => new external_value(PARAM_TEXT, 'current answer')
                        )
                    )
                ),
                'Questions and Current answers for this feedback'
            )
        );
    }

    public static function get_questions($courseModuleId) {
        global $DB;

        $wstoken = required_param('wstoken', PARAM_TEXT);
        // Get the user associated with this web service token
        $sql = <<<SQL
SELECT userid FROM mdl_external_tokens WHERE token = ?
SQL;
        $result = $DB->get_records_sql($sql, array($wstoken));
        if (empty($result)) {
            throw new exception("No user associated with that web token");
        }
        $keys = array_keys($result);
        $userId = $keys[0];

        // Find the id of the feedback.
        $sql = <<<SQL
SELECT cm.instance FROM mdl_course_modules cm WHERE id = ? LIMIT 1
SQL;
        $instances = $DB->get_records_sql($sql, array($courseModuleId));
        $feedbackId = array_keys($instances);
        $feedbackId = $feedbackId[0];

        // Has the user already started this tracking?
        $sql = <<< SQL
SELECT id, feedback FROM mdl_feedback_tracking WHERE userid = ?
SQL;
        $alreadyStarted = $DB->get_records_sql($sql, array($userId));

        $sql = <<<SQL
SELECT id, name, intro, page_after_submit FROM mdl_feedback f WHERE f.id = ?
SQL;

        $feedback = $DB->get_records_sql($sql, array($feedbackId));

        // Get the questions that we want.
        $sql = <<<SQL
SELECT
    fi.id, fi.name, fi.label, fi.presentation, fi.typ, fi.position, fi.required,
    fi.feedback, fi.dependitem as `dependent_item`
FROM mdl_feedback f
LEFT JOIN mdl_feedback_item fi ON fi.feedback = f.id
WHERE f.id = ?
ORDER BY fi.position ASC
SQL;
        $questions = $DB->get_records_sql($sql, array($feedbackId));
        $questionIds = array_keys($questions);
        $answers = array();

        // If the user has already started answering questions, grab their
        // existing answers too.
        if (count(array_keys($alreadyStarted)) > 0) {
            $sql = <<<SQL
SELECT fv.id, fv.item, fv.value WHERE fv.item IN (
SQL;
            $sql .= substr(str_repeat("?,", count($questionIds)), 0, -1);
            $sql .= <<<SQL
)
SQL;
            $answers = $DB->get_records_sql($sql, $questionIds);
        }

        $response = array(
            'feedback' => $feedback,
            'questions' => $questions,
            'answers' => $answers
        );

        return $response;
    }

    public static function send_answers_parameters() {
        return new external_function_parameters(
            array(
                'answers' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'questionid' => new external_value(PARAM_INT, 'ID of the question'),
                            'answer' => new external_value(PARAM_RAW, 'answer', VALUE_REQUIRED)
                        )
                    ), 'answers to questions'
                )
            )
        );
    }

    public static function send_answers_returns() {
        return new external_single_structure(
            array(
                'answers' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'id' => new external_value(PARAM_INT, 'Answer id'),
                            'questionId' => new external_value(PARAM_INT, 'Question id'),
                            'success' => new external_value(
                                PARAM_INT, 'Success saving to db'
                            ),
                            'error' => new external_value(
                                PARAM_TEXT, 'Any errors from saving this answer'
                            ),
                            'answer' => new external_value(
                                PARAM_RAW, 'Raw answer text, however it was submitted'
                            )
                        )
                    )
                ),
                'The answers that have been successfully saved ( by id ), and the error in the event there was one.'
            )
        );
    }

    public static function send_answers($answers) {
        global $DB;

        $response = array();

        $itemId = $answers[0]['questionid'];
        $sql = <<<SQL
SELECT f.course FROM mdl_feedback_item fi
LEFT JOIN mdl_feedback f ON f.id = fi.feedback
WHERE fi.id = ?
SQL;
        $courseId = $DB->get_records_sql($sql, array($itemId));
        $courseId = array_keys($courseId);
        $courseId = $courseId[0];

        foreach ($answers as $answer) {
            $questionId = $answer['questionid'];
            $answer = $answer['answer'];
            $answerObj = new stdClass();
            $answerObj->course_id = $courseId;
            $answerObj->item = $questionId;
            $answerObj->completed = 0;
            $answerObj->tmp_completed = 0;
            $answerObj->value = $answer;

            // Does this answer already exist?
            $sql = <<<SQL
SELECT fv.id FROM mdl_feedback_value fv
WHERE fv.item = ? AND course_id = ? LIMIT 1
SQL;
            $found = $DB->get_records_sql($sql, array($questionId, $courseId));
            if (!$found) {
                // No results or something went wrong. Who cares - attempt to
                // insert the record.
                $insertId = $DB->insert_record(
                    'feedback_value', $answerObj, true, false
                );
                $response[] = array(
                    'id' => $insertId,
                    'questionId' => $questionId,
                    'value' => $answer,
                    'success' => true,
                    'error' => ''
                );
            } else {
                // We've already got a record...
                $found = array_keys($found);
                $found = $found[0];
                $answerObj->id = $found;
                $error = "";
                $success = true;
                try {
                    $DB->update_record('feedback_value', $answerObj, false);
                } catch (dml_exception $e) {
                    $error = $e->getMessage();
                    $success = false;
                }
                $response[] = array(
                    'id' => $found,
                    'questionId' => $questionId,
                    'success' => $success,
                    'error' => $error,
                    'answer' => $answer
                );
            }
        }

        return array('answers' => $response);
    }
}
?>