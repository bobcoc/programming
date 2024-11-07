<?php

    require_once('../../../config.php');
    require_once('../lib.php');

    $id = required_param('id', PARAM_INT);     // programming ID
    $datafile_id = required_param('datafile', PARAM_INT);
    $direction = required_param('direction', PARAM_INT);
    $params = array('id' => $id, 'datafile' => $datafile_id, 'direction' => $direction);
    $PAGE->set_url('/mod/programming/datafile/move.php', $params);

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

    programming_datafile_adjust_sequence($programming->id, $datafile_id, $direction);
    redirect(new moodle_url('/mod/programming/datafile/list.php', array('id' => $cm->id)), get_string('datafilemoved', 'programming'), 1);

?>
