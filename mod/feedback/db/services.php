<?php
    $services = array(
        'feedbackservice' => array(
            'functions' => array(
                'mod_feedback_get_questions',
                'mod_feedback_send_answers'
            ),
            'requiredcapability' => '',
            'restrictedusers' => 0,
            'enabled' => 1
        )
    );

    $functions = array(
        'mod_feedback_get_questions' => array(
            'classname' => 'mod_feedback_external',
            'methodname' => 'get_questions',
            'classpath' => 'mod/feedback/externallib.php',
            'description' => 'Gets all the questions for a feedback',
            'type' => 'read'
        ),
        'mod_feedback_send_answers' => array(
            'classname' => 'mod_feedback_external',
            'methodname' => 'send_answers',
            'classpath' => 'mod/feedback/externallib.php',
            'description' => 'Sends back all the answers for a specific feedback',
            'type' => 'write'
        )
    );
?>