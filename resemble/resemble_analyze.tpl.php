<br />
<form id="resemble_analyze_form" action="resemble_analyze_cmd.php" target="cmd">
  <input type="hidden" name="id" value="<?php echo $cm->id ?>" />
  <input type="hidden" name="action" value="fetchresult" />
  <span>
    <label for="max">max</label>
    <input type="text" name="max" size="10" value="250"/>
  </span>
  <span>
    <label for="lowest">lowest</label>
    <input type="text" name="lowest" size="10" value="30"/>
  </span>
  <input id="begin_analyze" type="submit" value="begin" />
</form>
<iframe name="cmd" width="640" height="480"></iframe>
