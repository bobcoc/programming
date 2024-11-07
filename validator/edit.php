<?php
    require_once('../../../config.php');
    require_once('../lib.php');
    require_once('form.php');


    $id = required_param('id', PARAM_INT);    // Course Module ID, or

    $params = array('id' => $id);
    $PAGE->set_url('/mod/programming/validator/edit.php', $params);

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

    $mform = new validator_form();
    if ($mform->is_cancelled()) {
        redirect(new moodle_url('list.php', array('id' => $cm->id)));

    } else if ($data = $mform->get_data()) {
        $data->id = $programming->id;
        $DB->update_record('programming', $data);


        redirect(new moodle_url('edit.php', array('id' => $cm->id)), get_string('validatormodified', 'programming'));
    } else {
        $data = $DB->get_record('programming', array('id' => $cm->instance));
        $data->id = $cm->id;
        $mform->set_data($data);

    }

/// Print the page header
    $PAGE->set_title(format_string($course->shortname).': '.$programming->name).': '.get_string('validator', 'programming');
    $PAGE->set_heading(format_string($course->fullname));
    echo $OUTPUT->header();

/// Print tabs
    $renderer = $PAGE->get_renderer('mod_programming');
    $tabs = programming_navtab('edittest', 'validator', $course, $programming, $cm);
    echo $renderer->render_navtab($tabs);

/// Print page content
    echo html_writer::tag('h2', $programming->name);
    echo html_writer::tag('h3', get_string('validator', 'programming').$OUTPUT->help_icon('validator', 'programming'));

    $mform->display();

/// Finish the page
    echo $OUTPUT->footer($course);
