<?php

    require_once('../../config.php');
    require_once('lib.php');
    require_once('submit_form.php');

    $id = required_param('id', PARAM_INT);     // programming ID

    $cookiename = 'MDLPROGLANG_'.$CFG->sessioncookie;
    $default_language = 0;
    if (isset($_COOKIE[$cookiename])) {
        $default_language = $_COOKIE[$cookiename];
    }
    if (!isset($language)) $language = $default_language;

    $params = array('id' => $id);
    $PAGE->set_url('/mod/programming/submit.php', $params);
    $PAGE->requires->css('/mod/programming/codemirror/lib/codemirror.css');
 //   $PAGE->requires->css('/mod/programming/codemirror/doc/docs.css'); //把这个注释掉，CSS就不会错位了。
    $PAGE->requires->css('/mod/programming/codemirror/theme/eclipse.css');
//    $PAGE->requires->js('/mod/programming/codemirror/lib/codemirror.js');
//    $PAGE->requires->js('/mod/programming/codemirror/mode/clike/clike.js');
//    $PAGE->requires->js('/mod/programming/codemirror/mode/pascal/pascal.js');
//    $PAGE->requires->js('/mod/programming/codemirror/mode/python/python.js');
//    $PAGE->requires->js('/mod/programming/codemirror/mode/shell/shell.js');
/*    echo '
<script type="text/javascript" 
src=
"codemirror/lib/codemirror.js"
>
</script>
<script type="text/javascript" 
src=
"codemirror/mode/clike/clike.js"
>
</script>        
<script type="text/javascript" 
src=
"codemirror/mode/pascal/pascal.js"
>
</script>        
<script type="text/javascript" 
src=
"codemirror/mode/python/python.js"
>
</script>        
<script type="text/javascript" 
src=
"codemirror/mode/shell/shell.js"
>
</script>        
';//*/
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
    $PAGE->set_context($context);

    require_capability('mod/programming:submitprogram', $context);
    $submitatanytime = has_capability('mod/programming:submitatanytime', $context);

    $result = $DB->get_record('programming_result', array('programmingid' => $programming->id, 'userid' => $USER->id));
    $submitcount = is_object($result) ? $result->submitcount : 0;
    $time = time();
    $isearly = $time < $programming->timeopen;
    $islate = !$programming->allowlate && $time > $programming->timeclose;
    $istoomore = $programming->attempts != 0 && $submitcount > $programming->attempts;
    $allowpost = $submitatanytime || (!$isearly && !$islate && !$istoomore);

    // Check if user has passed the practice
    $haspassed = false;
    if ($submitcount > 0) {
        $latestsubmit = $DB->get_record('programming_submits', array('id' => $result->latestsubmitid));
        $haspassed = is_object($latestsubmit) && $latestsubmit->passed;
    }

    $mform = new submit_form();
    if ($mform->is_cancelled()) {
        redirect(new moodle_url('view.php', array('id' => $cm->id)));
    } else {
        if ($allowpost && $submit = $mform->get_data()) {
            $submits_count = $DB->count_records('programming_submits', array('programmingid' => $programming->id, 'userid' => $USER->id));
            if (!$submitatanytime && ($programming->attempts != 0 && $programming->attempts <= $submits_count)) {
                $error = get_string('submitfailednoattempts', 'programming');
                $submit = False;
            }

            if ($submit) {
                $submit->userid = $USER->id;
                $submit->programmingid = $programming->id;
                $code = $submit->code;
                if ($sourcefile = $mform->get_file_content('sourcefile')) {
                    $code = $sourcefile;
                }
                if ($programming->presetcode) {
                    $code = programming_submit_remove_preset($code);
                }
                $submit->code = trim($code);
                if($submit->language==6){
               //  $submit->code='import sys;import codecs;sys.stdout = codecs.getwriter("utf-8")(sys.stdout.detach())'. PHP_EOL.$submit->code;
                }
                if ($submit->code == '') {
                    $error = get_string('submitfailedemptycode', 'programming');
                    $submit = False;
                }

                if ($submit) {
                    unset($submit->id);
                    programming_submit_add_instance($programming, $submit);

                    // Send events
                    $ue = new stdClass();
                    $ue->userid = $USER->id;
                    $ue->programmingid = $programming->id;
                    $ue->language = $submit->language;
                    $ue->timemodified = $submit->timemodified;

                }
            }
        }
    }

/// Print the page header
    setcookie($cookiename, $language, time() + 3600 * 24 * 60, $CFG->sessioncookiepath);

    if (!empty($action) && is_object($submit)) {
        $PAGE->requires->css('/mod/programming/js/dp/SyntaxHighlighter.css');
        $PAGE->requires->js('/mod/programming/js/dp/shCore.js');
        $PAGE->requires->js('/mod/programming/js/dp/shBrushCSharp.js');
    }
    $PAGE->set_title($programming->name);
    $PAGE->set_heading(format_string($course->fullname));
    echo $OUTPUT->header();

/// Print tabs
    $renderer = $PAGE->get_renderer('mod_programming');
    $tabs = programming_navtab('submit', null, $course, $programming, $cm);
    echo $renderer->render_navtab($tabs);

/// Print the main part of the page
    echo html_writer::tag('h2', $programming->name);
    echo html_writer::tag('h3', get_string('submit', 'programming').$OUTPUT->help_icon('submit', 'programming'));

    if (is_object($submit)) {
        echo html_writer::tag('h1', get_string('submitsuccess', 'programming'));
        echo $OUTPUT->action_link(new moodle_url('result.php', array('id' => $cm->id)), get_string('viewresults', 'programming'));
    } else {
        print_submit();
//js code
    echo ' 
    <script src="codemirror/lib/codemirror.js"></script>
    <script src="codemirror/lib/util/loadmode.js"></script>
    <script src="codemirror/mode/clike/clike.js"></script>
    <script src="codemirror/mode/pascal/pascal.js"></script>
    <script src="codemirror/mode/python/python.js"></script>
    <script src="codemirror/mode/shell/shell.js"></script>
<script>
        CodeMirror.modeURL = "codemirror/mode/%N/%N.js";
        var editor = CodeMirror.fromTextArea(document.getElementById("id_code"), {
        lineNumbers: true,
        tabSize: 4,
        indentUnit: 4,
        indentWithTabs: true,
        theme:"eclipse",
        mode: "text/x-csrc"
      });
function change() {
   var arr = '.json_encode(programming_get_language_options_js($programming)).'
   editor.setOption("mode",arr[document.getElementById("id_language").value]);
   editor.setOption("theme", "eclipse");
   var mmode = arr[document.getElementById("id_language").value];
   mmode = mmode.replace("text/x-csharp","clike");
   mmode = mmode.replace("text/x-csrc","clike");
   mmode = mmode.replace("text/x-c++src","clike");
   mmode = mmode.replace("text/x-java","clike");
   mmode = mmode.replace("text/x-pascal","pascal");
   mmode = mmode.replace("text/x-python","python");
   mmode = mmode.replace("text/x-sh","shell");
   CodeMirror.autoLoadMode(editor, mmode);
}
</script>';
        
    }

/// Finish the page

    echo $OUTPUT->footer($course);


function print_submit() {
    global $PAGE,$DB, $OUTPUT, $cm, $programming, $mform;
    global $allowpost, $haspassed, $islate, $isearly;
    if ($allowpost) {
        if ($haspassed) {
            echo html_writer::start_tag('div', array('id' => 'submitagainconfirm'));
            echo html_writer::tag('p', get_string('youhavepassed', 'programming'));
            echo html_writer::empty_tag('input', array('type' => 'button', 'id' => 'submitagain', 'name' => 'submitagain', 'value' => get_string('submitagain', 'programming')));
            $PAGE->requires->js_init_call('M.mod_programming.init_submit');
            echo html_writer::end_tag('div');
        }

        echo html_writer::start_tag('div', array('id' => 'submit'));
        $mform->display();
        echo html_writer::end_tag('div');
    }
    
    if ($isearly) {
        echo html_writer::tag('p', get_string('programmingnotopen', 'programming'));
    }

    if ($islate) {
        echo html_writer::tag('p', get_string('timeexceed', 'programming'));
    }

}

