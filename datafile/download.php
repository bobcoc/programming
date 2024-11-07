<?php

    require_once('../../../config.php');
    require_once('../lib.php');

    $id = required_param('id', PARAM_INT);     // programming ID
    $datafile_id = required_param('datafile', PARAM_INT);
    $checkdata = optional_param('checkdata', 0, PARAM_INT);
    $params = array('id' => $id, 'datafile' => $datafile_id);
    if ($checkdata) {
        $params['checkdata'] = $checkdata;
    }
    $PAGE->set_url('/mod/programming/datafile/download.php', $params);

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

    if ($checkdata) {
        require_capability('mod/programming:viewhiddentestcase', $context);
    } else {
        require_capability('mod/programming:viewpubtestcase', $context);
    }


    $file = $DB->get_record('programming_datafile', array('id' => $datafile_id, 'programmingid' => $programming->id));
    if ($file) {
        $content = bzdecompress($checkdata ? $file->checkdata : $file->data);
        $size = $checkdata ? $file->checkdatasize : $file->datasize;
        if ($file->isbinary) {
            header('Content-Type: application/octec-stream');
        } else{
            header('Content-Type: plain/text');
        }
        header("Content-Disposition: attachment; filename=$file->filename");
        header("Content-Length: $size");
        print $content;
    } else {
        header('HTTP/1.0 404 Not Found');
        echo 'Not Found';
    }
?>
