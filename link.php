<?PHP

    require_once('../../config.php');
    require_once 'link_lib.php';
                    
    $id = required_param('id', PARAM_INT);   // course
    if (!$course = $DB->get_record('course', array('id'=>$id))) {
        print_error('Course ID is incorrect');
    }
    require_login($course);
    
    $PAGE->set_url('/mod/programming/link.php', array('id' => $id));

    //权限控制
    // 只允许管理员，该课程 教师 才能有权限 操作
    check_is_teacher($course);
    
    $strprogrammings = '引用编程插件管理';
    
    $PAGE->set_title($strprogrammings);
    $PAGE->set_heading($strprogrammings);
    $PAGE->set_pagelayout('incourse');
    $PAGE->set_context(context_course::instance($id));
    $PAGE->navbar->add($strprogrammings);

    $PAGE->requires->css('/mod/programming/styles.css');
    $PAGE->requires->js('/mod/programming/js/jquery-1.3.1.js');
    $PAGE->requires->js('/mod/programming/js/link.js');

    echo $OUTPUT->header();  
   
    
?>
<table width="300" height="169" border="0" align="left" cellpadding="0" cellspacing="0" style="margin:15px 0px 0px 15px;">
    <tr>
        <td colspan="4">
            课程列表：
            <select name="ccourse" id="ccourse">
            <?php
            link_course($id); 
            ?>
            </select>
        </td>
    </tr>
  <tr>
      <td width="126" align="center">
       <p>章节</p>
		<select rows="100" name="section" style="width:120px;height: 418px;" size="20" class="td3" id="section">
                   <?php
                       link_get_course_section($id);
                       echo "<input type=\"hidden\" name=\"course\" id=\"courseid\" value=\"$_REQUEST[id]\" />";
                   ?>
		</select>           <br/><br/>       <br/><br/>
	</td>
        
        <td width="126" align="center">
            <p>引用的编程练习</p>
		<select rows="100" name="first" style="width:250px;height: 385px;" size="10" multiple="multiple" class="td3" id="first">
                   <?php
                       link_get_detail($id);
                       echo "<input type=\"hidden\" name=\"course\" id=\"courseid\" value=\"$_REQUEST[id]\" />";
                   ?>
		</select>
                <div style="width:250px;">
                    <a href="javascript:void(0);" id="next_link">..下<?php echo $pageSize ?>条</a>
                    <input type="hidden" name="page_link" id="page_link" value="1" />        <br/><br/> 
                </div>
        <input type="button" id="viewall" value="显示全部" /> &nbsp;&nbsp;<input type="button" id="update_btn" value="更新课程缓存" />
        <br/><br/>
	</td>
    <td width="69" valign="middle">
        
       <input name="add"  id="add" type="button" class="button" value="-->" /> 
       <br/>
       <br/>
       <input name="remove"  id="remove" type="button" class="button" value="&lt;--" />
        </td>
    <td width="127" align="center">
        <p>所有编程练习</p>
	  <select  name="second" size="10" style="width:250px;height: 385px;"  multiple="multiple" class="td3" id="second">
         <?php
                       link_get_not_in_detail($id);
         ?>
      </select>
        <div style="width:250px;">
            <a href="javascript:void(0);" id="next_pro">..下<?php echo $pageSize ?>条</a>
            <input type="hidden" name="page_all" id="page_all" value="1" />
            <input type="hidden" name="keyword" id="keyword" value="" />
        </div>
        <select name="scourse" id="scourse" style="width:248px;">
            <option value="0" selected="true">请选择要搜索的课程</option>
            <?php
            //可以引用其他课程的编程练习实例 不受权限影响
                search_link_course($id); 
            ?>
        </select>
        <br/>
        <input type="text" name="search" id="search_txt" size="15" />
        <input type="button" id="search_btn" value="搜索" /> &nbsp;&nbsp;
        <input type="button" id="clear_btn" value="清空" />
        <br/>
	</td>
  </tr>
  <tr>
        <td colspan="4">
           ** &nbsp;&nbsp; 按照课程搜索会显示该课程下所有编程练习。<br/>
           ** &nbsp;&nbsp; 关键字搜索会显示所有编程练习中符合条件的编程练习。<br/>
           ** &nbsp;&nbsp; 添加编程插件引用后，需更新课程缓存。<br/>
        </td>
    </tr>
</table>

<?php 
     echo $OUTPUT->footer();
 ?>
