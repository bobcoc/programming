<?php
    include_once('../../config.php');
    include_once('lib.php');

    $submitid = optional_param('submitid', 0, PARAM_INT);

    $submit = $DB->get_record('programming_submits', array('id' => $submitid));
    if (! $programming = $DB->get_record('programming', array('id' => $submit->programmingid))) {
        error('Course module is incorrect');
    }
    if ($submit->userid != $USER->id) {
        if (! $course = $DB->get_record('course', array('id' => $programming->course))) {
            error('Course is misconfigured');
        }
        if (! $cm = get_coursemodule_from_instance('programming', $programming->id, $course->id)) {
            error('Course Module ID was incorrect');
        }
        $context = context_module::instance($cm->id);
        require_login($course->id);
        if (!has_capability('mod/programming:viewotherprogram', $context)) {
            $submit = null;
        }
    }
    if ($programming->presetcode) {
        $submit->code = programming_format_code($programming, $submit);
    }

    if ($submit) {
        echo str_replace("\r\n", "\r", $submit->code);
    }
?>
