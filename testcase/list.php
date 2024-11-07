<?php

    require_once('../../../config.php');
    require_once($CFG->libdir.'/tablelib.php');
    require_once('../lib.php');

    $id = required_param('id', PARAM_INT);     // programming ID
    $params = array('id' => $id);
    $PAGE->set_url('/mod/programming/testcase/list.php', $params);

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

    require_capability('mod/programming:viewhiddentestcase', $context);

/// Print the page header
    $PAGE->set_title(format_string($course->shortname).': '.$programming->name).': '.get_string('testcase', 'programming');
    $PAGE->set_heading(format_string($course->fullname));
    echo $OUTPUT->header();

/// Print tabs
    $renderer = $PAGE->get_renderer('mod_programming');
    $tabs = programming_navtab('edittest', 'testcase', $course, $programming, $cm);
    echo $renderer->render_navtab($tabs);

/// Print page content
    echo html_writer::tag('h2', $programming->name);
    echo html_writer::tag('h3', get_string('testcase', 'programming').$OUTPUT->help_icon('testcase', 'programming'));
    print_testcase_table();

/// Finish the page
    echo $OUTPUT->footer($course);

function print_testcase_table() {
    global $CFG, $OUTPUT, $DB, $cm, $params, $programming, $course, $language, $groupid;

    $table = new html_table();
    $table->head = array(
        get_string('sequence', 'programming'),
        get_string('testcasepub', 'programming').$OUTPUT->help_icon('testcasepub', 'programming'),
        get_string('input', 'programming').$OUTPUT->help_icon('input', 'programming'),
        get_string('output', 'programming').$OUTPUT->help_icon('output', 'programming'),
        get_string('timelimit', 'programming').$OUTPUT->help_icon('timelimit', 'programming'),
        get_string('memlimit', 'programming').$OUTPUT->help_icon('memlimit', 'programming'),
        get_string('extraproc', 'programming').$OUTPUT->help_icon('nproc', 'programming'),
        get_string('weight', 'programming').$OUTPUT->help_icon('weight', 'programming'),
        get_string('action'),
        );

    //$table->set_attribute('id', 'presetcode-table');
    //$table->set_attribute('class', 'generaltable generalbox');
    $table->tablealign = 'center';
    $table->cellpadding = 3;
    $table->cellspacing = 1;
    $table->colclasses[2] = 'programming-io';
    $table->colclasses[3] = 'programming-io';
    //$table->no_sorting('code');
    $table->data = array();

    $strshowasplaintext = get_string('showasplaintext', 'programming');
    $strdownload = get_string('download', 'programming');
    $stredit = get_string('edit');
    $strdelete = get_string('delete');
    $strmoveup = get_string('moveup');
    $strmovedown = get_string('movedown');
    $fields = 'id,programmingid,seq,input,output,cmdargs,timelimit,memlimit,nproc,pub,weight,memo,timemodified';
	$tests = $DB->get_records('programming_tests', array('programmingid' => $programming->id), 'seq',$fields);

    if (is_array($tests)) {
        $tests_count = count($tests)-1;
        $i = 0;
        foreach ($tests as $case) {
            $data = array();
            $data[] = $case->seq;
            $data[] = programming_testcase_pub_getstring($case->pub);

            // stdin
            $url = new moodle_url('/mod/programming/testcase/download_io.php', array('id' => $cm->id, 'test' => $case->id, 'type'=> 'in', 'download' => 0));
            $html = $OUTPUT->action_link($url, $strshowasplaintext, new popup_action('click', $url), array('class' => 'showasplaintext small'));
            $html .= '&nbsp;';
            $url->param('download', 1);
            $html .= $OUTPUT->action_link($url, $strdownload, null, array('class' => 'download small'));
            $html .= programming_format_io($case->input, false);
            $data[] = $html;

            // stdout
            $url->params(array('type' => 'out', 'download' => 0));
            $html = $OUTPUT->action_link($url, $strshowasplaintext, new popup_action('click', $url), array('class' => 'showasplaintext small'));
            $html .= '&nbsp;';
            $url->param('download', 1);
            $html .= $OUTPUT->action_link($url, $strdownload, null, array('class' => 'download small'));
            $html .= programming_format_io($case->output, false);
            $data[] = $html;

            // limits
            $data[] = get_string('nseconds', 'programming', $case->timelimit);
            $data[] = get_string('nkb', 'programming', $case->memlimit);
            $data[] = $case->nproc;

            $data[] = get_string('nweight', 'programming', $case->weight);

            // actions
            $actions = array();
            $actions[] = $OUTPUT->action_link(
                new moodle_url('edit.php', array('id' => $cm->id, 'case' => $case->id)),
                html_writer::empty_tag('img', array('title' => $stredit, 'src' => $OUTPUT->image_url('t/edit'))),
                null,
                array('class' => 'icon edit'));
            $url = new moodle_url('/mod/programming/testcase/delete.php', array('id' => $cm->id, 'case' => $case->id));
            $txt = html_writer::empty_tag('img', array('title' => $strdelete, 'src' => $OUTPUT->image_url('t/delete')));
            $act = new confirm_action(get_string('deletetestcaseconfirm', 'programming'));
            $actions[] = $OUTPUT->action_link($url, $txt, $act, array('class' => 'icon delete'));
            if ($i > 0) {
                $actions[] = $OUTPUT->action_link(
                    new moodle_url('move.php', array('id' => $cm->id, 'case' => $case->id, 'direction' => 1)),
                    html_writer::empty_tag('img', array('title' => $strmoveup, 'src' => $OUTPUT->image_url('t/up'))),
                    null,
                    array('class' => 'icon up'));
            }
            if ($i < $tests_count) {
                $actions[] = $OUTPUT->action_link(
                    new moodle_url('move.php', array('id' => $cm->id, 'case' => $case->id, 'direction' => 2)),
                    html_writer::empty_tag('img', array('title' => $strmovedown, 'src' => $OUTPUT->image_url('t/down'))),
                    null,
                    array('class' => 'icon down'));
            }
	    $data[] = implode("\n",$actions);

            $table->data[] = $data;
            $i++;
        }

        echo html_writer::table($table);
    } else {
        echo html_writer::tag('p'.get_string('notestcase', 'programming'));
    }
    echo html_writer::tag('p', $OUTPUT->action_link(new moodle_url('add.php', array('id' => $cm->id)), get_string('addtestcase', 'programming')));
}
