<?php

    require_once('../../../config.php');
    require_once('../lib.php');

    $id = required_param('id', PARAM_INT);     // programming ID
    $case_id = required_param('case', PARAM_INT); // testcase ID

    $params = array();
    if ($id) {
        $params['id'] = $id;
    }
    if ($case_id) {
        $params['case'] = $case_id;
    }
    $PAGE->set_url('/mod/programming/view.php', $params);

    if ($id) {
        if (! $cm = get_coursemodule_from_id('programming', $id)) {
            error('Course Module ID was incorrect');
        }
    
        if (! $course = $DB->get_record('course', array('id' => $cm->course))) {
            error('Course is misconfigured');
        }
    
        if (! $programming = $DB->get_record('programming', array('id' => $cm->instance))) {
            error('Course module is incorrect');
        }
    }

    require_login($course->id, true, $cm);
    $context = context_module::instance($cm->id);
    require_capability('mod/programming:edittestcase', $context);

    $DB->delete_records('programming_test_results', array('testid' => $case_id));
    $DB->delete_records('programming_tests', array('id' => $case_id));
    programming_testcase_adjust_sequence($programming->id);
    redirect(new moodle_url('list.php', array('id' => $cm->id)), get_string('testcasedeleted', 'programming'), 0);
