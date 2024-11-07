<?php
    $PAGE->requires->css = '/mod/programming/styles.css';

    if ($course->category) {
        $navigation = "<a href='/course/view.php?id=$course->id'>$course->shortname</a> ->";
    }

    $strprogrammings = get_string('modulenameplural', 'programming');
    $strprogramming  = get_string('modulename', 'programming');

    $meta = '';
    foreach ($CFG->scripts as $script) {
        //$meta .= '<script type="text/javascript" src="'.$script.'"></script>';
        //$meta .= "\n";
        $PAGE->requires->js($script);
    }

    if (isset($cm)) {
        $navigation = build_navigation($pagename, $cm);
    } else {
        $navigation = build_navigation($strprogrammings);
    }

   print_header(
        empty($programming) ? $strprogrammings.' '.$title : $course->shortname.': '.$programming->name,
        $course->fullname,
        $navigation,
        '', // focus
        '',
        true,
        !empty($cm) ? update_module_button($cm->id, $course->id, $strprogramming) : '', 
        !empty($cm) ? navmenu($course, $cm) : navmenu($course));
   //  */
?>
