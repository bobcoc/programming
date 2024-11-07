<?php

    require_once('../../../config.php');
    require_once('../lib.php');

    $id = required_param('id', PARAM_INT);     // programming ID
    $code_id = required_param('code', PARAM_INT);
    $params = array('id' => $id, 'code' => $code_id);
    $PAGE->set_url('/mod/programming/presetcode/delete.php', $params);

    if (! $cm = get_coursemodule_from_id('programming', $id)) {
        print_error('invalidcoursemodule');
    }

    if (! $course = $DB->get_record('course', array('id' => $cm->course))) {
        print_error('coursemisconf');
    }

    if (! $programming = $DB->get_record('programming', array('id' => $cm->instance))) {
        print_error('invalidprogrammingid', 'programming');
    }

    require_login($course->id, true, $cm);
    $context = context_module::instance($cm->id);
    require_capability('mod/programming:edittestcase', $context);

    $DB->delete_records('programming_presetcode', array('id' => $code_id));
    programming_presetcode_adjust_sequence($programming->id);
    redirect(new moodle_url('/mod/programming/presetcode/list.php', array('id' => $cm->id)), get_string('presetcodedeleted', 'programming'));

?>
