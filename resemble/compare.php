<?php

    require_once('../../../config.php');
    require_once('../lib.php');

    $id = required_param('id', PARAM_INT);     // programming ID
    $rid = optional_param('rid', 0, PARAM_INT); // resemble id
    $page = optional_param('page', 0, PARAM_INT);
    $perpage = optional_param('perpage', 0, PARAM_INT);
    
    $params = array('id' => $id, 'rid' => $rid, 'page' => $page, 'perpage' => $perpage);
    $PAGE->set_url('/mod/programming/resemble/compare.php', $params);

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

    $resemble = $DB->get_record('programming_resemble', array('id' => $rid));
    $submit1 = $DB->get_record('programming_submits', array('id' => $resemble->submitid1));
    $submit2 = $DB->get_record('programming_submits', array('id' => $resemble->submitid2));
    if ($submit1->userid == $USER->id || $submit2->userid == $USER->id) {
        require_capability('mod/programming:viewresemble', $context);
    } else {
        require_capability('mod/programming:editresemble', $context);
    }

    $user1 = $DB->get_record('user', array('id' => $submit1->userid));
    $user2 = $DB->get_record('user', array('id' => $submit2->userid));


    // Change matched lines into array, with an matched id as first element
    $lines1 = explode("\n", $submit1->code);
    $lines2 = explode("\n", $submit2->code);

    $matches = explode(';', $resemble->matchedlines);
    $mid = 1;
    foreach($matches as $range) {
        list($range1, $range2) = explode(',', $range);

        list($start, $end) = explode('-', $range1);
        while ($start <= $end) {
            if (array_key_exists($start, $lines1) &&
                !is_array($lines1[$start])) {
                $lines1[$start] = array($mid, $lines1[$start]);
            }
            $start++;
        }
        list($start, $end) = explode('-', $range2);
        while ($start <= $end) {
            if (array_key_exists($start, $lines2) &&
                !is_array($lines2[$start])) {
                $lines2[$start] = array($mid, $lines2[$start]);
            }
            $start++;
        }
        $mid++;
    }


	include_once('resemble_compare.tpl.php');

?>
