<?php

    require_once('../../config.php');
    require_once('lib.php');

    $id = optional_param('id',0, PARAM_INT);     // programming ID
    if(!$id){
        $a = optional_param('a',0, PARAM_INT);     // programming ID
    }
    $userid = optional_param('userid', 0, PARAM_INT);
    $submitid = optional_param('submitid', 0, PARAM_INT);

    $params = array('id' => $id);
    if ($userid) {
        $params['userid'] = $userid;
    }
    if ($submitid) {
        $params['submitid'] = $submitid;
    }
    $PAGE->set_url('/mod/programming/history.php', $params);

    if($a){
        if (! $cm = get_coursemodule_from_instance('programming', $a)) {
            print_error('invalidcoursemodule');
        }        
    }else{
        if (! $cm = get_coursemodule_from_id('programming', $id)) {
            print_error('invalidcoursemodule');
        }
    }

    if (! $course = $DB->get_record('course', array('id' => $cm->course))) {
        print_error('coursemisconf');
    }

    if (! $programming = $DB->get_record('programming', array('id' => $cm->instance))) {
        print_error('invalidprogrammingid', 'programming');
    }

    require_login($course->id, true, $cm);
    $context = context_module::instance($cm->id);

    require_capability('mod/programming:viewhistory', $context);
    if ($userid != 0 && $userid != $USER->id) {
       require_capability('mod/programming:viewotherprogram', $context);
    }

    if (!$userid) $userid = $USER->id;

    $submits = $DB->get_records('programming_submits', array('programmingid' => $programming->id, 'userid' => $userid), 'id DESC');
    if ($programming->presetcode) {
        if (is_array($submits)) {
            foreach ($submits as $submit) {
                $submit->code = programming_format_code($programming, $submit);
            }
        }
    }


/// Print the page header
    $PAGE->set_title($programming->name);
    $PAGE->set_heading(format_string($course->fullname));
    $PAGE->requires->css('/mod/programming/styles.css');
    $PAGE->requires->css('/mod/programming/js/dp/SyntaxHighlighter.css');
    $PAGE->requires->js('/mod/programming/js/dp/shCore.js');
    $PAGE->requires->js('/mod/programming/js/dp/shBrushCSharp.js');
    echo $OUTPUT->header();

/// Print tabs
    $renderer = $PAGE->get_renderer('mod_programming');
    $tabs = programming_navtab('history', null, $course, $programming, $cm);
    echo $renderer->render_navtab($tabs);

/// Print page content
    echo html_writer::tag('h2', $programming->name);
    if ($USER->id != $userid) {
        $u = $DB->get_record('user', array('id' => $userid));
        echo html_writer::tag('h3', get_string('viewsubmithistoryof', 'programming', fullname($u)));
    } else {
        echo html_writer::tag('h3', get_string('viewsubmithistory', 'programming'));
    }

    include_once('history.tpl.php');

    $PAGE->requires->js_init_call('M.mod_programming.init_history');
    $PAGE->requires->js_init_call('M.mod_programming.init_fetch_code');

/// Finish the page
    echo $OUTPUT->footer($course);

?>
