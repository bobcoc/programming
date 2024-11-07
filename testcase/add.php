<?php

require_once('../../../config.php');
require_once($CFG->libdir . '/weblib.php');
require_once('../lib.php');
require_once('form.php');

$id = required_param('id', PARAM_INT);     // programming ID
$params = array('id' => $id);
$PAGE->set_url('/mod/programming/testcase/add.php', $params);

if (!$cm = get_coursemodule_from_id('programming', $id,0,false,MUST_EXIST)) {
    print_error('invalidcoursemodule');
}

if (!$course = $DB->get_record('course', array('id' => $cm->course))) {
    print_error('coursemisconf');
}

if (!$programming = $DB->get_record('programming', array('id' => $cm->instance))) {
    print_error('invalidprogrammingid', 'programming');
}

require_login($course->id, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/programming:edittestcase', $context);

$mform = new testcase_form();
if ($mform->is_cancelled()) {
    redirect(new moodle_url('list.php', array('id' => $cm->id)));
} else if ($data = $mform->get_data()) {
    unset($data->id);
    $data->programmingid = $programming->id;
    $data->seq = $DB->count_records('programming_tests', array('programmingid' => $programming->id), 'MAX(seq)') + 1;
    $infile = $mform->get_file_content('inputfile');
    if (empty($infile)) {
        $data->input = stripcslashes($data->input);
    } else {
        $data->input = $infile;
    }
    $outfile = $mform->get_file_content('outputfile');
    if (empty($outfile)) {
        $data->output = stripcslashes($data->output);
    } else {
        $data->output = $outfile;
    }
//    $data->input = str_replace("\r\n\r\n", "", $data->input);
 //   $data->output = str_replace("\r\n\r\n", "", $data->output);
  //  $data->input = str_replace("\r\n\n", "", $data->input);
   // $data->output = str_replace("\r\n\n", "", $data->output);
//    $data->input = str_replace(chr(13), "", $data->input);
 //   $data->output = str_replace(chr(13), "", $data->output);
  //  $data->input = str_replace("\n\n", "\n", $data->input);
   // $data->output = str_replace("\n\n", "\n", $data->output);
    $data->input = rtrim($data->input);
    $data->output = rtrim($data->output);
//    $data->input .= "\n";
 //   $data->output .= "\n";
    programming_test_add_instance($data);

    redirect(new moodle_url('list.php', array('id' => $cm->id)), get_string('testcasemodified', 'programming'), 0);
} else {
    /// Print the page header
    $PAGE->set_title(format_string($course->shortname) . ': ' . $programming->name) . ': ' . get_string('addtestcase', 'programming');
    $PAGE->set_heading(format_string($course->fullname));
    echo $OUTPUT->header();

    /// Print tabs
    $renderer = $PAGE->get_renderer('mod_programming');
    $tabs = programming_navtab('edittest', 'testcase', $course, $programming, $cm);
    echo $renderer->render_navtab($tabs);

    echo html_writer::tag('h2', $programming->name);
    echo html_writer::tag('h3', get_string('addtestcase', 'programming'));

    /// Print page content
    $mform->display();

    /// Finish the page
    echo $OUTPUT->footer($course);
}
