<?php

/// Print the page header
    $PAGE->set_title($programming->name);
    $PAGE->set_heading(format_string($course->fullname));
    echo $OUTPUT->header();

/// Print tabs
    $renderer = $PAGE->get_renderer('mod_programming');
    $tabs = programming_navtab('resemble', 'resemble-edit', $course, $programming, $cm);
    echo $renderer->render_navtab($tabs);
?>
<?php
    if (is_array($resemble) && count($resemble)):
    $pagingbar = new paging_bar($totalcount, $page, $perpage, $PAGE->url, 'page');
    echo $OUTPUT->render($pagingbar);
?>
<form action="edit.php" method="post" id="resemble_editform">
<input type="hidden" name="id" value="<?php echo $cm->id; ?>" />
<input type="hidden" name="page" value="<?php echo $page; ?>" />
<input type="hidden" name="perpage" value="<?php echo $perpage; ?>" />
<input type="hidden" name="action" value="" />

<table class="generaltable resemble-list">
<tbody>
  <tr>
    <th><input type="checkbox" onchange="$('input[@type=checkbox]').attr('checked', this.checked);"/></th>
    <th colspan="4"><?php echo get_string('program1', 'programming'); ?></th>
    <th><?php echo get_string('percent1', 'programming'); ?></th>
    <th colspan="4"><?php echo get_string('program2', 'programming'); ?></th>
    <th><?php echo get_string('percent2', 'programming'); ?></th>
    <th><?php echo get_string('matchedlines', 'programming'); ?></th>
  </tr>

<?php
    foreach ($resemble as $r):
        switch($r->flag) {
        case PROGRAMMING_RESEMBLE_WARNED:
            $styleclass = $styleclass1 = $styleclass2 = 'warned cell';
            break;
        case PROGRAMMING_RESEMBLE_CONFIRMED:
            $styleclass = $styleclass1 = $styleclass2 = 'confirmed cell';
            break;
        case PROGRAMMING_RESEMBLE_FLAG1:
            $styleclass = 'confirmed cell';
            $styleclass1 = 'confirmed cell';
            $styleclass2 = 'cell';
            break;
        case PROGRAMMING_RESEMBLE_FLAG2:
            $styleclass = 'confirmed cell';
            $styleclass1 = 'cell';
            $styleclass2 = 'confirmed cell';
            break;
        case PROGRAMMING_RESEMBLE_FLAG3:
            $styleclass = $styleclass1 = $styleclass2 = 'flag3 cell';
            break;
        default:
            $styleclass = $styleclass1 = $styleclass2 = 'cell';
      }
?>
  <tr>
	<td class="<?php echo $styleclass; ?>"><input type="checkbox" name="rids[]" value="<?php echo $r->id ?>" /></td>
	<td class="<?php echo $styleclass1; ?>">
        <?php echo $OUTPUT->user_picture($users[$r->userid1], array('courseid' => $course->id)); ?>
    </td>
    <td class="<?php echo $styleclass1; ?>">
        <?php echo $OUTPUT->action_link(new moodle_url('/user/view.php', array('id' => $r->userid1, 'course' => $course->id)), fullname($users[$r->userid1])); ?>
	</td>
	<td class="<?php echo $styleclass1 ?>">
      <?php echo $users[$r->userid1]->idnumber; ?>
    </td>
	<td class="<?php echo $styleclass1 ?>">
      <?php echo $OUTPUT->action_link(new moodle_url('/mod/programming/result.php', array('id' => $cm->id, 'submitid' => $r->submitid1)), $submits[$r->submitid1]->judgeresult); ?></a>
    </td>
	<td class="<?php echo $styleclass1; ?>"><?php echo $r->percent1; ?></td>
	<td class="<?php echo $styleclass2; ?>">
        <?php echo $OUTPUT->user_picture($users[$r->userid2], array('courseid' => $course->id)); ?>
    </td>
	<td class="<?php echo $styleclass2; ?>">
        <?php echo $OUTPUT->action_link(new moodle_url('/user/view.php', array('id' => $r->userid2, 'course' => $course->id)), fullname($users[$r->userid2])); ?>
    </td>
	<td class="<?php echo $styleclass2; ?>">
      <?php echo $users[$r->userid2]->idnumber; ?>
    </td>
	<td class="<?php echo $styleclass2; ?>">
      <?php echo $OUTPUT->action_link(new moodle_url('/mod/programming/result.php', array('id' => $cm->id, 'submitid' => $r->submitid2)), $submits[$r->submitid2]->judgeresult); ?></a>
    </td>
	<td class="<?php echo $styleclass2; ?>"><?php echo $r->percent2; ?></td>
	<td class="<?php echo $styleclass; ?>">
    <?php echo $OUTPUT->action_link(new moodle_url('/mod/programming/resemble/compare.php', array('id' => $cm->id, 'rid' => $r->id, 'page' => $page, 'perpage' => $perpage)), $r->matchedcount); ?>
    </td>
  </tr>
<?php endforeach; ?>
</tbody>
</table>

<div class="buttons">
    <input type="submit" name="highsimilitude" value="<?php echo get_string('highsimilitude', 'programming'); ?>" onclick="this.form.elements['action'].value = 'confirm'" />
    <input type="submit" name="mediumsimilitude" value="<?php echo get_string('mediumsimilitude', 'programming'); ?>" onclick="this.form.elements['action'].value = 'warn'"/>
    <input type="submit" name="lowsimilitude" value="<?php echo get_string('lowsimilitude', 'programming'); ?>" onclick="this.form.elements['action'].value = 'reset'"/>
    <input type="submit" name="flag1" value="<?php echo get_string('flag1', 'programming'); ?>" onclick="this.form.elements['action'].value = 'flag1'"/>
    <input type="submit" name="flag2" value="<?php echo get_string('flag2', 'programming'); ?>" onclick="this.form.elements['action'].value = 'flag2'"/>
    <input type="submit" name="flag3" value="<?php echo get_string('flag3', 'programming'); ?>" onclick="this.form.elements['action'].value = 'flag3'"/>
    <input type="submit" name="delete" value="<?php echo get_string('delete'); ?>" onclick="this.form.elements['action'].value = 'delete'"/>
</div>
</form>
<?php
    else:
        echo html_writer::tag('p', get_string('noresembleinfo', 'programming'));
    endif;

/// Finish the page
    echo $OUTPUT->footer($course);
