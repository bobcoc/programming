<?php

    require_once('../../../config.php');
    require_once('../lib.php');

    $id = required_param('id', PARAM_INT);     // programming ID
    $testid = required_param('test', PARAM_INT);
    $submitid = optional_param('submit', -1, PARAM_INT);
    $type = required_param('type', PARAM_CLEAN);
    $download = optional_param('download', 1, PARAM_INT);

    $params = array();
    if ($id) {
        $params['id'] = $id;
    }
    if ($testid) {
        $params['test'] = $testid;
    }
    if ($submitid) {
        $params['submit'] = $submitid;
    }
    if ($type) {
        $params['type'] = $type;
    }
    if ($download) {
        $params['download'] = $download;
    }
    $PAGE->set_url('/mod/programming/download_io.php', $params);

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

    // Download input and output of testcase
    if ($type == 'in' or ($type == 'out' and $submitid == -1)) {
        if (! $test = $DB->get_record('programming_tests', array('id' => $testid))) {
            error('Test ID was incorrect');
        }
        programming_testcase_require_view_capability($context, $test);
        $filename = sprintf('test-%d.%s', $testid, $type);
        if ($type == 'in') {
            $content = !empty($test->gzinput) ? bzdecompress($test->gzinput) : $test->input;
        } else {
            $content = !empty($test->gzoutput) ? bzdecompress($test->gzoutput) : $test->output;
        }
    }
    // Download output and error message of user program
    else if ($type == 'out' or $type == 'err') {
        require_capability('mod/programming:viewdetailresult', $context);
        if (! $result = $DB->get_record('programming_test_results', array('submitid' => $submitid, 'testid' => $testid))) {
            error('Test ID or submit ID was incorrect.');
        }
        $test = $DB->get_record('programming_tests', array('id' => $testid));
        if ($test->pub >= 0) {
            require_capability('mod/programming:viewpubtestcase', $context);
        } else {
            require_capability('mod/programming:viewhiddentestcase', $context);
        }
        $submit = $DB->get_record('programming_submits', array('id' => $submitid));
        if ($submit->userid != $USER->id) {
            require_capability('mod/programming:viewotherresult', $context);
        }
        if ($result->judgeresult == 'AC' && strlen($result->output) == 0) {
            $result->output = $test->output;
        }
        $filename = sprintf('test-%d-%d.%s', $testid, $submitid, $type);
        $content = $type == 'out' ? $result->output : $result->stderr;
    }

    if ($filename && $download) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="'.$filename.'"');
    } else {
        header('Content-Type: text/plain');
    }
    echo $content;
