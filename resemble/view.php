<?php

    require_once('../../../config.php');
    require_once('../lib.php');

    $id = required_param('id', PARAM_INT);     // programming ID
    $format = optional_param('format', 'html', PARAM_CLEAN);

    $params = array('id' => $id, 'format' => $format);
    $PAGE->set_url('/mod/programming/resemble/view.php', $params);

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

    require_capability('mod/programming:viewresemble', $context);

    $sql = "SELECT re.*, sa.userid AS userid1, sb.userid AS userid2
              FROM {programming_resemble} AS re,
                   {programming_submits} AS sa,
                   {programming_submits} AS sb
             WHERE re.programmingid={$programming->id}
               AND re.flag > 0
               AND sa.programmingid={$programming->id}
               AND sb.programmingid={$programming->id}
               AND re.submitid1 = sa.id
               AND re.submitid2 = sb.id
               AND (sa.userid = $USER->id OR sb.userid = $USER->id)
          ORDER BY re.id";
    $resemble = $DB->get_records_sql($sql);
    if (!is_array($resemble)) $resemble = array();

    $uids = array();
    foreach($resemble as $r) {
        $uids[] = $r->userid1;
        $uids[] = $r->userid2;
    }
    if (!empty($uids)) {
        $users = $DB->get_records_select('user', 'id IN ('.implode($uids, ',').')');
    }

    /// Print page content
    if ($format == 'json') {
        require_once('../lib/JSON.php');
        $data = array(array_keys($resemble), array_values($resemble), array_keys($users), array_values($users));
        $json = new Services_JSON();
        echo $json->encode($data);
    } else {

    /// Print the page header
        $PAGE->set_title($programming->name);
        $PAGE->set_heading(format_string($course->fullname));
        echo $OUTPUT->header();

    /// Print tabs
        $renderer = $PAGE->get_renderer('mod_programming');
        $tabs = programming_navtab('resemble', 'resemble-view', $course, $programming, $cm);
        echo $renderer->render_navtab($tabs);

        if (is_array($resemble) && count($resemble)) {
            $mediumdegree = get_string('mediumsimilitude', 'programming');
            $highdegree = get_string('highsimilitude', 'programming');

            echo $OUTPUT->box_start('resemble-list');

            // resemble-list
            $table = new html_table();
            $table->head = array(
                get_string('similitudedegree', 'programming'),
                get_string('program1', 'programming'),
                get_string('percent1', 'programming'),
                get_string('program2', 'programming'),
                get_string('percent2', 'programming'),
                get_string('matchedlines', 'programming')
            );
            $table->data = array();

            foreach ($resemble as $r) {
                switch($r->flag) {
                case PROGRAMMING_RESEMBLE_WARNED:
                    $styleclass = $styleclass1 = $styleclass2 = 'warned';
                    $degree = $mediumdegree;
                    break;
                case PROGRAMMING_RESEMBLE_CONFIRMED:
                    $styleclass = $styleclass1 = $styleclass2 = 'confirmed';
                    $degree = $highdegree;
                    break;
                case PROGRAMMING_RESEMBLE_FLAG1:
                    $styleclass = 'confirmed';
                    $styleclass1 = 'confirmed';
                    $styleclass2 = '';
                    $degree = $highdegree;
                    break;
                case PROGRAMMING_RESEMBLE_FLAG2:
                    $styleclass = 'confirmed';
                    $styleclass1 = '';
                    $styleclass2 = 'confirmed';
                    $degree = $highdegree;
                    break;
                case PROGRAMMING_RESEMBLE_FLAG3:
                    $styleclass = $styleclass1 = $styleclass2 = 'flag3';
                    $degree = $highdegree;
                    break;
                default:
                    $styleclass = '';
                }

                $url1 = new moodle_url('/user/view.php', array('id' => $r->userid1, 'course' => $course->id));
                $fullname1 = fullname($users[$r->userid1]);
                $url2 = new moodle_url('/user/view.php', array('id' => $r->userid2, 'course' => $course->id));
                $fullname2 = fullname($users[$r->userid2]);
                $urlcmp = new moodle_url('/mod/programming/resemble/compare.php', array('id' => $cm->id, 'rid' => $r->id));
                $table->data[] = array(
                    html_writer::tag('span', $degree, array('class' => $styleclass)),
                    html_writer::tag('span', $OUTPUT->user_picture($users[$r->userid1]).$OUTPUT->action_link($url1, $fullname1, null, array('title' => $fullname1)), array('class' => $styleclass1)),
                    html_writer::tag('span', $r->percent1, array('class' => $styleclass1)),
                    html_writer::tag('span', $OUTPUT->user_picture($users[$r->userid2]).$OUTPUT->action_link($url2, $fullname2, null, array('title' => $fullname2)), array('class' => $styleclass2)),
                    html_writer::tag('span', $r->percent2, array('class' => $styleclass2)),
                    html_writer::tag('span', $OUTPUT->action_link($urlcmp, $r->matchedcount), array('class' => $styleclass)) );
            }

            echo html_writer::table($table);
            echo $OUTPUT->box_end();

        } else {
            echo html_writer::tag('p', get_string('noresembleinfo', 'programming'));
        }

    /// Finish the page
        echo $OUTPUT->footer($course);
    }
