<?PHP

// $Id: view.php,v 1.1 2003/09/30 02:45:19 moodler Exp $
/// This page prints a particular instance of programming
/// (Replace programming with the name of your module)

require_once('../../config.php');
require_once('lib.php');

$submitid = optional_param('submitid', 0, PARAM_INT);
$a = optional_param('a', 0, PARAM_INT);
$id = required_param('id', PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_INT);
$href = optional_param('href', $_SERVER['HTTP_REFERER'], PARAM_URL);

if ($a) {
    if (!$cm = get_coursemodule_from_instance('programming', $a)) {
        print_error('invalidcoursemodule');
    }
} else {
    if (!$cm = get_coursemodule_from_id('programming', $id)) {
        print_error('invalidcoursemodule');
    }
}

if (!$course = $DB->get_record('course', array('id' => $cm->course))) {
    print_error('coursemisconf');
}

if (!$programming = $DB->get_record('programming', array('id' => $cm->instance))) {
    print_error('invalidprogrammingid', 'programming');
}
$context = context_module::instance($cm->id);

require_login($course->id, true, $cm);
require_capability('mod/programming:deleteothersubmit', $context);

/// Print the page header
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


/// Print the main part of the page
if ($confirm) {
    foreach ($submitid as $sid) {
        $submit = $DB->get_record('programming_submits', array('id' => $sid));
        if ($submit)
            programming_delete_submit($submit);
    }

    echo '<div class="maincontent generalbox">';
    echo '<p align="center">' . get_string('deleted') . '</p>';
    echo '<p align="center"><a href="' . $href . '">' . get_string('continue') . '</a></p>';
    echo '</div>';
} else {
    echo '<table align="center" width="60%" class="noticebox" border="0" cellpadding="20" cellspacing="0">';
    echo '<tr><td bgcolor="#FFAAAA" class="noticeboxcontent">';
    echo '<h2 class="main">' . get_string('deletesubmitconfirm', 'programming') . '</h2>';
    echo '<ul>';
    foreach ($submitid as $sid) {
        $submit = $DB->get_record('programming_submits', array('id' => $sid));
        $tm = userdate($submit->timemodified);
        $user = fullname($DB->get_record('user', array('id' => $submit->userid)));
        echo "<li>$sid $user $tm</li>";
    }
    echo '</ul>';
    echo '<form name="form" method="post">';
    foreach ($submitid as $sid) {
        echo "<input type='hidden' name='submitid[]' value='$sid' />";
    }
    echo "<input type='hidden' name='a' value='$a' />";
    echo "<input type='hidden' name='id' value='$id' />";
    echo '<input type="hidden" name="confirm" value="1" />';
    echo "<input type='hidden' name='href' value='$href' />";
    echo '<input type="submit" value=" ' . get_string('yes') . ' " /> ';
    echo '<input type="button" value=" ' . get_string('no') . ' " onclick="javascript:history.go(-1);" />';

    echo '</form>';
    echo '</td></tr></table>';
}

/// Finish the page
$OUTPUT->footer($course);
?>
