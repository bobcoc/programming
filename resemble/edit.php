<?php

    require_once('../../../config.php');
    require_once('../lib.php');

    $id = required_param('id', PARAM_INT);
    $action = optional_param('action', 'list', PARAM_CLEAN);
    $page = optional_param('page', 0, PARAM_INT);
    $perpage = optional_param('perpage', 10, PARAM_INT);
    $format = optional_param('format', 'html', PARAM_CLEAN);
    $rids = optional_param_array('rids', array(), PARAM_INT);

    $params = array('id' => $id, 'action' => $action, 'page' => $page, 'perpage' => $perpage, 'format' => $format);
    $PAGE->set_url('/mod/programming/resemble/edit.php', $params);

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

    $successurl = new moodle_url('/mod/programming/resemble/edit.php', array('id' => $cm->id, 'page' => $page, 'perpage' => $perpage));
    switch($action) {
    case 'list':
        require_capability('mod/programming:editresemble', $context);
        $offset = $page * $perpage;
        $sql = "SELECT re.*, ua.id as userid1, ub.id as userid2
                  FROM {programming_resemble} AS re,
                       {programming_submits} AS sa,
                       {programming_submits} AS sb,
                       {user} AS ua,
                       {user} AS ub
                 WHERE re.programmingid={$programming->id}
                   AND re.flag>=0
                   AND re.submitid1 = sa.id
                   AND re.submitid2 = sb.id
                   AND sa.userid = ua.id
                   AND sb.userid = ub.id
              ORDER BY id
                 LIMIT $offset, $perpage";
        $resemble = $DB->get_records_sql($sql);
        if (!is_array($resemble)) $resemble = array();
        $uids = array(); $sids = array();
        foreach($resemble as $r) {
            $uids[] = $r->userid1;
            $uids[] = $r->userid2;
            $sids[] = $r->submitid1;
            $sids[] = $r->submitid2;
        }
        if (!empty($uids)) {
            $users = $DB->get_records_select('user', 'id IN ('.implode($uids, ',').')');
        }
        if (!empty($sids)) {
            $submits = $DB->get_records_select('programming_submits', 'id IN ('.implode($sids, ',').')');
        }
        $totalcount = $DB->count_records_select('programming_resemble', 'programmingid='.$programming->id.' AND flag>=0');

        /// Print page content
        if ($format == 'json') {
            require_once('../lib/JSON.php');
            $data = array(array_keys($resemble), array_values($resemble), array_keys($users), array_values($users));
            $json = new Services_JSON();
            echo $json->encode($data);
        } else {
            include_once('resemble_edit.tpl.php');
        }

        break;

    case 'confirm':
        require_capability('mod/programming:editresemble', $context);
        $select = 'id in ('.join(',', $rids).')';
        $sql = $DB->set_field_select('programming_resemble', 'flag', PROGRAMMING_RESEMBLE_CONFIRMED, $select);
        redirect($successurl, get_string('resembleeditsucceeded', 'programming'), 2);
        break;

    case 'warn':
        require_capability('mod/programming:editresemble', $context);
        $select = 'id in ('.join(',', $rids).')';
        $sql = $DB->set_field_select('programming_resemble', 'flag', PROGRAMMING_RESEMBLE_WARNED, $select);
        redirect($successurl, get_string('resembleeditsucceeded', 'programming'), 2);
        break;

    case 'reset':
        require_capability('mod/programming:editresemble', $context);
        $select = 'id in ('.join(',', $rids).')';
        $sql = $DB->set_field_select('programming_resemble', 'flag', PROGRAMMING_RESEMBLE_NEW, $select);
        redirect($successurl, get_string('resembleeditsucceeded', 'programming'), 2);
        break;

    case 'flag1':
        require_capability('mod/programming:editresemble', $context);
        $select = 'id in ('.join(',', $rids).')';
        $sql = $DB->set_field_select('programming_resemble', 'flag', PROGRAMMING_RESEMBLE_FLAG1, $select);
        redirect($successurl, get_string('resembleeditsucceeded', 'programming'), 2);
        break;

    case 'flag2':
        require_capability('mod/programming:editresemble', $context);
        $select = 'id in ('.join(',', $rids).')';
        $sql = $DB->set_field_select('programming_resemble', 'flag', PROGRAMMING_RESEMBLE_FLAG2, $select);
        redirect($successurl, get_string('resembleeditsucceeded', 'programming'), 2);
        break;

    case 'flag3':
        require_capability('mod/programming:editresemble', $context);
        $select = 'id in ('.join(',', $rids).')';
        $sql = $DB->set_field_select('programming_resemble', 'flag', PROGRAMMING_RESEMBLE_FLAG3, $select);
        redirect($successurl, get_string('resembleeditsucceeded', 'programming'), 2);
        break;

    case 'delete':
        require_capability('mod/programming:editresemble', $context);
        $select = 'id in ('.join(',', $rids).')';
        $sql = $DB->set_field_select('programming_resemble', 'flag', PROGRAMMING_RESEMBLE_DELETED, $select);
        redirect($successurl, get_string('resembleeditsucceeded', 'programming'), 2);
        break;
    }

?>
