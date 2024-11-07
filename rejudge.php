<?PHP

    require_once('../../config.php');
    require_once('lib.php');

    $id = required_param('id', PARAM_INT);
    $groupid = optional_param('groupid', 0, PARAM_INT);
    $submitid = optional_param_array('submitid', array(), PARAM_INT);
    $confirm = optional_param('confirm', 0, PARAM_INT);
    $href = optional_param('href', $_SERVER['HTTP_REFERER'], PARAM_URL);
    $ac = optional_param('ac', 0, PARAM_INT);

    $PAGE->set_url('/mod/programming/rejudge.php');

    if ($id) {
        if (! $cm = get_coursemodule_from_id('programming', $id)) {
            print_error('Course Module ID was incorrect');
        }
    
        if (! $course = $DB->get_record('course', array('id' => $cm->course))) {
            print_error('Course is misconfigured');
        }
    
        if (! $programming = $DB->get_record('programming', array('id' => $cm->instance))) {
            print_error('Course module is incorrect');
        }
    }
    require_login($course->id, true, $cm);
    $context = context_module::instance($cm->id);

    require_capability('mod/programming:rejudge', $context);

/// Print the page header
    $PAGE->set_title($programming->name);
    $PAGE->set_heading(format_string($course->fullname));
    $PAGE->requires->css('/mod/programming/styles.css');
    echo $OUTPUT->header();

/// Print the main part of the page

    if (!empty($submitid) || $confirm) {
        programming_rejudge($programming, $submitid, $groupid, $ac);
        echo html_writer::tag('h2', get_string('rejudgestarted', 'programming'));
        echo html_writer::tag('p', $OUTPUT->action_link(new moodle_url($href), get_string('continue')));
    } else {
        echo html_writer::start_tag('div', array('class' => 'noticebox'));
        echo html_writer::tag('h2', get_string('rejudgeprograms', 'programming', $programming));
        echo html_writer::start_tag('form', array('name' => 'form', 'method' => 'post'));
        echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'id', 'value' => $id));
        echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'confirm', 'value' => 1));
        echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'href', 'value' => $_SERVER['HTTP_REFERER']));
        echo html_writer::tag('input', get_string('rejudgeac', 'programming'), array('type' => 'checkbox', 'name' => 'ac', 'value' => 1));
        echo html_writer::start_tag('div', array('class' => 'buttons'));
        echo html_writer::empty_tag('input', array('type' => 'submit', 'value' => get_string('yes')));
        echo html_writer::empty_tag('input', array('type' => 'button', 'value' => get_string('no'), 'onclick' => 'javascript:history.go(-1);'));
        echo html_writer::end_tag('div');
        echo html_writer::end_tag('form');
        echo html_writer::end_tag('div');
    }

/// Finish the page
    echo $OUTPUT->footer($course);

?>
