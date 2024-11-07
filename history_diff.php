<?php

    require_once('../../config.php');
    require_once('lib.php');
    
    $id = optional_param('id', 0, PARAM_INT);     // programming ID
    $s1 = required_param('s1', PARAM_INT);
    $s2 = required_param('s2', PARAM_INT);

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

    require_capability('mod/programming:viewhistory', $context);

    $submit1 = $DB->get_record('programming_submits', array('id' => $s1));
    $submit2 = $DB->get_record('programming_submits', array('id' => $s2));

    if ($submit1->userid != $USER->id || $submit2->userid != $USER->id) {
        require_capability('mod/programming:viewotherprogram', $context);
    }

/// Print the page header
    $pagename = get_string('submithistory', 'programming');
    $CFG->scripts[] = '/mod/programming/js/dp/shCore.js';
    $CFG->scripts[] = '/mod/programming/js/dp/shBrushCSharp.js';
    $CFG->stylesheets[] = '/mod/programming/js/dp/SyntaxHighlighter.css';
    include_once('pageheader.php');

/// Print tabs
    $currenttab = 'history';
    include_once('tabs.php');

/// Print page content

    ini_set("include_path", ".:./lib");
    require_once('Text/Diff.php');
    require_once('text_diff_render_html.php');

    $lines1 = explode("\n", $submit1->code);
    $lines2 = explode("\n", $submit2->code);

    $diff = new Text_Diff('auto', array($lines1, $lines2));

    $renderer = new Text_Diff_Renderer_html();

    echo '<pre>';
    echo $renderer->render($diff);
    echo '</pre>';

/// Finish the page
    $OUTPUT->footer($course);

?>
