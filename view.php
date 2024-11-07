<?php

    require_once('../../config.php');
    require_once('lib.php');

    /**
     * 添加一个由 参数a=programing id 直接访问　programming 插件的方法。
     */
    $cid = optional_param('a', 0 ,PARAM_INT); // Programming id
    
    if(empty($cid)){
        $id = required_param('id', PARAM_INT);    // Course Module ID, or
        $params = array('id' => $id);
        if (! $cm = get_coursemodule_from_id('programming', $id)) {
            print_error('invalidcoursemodule');
        }
    } else {
        $params = array('course' => $cid);
        if(! $cm = get_coursemodule_from_instance('programming' , $cid ) ){
            print_error('invalidcoursemodule');
        }
    }

    $PAGE->set_url('/mod/programming/view.php', $params);

    if (! $course = $DB->get_record('course', array('id' => $cm->course))) {
        print_error('coursemisconf');
    }

    if (! $programming = $DB->get_record('programming', array('id' => $cm->instance))) {
        print_error('invalidprogrammingid', 'programming');
    }

    require_login($course->id, true, $cm);

    $context = context_module::instance($cm->id);

    if (!$cm->visible) {
        require_capability('moodle/course:viewhiddenactivities', $context);
    }

    require_capability('mod/programming:viewcontent', $context);


/// Print the page header
    $PAGE->set_title($programming->name);
    $PAGE->set_heading(format_string($course->fullname));
    $PAGE->requires->css('/mod/programming/js/dp/SyntaxHighlighter.css');
    $PAGE->requires->js('/mod/programming/js/dp/shCore.js');
    $PAGE->requires->js('/mod/programming/js/dp/shBrushCSharp.js');
    echo $OUTPUT->header();

/// Print tabs
    $renderer = $PAGE->get_renderer('mod_programming');
    $tabs = programming_navtab('view', null, $course, $programming, $cm);
    echo $renderer->render_navtab($tabs);

/// Print the main part of the page
    $fields = 'id,programmingid,seq,input,output,cmdargs,timelimit,memlimit,nproc,pub,weight,memo,timemodified';
    $pubtests = $DB->get_records('programming_tests', array('programmingid' => $programming->id, 'pub' => 1), 'seq',$fields);
    $presetcodes = $DB->get_records('programming_presetcode', array('programmingid' => $programming->id), 'sequence');
    $datafiles = $DB->get_records('programming_datafile', array('programmingid' => $programming->id), 'seq', 'id, programmingid, filename, isbinary, datasize, checkdatasize');

    $notlate = $programming->allowlate || time() <= $programming->timeclose;

    print_content();

/// Finish the page
    echo $OUTPUT->footer($course);

function print_content() {
    global $CFG, $OUTPUT, $cm, $programming, $context;
    global $datafiles, $presetcodes, $viewpubtestcase, $pubtests;

    echo html_writer::tag('h2', $programming->name);

    if ($programming->showmode == PROGRAMMING_SHOWMODE_NORMAL) {
        echo $OUTPUT->box_start('time-table');

        $table = new html_table();
        $table->data = array();

        $table->data[] = array(get_string('grade','mod_programming'), $programming->grade, get_string('timeopen', 'programming'), userdate($programming->timeopen));
        $table->data[] = array(get_string('discount', 'programming'), $programming->discount/10.0, get_string('timediscount', 'programming'), userdate($programming->timediscount));
        $table->data[] = array(get_string('allowlate', 'programming'), get_string($programming->allowlate ? 'yes' : 'no'), get_string('timeclose', 'programming'), userdate($programming->timeclose));
        if($programming->inputfile) {
           $table->data[] = array(get_string('inputfile', 'programming'), '<font color=red>'.$programming->inputfile.'</font>', get_string('outputfile', 'programming'), '<font color=red>'.$programming->outputfile.'</font>');
        }
        echo html_writer::table($table);

        echo $OUTPUT->box_end();
    } else {
        echo html_writer::start_tag('span', array('class' => 'limit'));
        echo get_string('timelimit', 'programming');
        echo programming_format_timelimit($programming->timelimit);
        echo '&nbsp;';
        echo get_string('memlimit', 'programming');
        echo programming_format_memlimit($programming->memlimit);
        echo html_writer::end_tag('span');
    }

    echo $OUTPUT->box_start('description');

    echo $OUTPUT->box_start('intro');
    echo format_module_intro('programming', $programming, $cm->id);
    echo $OUTPUT->box_end();
    
    if (is_array($datafiles) && !empty($datafiles)) {
        echo $OUTPUT->box_start('datafile');
        echo html_writer::tag('h3', get_string('datafile', 'programming'));
        echo html_writer::start_tag('ul');
        foreach ($datafiles as $datafile) {
            $url = new moodle_url('/mod/programming/datafile/download.php', array('id' => $cm->id, 'datafile' => $datafile->id));
            echo html_writer::tag('li', $OUTPUT->action_link($url, $datafile->filename, null, array('title' => get_string('presstodownload', 'programming'))));
        }
        echo html_writer::end_tag('ul');
        echo $OUTPUT->box_end();
    }

    if (is_array($presetcodes) && !empty($presetcodes)) {
        echo $OUTPUT->box_start('presetcode');
        echo html_writer::tag('h3', get_string('presetcode', 'programming'));
        foreach ($presetcodes as $pcode) {
            echo html_writer::tag('h4', programming_get_presetcode_name($pcode));
            echo html_writer::tag('textarea', htmlspecialchars(programming_format_presetcode($pcode)), array('rows' => 20, 'cols' => 60, 'name' => 'code', 'class' => 'c#', 'id' => 'code'));
        }
        echo $OUTPUT->box_end();
    }

    $viewpubtestcase = has_capability('mod/programming:viewpubtestcase', $context);
    if ($viewpubtestcase && $programming->showmode == PROGRAMMING_SHOWMODE_NORMAL && count($pubtests) > 0) {
        $strshowasplaintext = get_string('showasplaintext', 'programming');

        echo $OUTPUT->box_start('testcase-table');
        $table = new html_table();
        $table->head = array(
            '&nbsp;',
            get_string('input', 'programming').$OUTPUT->help_icon('input', 'programming'),
            get_string('expectedoutput', 'programming').$OUTPUT->help_icon('expectedoutput', 'programming'),
            get_string('timelimit', 'programming').$OUTPUT->help_icon('timelimit', 'programming'),
            get_string('memlimit', 'programming').$OUTPUT->help_icon('memlimit', 'programming'),
	        get_string('extraproc', 'programming').$OUTPUT->help_icon('nproc', 'programming'));
        $table->colclasses[1] = 'programming-io';
        $table->colclasses[2] = 'programming-io';

        $table->data = array();
        $i = 0; $ioid = 0;
        foreach ($pubtests as $programmingtest) {
            $row = array();
            $row[] = get_string('testcasen', 'programming', $programmingtest->seq);

            $url = new moodle_url($CFG->wwwroot.'/mod/programming/testcase/download_io.php', array('id' => $cm->id, 'test' => $programmingtest->id, 'type' => 'in', 'download' => 0));
            $action = new popup_action('click', $url, '_blank', array('height' => 300, 'width' => 400));
            $html = $OUTPUT->action_link($url, $strshowasplaintext, $action, array('class' => 'showasplaintext small'));
	        $html.= programming_format_io($programmingtest->input, true);
            $row[] = $html;

            $url->param('type', 'out');
            $action = new popup_action('click', $url, '_blank', array('height' => 300, 'width' => 400));
            $html = $OUTPUT->action_link($url, $strshowasplaintext, $action, array('class' => 'showasplaintext small'));
            $html.= programming_format_io($programmingtest->output, true);
            $row[] = $html;
	        $row[] = programming_format_timelimit($programmingtest->timelimit);
            $row[] = programming_format_memlimit($programmingtest->memlimit);
            $row[] = $programmingtest->nproc;

            $table->data[] = $row;
        }

        echo html_writer::table($table);        
        echo $OUTPUT->box_end();
    }

    echo $OUTPUT->box_end(); // description
        
}
