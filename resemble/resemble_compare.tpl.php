<?php

/// Print the page header
    $PAGE->set_title($programming->name);
    $PAGE->set_heading(format_string($course->fullname));
    echo $OUTPUT->header();

/// Print tabs
    $renderer = $PAGE->get_renderer('mod_programming');
    $tabs = programming_navtab('resemble', 'resemble-compare', $course, $programming, $cm);
    echo $renderer->render_navtab($tabs);

/// Print page content
?>

<table class="generaltable resemble-list">
  <tr>
    <td class="cell">
        <?php echo $OUTPUT->user_picture($user1, array('courseid' => $course->id)); ?>
        <a href="<?php echo $CFG->wwwroot; ?>/user/view.php?id=<?php echo $user1->id; ?>&amp;course=<?php echo $course->id; ?>"><?php echo fullname($user1); ?></a>
    </td>
    <td class="cell"><?php echo userdate($submit1->timemodified); ?></td>
    <td class="cell">
        <?php echo $OUTPUT->user_picture($user2, array('courseid' => $course->id)); ?>
        <a href="<?php echo $CFG->wwwroot; ?>/user/view.php?id=<?php echo $user2->id; ?>&amp;course=<?php echo $course->id; ?>"><?php echo fullname($user2); ?></a>
    </td>
    <td class="cell"><?php echo userdate($submit2->timemodified); ?></td>
  </tr>
</table>

<div class="resemble-compare-programs">
    <div id="submit1">
    <?php
        foreach ($lines1 as $line) {
            if (is_array($line)) {
                $mid = $line[0];
                $line = $line[1];
                echo '<span class="code match'.$mid.'">';
            } else {
                echo '<span class="code">';
            }
            $line = htmlspecialchars($line);
            $line = str_replace(array(' ', "\r"), array('&nbsp;', ''), $line);
            echo $line;
            echo '</span><br />'."\n";
        }
    ?>
    </div>

    <div id="submit2">
    <?php
        foreach ($lines2 as $line) {
            if (is_array($line)) {
                $mid = $line[0];
                $line = $line[1];
                echo '<span class="code match'.$mid.'">';
            } else {
                echo '<span class="code">';
            }
            $line = htmlspecialchars($line);
            $line = str_replace(array(' ', "\r"), array('&nbsp;', ''), $line);
            echo $line;
            echo '</span><br />'."\n";
        }
    ?>
    </div>
</div>

<?php if (has_capability('mod/programming:editresemble', $context)): ?>
<form action="edit.php" method="post" id="resemble_editform">
<input type="hidden" name="id" value="<?php echo $cm->id; ?>" />
<input type="hidden" name="page" value="<?php echo $page; ?>" />
<?php if (isset($perpage)): ?>
<input type="hidden" name="perpage" value="<?php echo $perpage; ?>" />
<?php endif; ?>
<input type="hidden" name="action" value="" />
<input type="hidden" name="rids[]" value="<?php echo $resemble->id; ?>" />

<div class="buttons">
    <input type="submit" name="highsimilitude" value="<?php echo get_string('highsimilitude', 'programming'); ?>" onclick="this.form.elements['action'].value = 'confirm'" />
    <input type="submit" name="mediumsimilitude" value="<?php echo get_string('mediumsimilitude', 'programming'); ?>" onclick="this.form.elements['action'].value = 'warn'"/>
    <input type="submit" name="lowsimilitude" value="<?php echo get_string('lowsimilitude', 'programming'); ?>" onclick="this.form.elements['action'].value = 'reset'"/>
    <input type="submit" name="delete" value="<?php echo get_string('delete'); ?>" onclick="this.form.elements['action'].value = 'delete'"/>
</div>
</form>
<?php endif; ?>
<?php
/// Finish the page
    echo $OUTPUT->footer($course);
?>
