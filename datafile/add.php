<?php

    require_once('../../../config.php');
    require_once($CFG->libdir.'/weblib.php');
    require_once('../lib.php');
    require_once('form.php');

    $id = required_param('id', PARAM_INT);     // programming ID
    $params = array('id' => $id);
    $PAGE->set_url('/mod/programming/datafile/list.php', $params);

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

    $mform = new datafile_form();
    if ($mform->is_cancelled()) {
        redirect(new moodle_url('list.php', array('id' => $cm->id)));

    } else if ($data = $mform->get_data()) {
        unset($data->id);
        $data->programmingid = $programming->id;

        $data->seq = $DB->count_records('programming_datafile', array('programmingid' => $programming->id), 'MAX(seq)') + 1;

        $content = $mform->get_file_content('data');
        $data->data = bzcompress($content);
        $data->datasize = strlen($content);

        if (!empty($data->usecheckdata)) {
            $content = $mform->get_file_content('checkdata');
            $data->checkdata = bzcompress($content);
            $data->checkdatasize = strlen($content);
        }

        $data->timemodified = time();
        $DB->insert_record('programming_datafile', $data);
        programming_datafile_adjust_sequence($programming->id);

        redirect(new moodle_url('list.php', array('id' => $cm->id)), get_string('datafileadded', 'programming'));

    } else {
        /// Print the page header
        $PAGE->set_title($programming->name);
        $PAGE->set_heading(format_string($course->fullname));
        echo $OUTPUT->header();

        /// Print tabs
        $renderer = $PAGE->get_renderer('mod_programming');
        $tabs = programming_navtab('edittest', 'datafile', $course, $programming, $cm);
        echo $renderer->render_navtab($tabs);

        /// Print page content
        echo html_writer::tag('h2', $programming->name);
        echo html_writer::tag('h3', get_string('adddatafile', 'programming').$OUTPUT->help_icon('datafile', 'programming'));
        $mform->display();

        /// Finish the page
        echo $OUTPUT->footer($course);
    }
