<?php

    require_once('../../config.php');
    require_once('lib.php');

    $id = required_param('id', PARAM_INT);     // programming ID
    $submitid = optional_param('submitid', 0, PARAM_INT);

    $params = array('id' => $id);
    if ($submitid) {
        $params['submitid'] = $submitid;
    }
    $PAGE->set_url('/mod/programming/result.php', $params);

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

    require_capability('mod/programming:viewdetailresult', $context);

    if (!$submitid) {
        // get the latest submitid of current user
        $r = $DB->get_record('programming_result', array('programmingid' => $programming->id, 'userid' => $USER->id));
        if (!empty($r)) $submitid = $r->latestsubmitid;
    }
    $submit = $DB->get_record('programming_submits', array('id' => $submitid));
    // Check is the user view result of others
    if (!empty($submit) && $submit->userid != $USER->id) {
        require_capability('mod/programming:viewotherresult', $context);
    }

    $viewhiddentestcase = has_capability('mod/programming:viewhiddentestcase', $context);


    // get title of the page
    if ($submit && $submit->userid != $USER->id) {
        $u = $DB->get_record('user', array('id' => $submit->userid));
        $title = get_string('viewtestresultof', 'programming', fullname($u));
        $pagename = $title;
    } else {
        $title = get_string('viewtestresult', 'programming');
        $pagename = get_string('result', 'programming');
    }

    $PAGE->set_title($programming->name);
    $PAGE->set_heading(format_string($course->fullname));
    $PAGE->requires->css('/mod/programming/styles.css');
    echo $OUTPUT->header();

/// Print tabs
    $renderer = $PAGE->get_renderer('mod_programming');
    $tabs = programming_navtab('result', null, $course, $programming, $cm);
    echo $renderer->render_navtab($tabs);

/// Print page content
    echo html_writer::tag('h2', $programming->name);
    echo html_writer::tag('h3', $title);
    if (empty($submit)) {
        echo '<p>'.get_string('cannotfindyoursubmit', 'programming').'</p>';
    } else {
        echo '<p>'.$currentstate = get_string('currentstatus', 'programming', programming_get_submit_status_desc($submit)).'</p>';

        if (!empty($submit->compilemessage)) {
            echo html_writer::tag('h3', get_string('compilemessage', 'programming'));
            echo $OUTPUT->box_start('compilemessage');
            echo programming_format_compile_message($submit->compilemessage);
            echo $OUTPUT->box_end();
        }

        if (!empty($submit->judgeresult)) {
            echo html_writer::tag('h3', get_string('judgeresult', 'programming'));
            $results = $DB->get_records('programming_test_results', array('submitid' => $submit->id), 'testid');

            if (!empty($results)) {
                if ($programming->showmode == PROGRAMMING_SHOWMODE_NORMAL || has_capability('mod/programming:viewdetailresultincontest', $context)) {
                    $tests = $DB->get_records('programming_tests', array('programmingid' => $programming->id), 'id');
                    uasort($results, 'cmp_results_by_test_seq');
                    echo html_writer::start_tag('div', array('id' => 'test-result-detail'));
                    echo html_writer::tag('p', get_string('testresult', 'programming', programming_get_test_results_desc($submit, $results)));
                    echo html_writer::tag('p', get_string('iostripped', 'programming', '1'));
                    print_test_result_table();
                    echo html_writer::end_tag('div');
                } else {
                    echo html_writer::tag('p', programming_contest_get_judgeresult($results));
                }
            }
        }

        $strviewprogram = get_string('viewprogram', 'programming');
        $viewprogramurl = 'history.php?id='.$id;
        if ($submitid) $viewprogramurl .= '&amp;userid='.$submit->userid;
        echo "<p><a href='$viewprogramurl'>$strviewprogram</a></p>";
    }

/// Finish the page
    echo $OUTPUT->footer($course);

function print_test_result_table()
{
    global $CFG, $OUTPUT, $PAGE;
    global $tests, $results;
    global $cm, $programming, $viewhiddentestcase, $params;

    $strsecuretestcase = get_string('securetestcase', 'programming');
    $strshowasplaintext = get_string('showasplaintext', 'programming');
    $strdownload = get_string('download', 'programming');

    $table = new html_table();
    $headers = array(
        get_string('testcasenumber', 'programming'),
        get_string('weight', 'programming'), //.$OUTPUT->help_icon('weight', 'programming'),
        get_string('timelimit', 'programming'), //.helpbutton('timelimit', 'timelimit', 'programming', true, false, '', true),
        get_string('memlimit', 'programming'), //.helpbutton('memlimit', 'memlimit', 'programming', true, false, '', true),
        get_string('input', 'programming'), //.helpbutton('input', 'input', 'programming', true, false, '', true),
        get_string('expectedoutput', 'programming'), //.helpbutton('expectedoutput', 'expectedoutput', 'programming', true, false, '', true),
        get_string('output', 'programming'), //.helpbutton('output', 'output', 'programming', true, false, '', true),
        get_string('errormessage', 'programming'), //.helpbutton('stderr', 'stderr', 'programming', true, false, '', true),
        get_string('timeused', 'programming'), //.helpbutton('timeused', 'timeused', 'programming', true, false, '', true),
        get_string('memused', 'programming'), //.helpbutton('memused', 'memused', 'programming', true, false, '', true),
        get_string('exitcode', 'programming'), //.helpbutton('exitcode', 'exitcode', 'programming', true, false, '', true),
        get_string('passed', 'programming'),
        get_string('judgeresult', 'programming'));
    $table->head = $headers;

    $table->attributes = array('id' => 'test-result-detail-table', 'class' => 'generaltable generalbox');
    $table->cellpadding = 3;
    $table->cellspacing = 1;
    $table->tablealign = 'center';
    $table->colclasses[4] = 'programming-io';
    $table->colclasses[5] = 'programming-io';
    $table->colclasses[6] = 'programming-io';
    $table->colclasses[7] = 'programming-io';

    if (!is_array($results)) $results = array();
    $i = 0; $id = 0;
    $rowclazz = array();
    foreach ($results as $result) {
        $rowclazz[] = $result->passed ? 'passed' : 'notpassed';
        $data = array();
        $data[] = $tests[$result->testid]->seq;
        $data[] = $tests[$result->testid]->weight;
        $data[] = programming_format_timelimit($tests[$result->testid]->timelimit);
        $data[] = programming_format_memlimit($tests[$result->testid]->memlimit);
        $downloadurl = new moodle_url($CFG->wwwroot.'/mod/programming/testcase/download_io.php', array('id' => $cm->id, 'test' => $result->testid));
        if (true||$viewhiddentestcase || programming_testcase_visible($tests, $result, true, $programming->timediscount <= time())) {
            // input
            $downloadurl->params(array('type' => 'in', 'download' => 0));
            $action = new popup_action('click', $downloadurl, '_blank', array('height' => 300, 'width' => 400));
            $html = $OUTPUT->action_link($downloadurl, $strshowasplaintext, $action);
            $html.= '&nbsp;';
            $downloadurl->remove_params('download');
            $html.= $OUTPUT->action_link($downloadurl, $strdownload);
            $html.= programming_format_io($tests[$result->testid]->input, true);
            $data[] = $html;

            // expected output
            $downloadurl->params(array('type' => 'out', 'download' => 0));
            $action = new popup_action('click', $downloadurl, '_blank', array('height' => 300, 'width' => 400));
            $html = $OUTPUT->action_link($downloadurl, $strshowasplaintext, $action);
            $html.= '&nbsp;';
            $downloadurl->remove_params('download');
            $html.= $OUTPUT->action_link($downloadurl, $strdownload);
            $html.= programming_format_io($tests[$result->testid]->output, true);
            $data[] = $html;

            // output
            if (!empty($result->output)) {
                $downloadurl->params(array('submit' => $result->submitid, 'type' => 'out', 'download' => 0));
                $action = new popup_action('click', $downloadurl, '_blank', array('height' => 300, 'width' => 400));
                $html = $OUTPUT->action_link($downloadurl, $strshowasplaintext, $action);

                $html.= '&nbsp;';
                $downloadurl->remove_params('download');
                $html.= $OUTPUT->action_link($downloadurl, $strdownload);
                $html.= programming_format_io($result->output, false);
                $data[] = $html;
            } else {
                $data[] = get_string('noresult', 'programming');
            }

            // error message
            if (!empty($result->stderr)) {
                $downloadurl->params(array('submit' => $result->submitid, 'type' => 'err', 'download' => 0));
                $action = new popup_action('click', $downloadurl, '_blank', array('height' => 300, 'width' => 400));
                $html = $OUTPUT->action_link($downloadurl, $strshowasplaintext, $action);
                $html.= '&nbsp;';
                $downloadurl->remove_params('download');
                $html.= $OUTPUT->action_link($downloadurl, $strdownload);
                $html.= programming_format_io($result->stderr, false);
                $data[] = $html;
            } else {
                $data[] = get_string('n/a', 'programming');
            }
        } else {
            $data[] = $strsecuretestcase; $data[] = $strsecuretestcase;
            $data[] = $strsecuretestcase; $data[] = $strsecuretestcase;
        }

        $data[] = round($result->timeused, 3);
        $data[] = $result->memused;
    
        if ($viewhiddentestcase || programming_testcase_visible($tests, $results)) {
            $data[] = $result->exitcode;
        } else {
            $data[] = $strsecuretestcase;
        }

        $data[] = get_string($result->passed ? 'yes' : 'no');
        $data[] = programming_get_judgeresult($result);
        $table->data[] = $data;
    }

    $table->rowclasses = $rowclazz;
    echo html_writer::table($table);
}

function cmp_results_by_test_seq($a, $b) {
    global $tests;
    return $tests[$a->testid]->seq - $tests[$b->testid]->seq;
}

?>
