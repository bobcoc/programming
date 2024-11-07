<?php
    require_once('../../config.php');

    $pageSize = 25;
    $page = isset($_REQUEST['page']) ? $_REQUEST['page'] : 1;
    if ($page < 1) {
        $page = 1;
    }
if (isset($_REQUEST['course'])) {
    if (isset($_REQUEST['do']) && $_REQUEST['do'] == "remove") {
        //在course_modules 上，删除 编程插件关联关系

        $section = required_param('section', PARAM_INT);

        //编程实例的Id
        $ids = required_param('pids', PARAM_RAW);
        $ids = rtrim($ids, ',');
        //     echo $ids;
        //课程id
        $course = required_param('course', PARAM_INT);

        //     1.修改mdl_course_sections 上的sequences
        //  获取 mdl_course_modules 关联实例
        $sql = "SELECT id from {course_modules} where instance in ($ids) and section=? ";
        $rs = array();
        $rs = $DB->get_records_sql($sql,array($section));
        
        $remove_sequence = '';

        foreach($rs as $k=>$v){
            $obj = new object();
            $obj = $v;
            $remove_sequence .= $obj->id.',';
        }
        
        $remove_sequence = rtrim($remove_sequence, ',');

        //    2.删除 mdl_course_modules 上的记录
        $ids_arr = explode(',', $ids);
        foreach($ids_arr as $k=>$v){
            $DB->delete_records("course_modules",array("course"=>$course,'instance'=>$v));
        }
        
        $rs = $DB->get_field("course_sections",'sequence',array('id'=>$section));
        $section_src = '';
        $section_new = '';
        $section_src = $rs;

        $arr1 = explode(',', $remove_sequence); //mdl_course_section
        $arr2 = explode(',', $section_src);
        $arr3 = array_diff($arr2, $arr1);
        $section_new = implode(',', $arr3);
        
        $DB->update_record("course_sections",array("id"=>$section,'sequence'=>$section_new));

    } else if (isset($_REQUEST['do']) && $_REQUEST['do'] == "add") {
//        do=add&section=278&pids=7,&course=33
        // 添加编程实例引用
        //编程实例id
        $ids = required_param('pids', PARAM_RAW);
        $section = required_param('section', PARAM_INT);
        $ids = rtrim($ids, ',');
        $idArr = explode(',', $ids);
        //课程id
        $course = required_param('course', PARAM_INT);

        //查找programming module 的 插件编号
        $module = 0;
        
        $module = $DB->get_field("modules",'id',array('name'=>'programming'));
        
        if(empty($module)){
            echo '<script>alert("编程插件不存在！请修复");</script>';
            exit();
        }
        foreach ($idArr as $key => $val) {

             //添加的时候·先检查该section中是否有此 programming instance

            $instance = $DB->get_field("course_modules",'instance',array('instance'=>$val,'section'=>$section));
            
            if( $instance ){
                continue 1;  //如果 mdl_course_modules 该 section中，已经存在 该 instance 则跳出循环。进行下一次循环。
            }
            
            $now = time();//update time

            $param = array(
                 'course' => $course ,
                 'module' =>$module,
                 'instance'=>$val,
                 'section'=>$section, 
                 'idnumber'=> '', 
                 'added'=> $now,
                 'score'=> 0,
                 'indent'=> 0,
                 'visible'=> 1,
                 'visibleold'=> 1,
                 'groupmode'=> 0,
                 'groupingid'=> 0,
                 'groupmembersonly'=> 0,
                 'completion'=> 0,
                 'completiongradeitemnumber'=> NULL,
                 'completionview'=> 0,
                 'completionexpected'=> 0,
                 'availablefrom'=> 0,
                 'availableuntil'=> 0,
                 'showavailability' => 0,
                 'showdescription' => 0
            );
            $cmid = $DB->insert_record('course_modules',$param);
            //修改course_sections 上的sequence
            //1.查询现有的sequence
            $sequence = '';
            $sequence_new = '';

            $sequence = $DB->get_field('course_sections','sequence',array('course'=>$course,'id'=>$section));
            
            if (!empty($sequence)) {
                $sequence_new = $sequence . ',' . $cmid;
            } else {
                $sequence_new = $cmid;
            }
            
            $DB->update_record('course_sections',array('id'=>$section,'sequence'=>$sequence_new));
        }

    } else if (isset($_REQUEST['do']) && $_REQUEST['do'] == "get_link_p") {
        //根据课程章节，显示 章节上已经引用的 编程练习

        //章节id
        $section = $_REQUEST['section'];
        //课程id
        $course = required_param('course', PARAM_INT);

        if (!empty($section)) {

            //选中了章节
            $sequences = $DB->get_field('course_sections','sequence',array('id'=>$section));
            
            if (!empty($sequences)) {
                
                //此处有一些小bug
                //当sequences 上的实例 非programming 会显示出没有记录--已解决
                
                $total_records = $DB->count_records_sql("SELECT count(id) from {programming} where id in 
( select instance from {course_modules} cm,{modules} m where m.id=cm.module and m.`name`='programming' and cm.id in ($sequences))");
              //  $total_records = count($total_records);
                //总页数
                $total_page = ceil($total_records / $pageSize);

                if ($page > $total_page) {
                    if($total_page>=1){
                        echo '<script>alert("没有记录了！");</script>';
                    }
                    exit();
                }
                
                $sql = "
                SELECT id,name from {programming} where id in 
( select instance from {course_modules} cm,{modules} m where m.id=cm.module and m.`name`='programming' and cm.id in ($sequences))";
                //    echo $sql;
                $rs = array();
                $rs = $DB->get_records_sql($sql,null,($page - 1) * $pageSize,$pageSize);
                foreach ($rs as $k=>$v){
                    $obj = new object();
                    $obj = $v;
                    echo "<option value=\"$obj->id\">$obj->name</option>";
                }
            }
        } else {
            //选中章节
            link_get_detail($course);
        }
    } else if (isset($_REQUEST['do']) && $_REQUEST['do'] == "get_link_r") {
//        do=get_link_r&section=0&page=1&course="+$courseid
        //根据课程章节，显示 章节上未引用的 编程练习
        //章节id
        $section = $_REQUEST['section'];
        //课程id
        $course = required_param('course', PARAM_INT);
        $scourse = optional_param('scourse',0,PARAM_INT);
        if (!empty($section)) {

            $sequences = '';
            $sequences = $DB->get_field('course_sections','sequence',array('id'=>$section));
            if (!empty($sequences)) {
                //有一种可能性，section里面有sequence值，但sequence上的引用，不属于编程插件的。
                if($scourse>0){
                    //按照课程搜索
               //     $wheres = ' and cm.instance='.$scourse;
                    $pagesql = "SELECT count(id) from {programming} where id  in 
( select cm.instance from {course_modules} cm,{modules} m where m.id=cm.module and m.`name`='programming' and cm.course=$scourse )  ";
                    $sql = "SELECT distinct(id),name from {programming} where id  in 
( select cm.instance from {course_modules} cm,{modules} m where m.id=cm.module and m.`name`='programming' and cm.course=$scourse  ) ";
                    
                }else{
                    $pagesql = "SELECT count(id) from {programming} where id not in 
( select instance from {course_modules} cm,{modules} m where m.id=cm.module and m.`name`='programming' and cm.id in ($sequences))  ";
                     $sql = "SELECT id,name from {programming} where id not in 
( select instance from {course_modules} cm,{modules} m where m.id=cm.module and m.`name`='programming' and cm.id in ($sequences))  ";
                }
                
                //1.先取出 属于 编程插件的引用实例
                //计算总行数
                
                $total_records = $DB->count_records_sql($pagesql);
                //总页数
                $total_page = ceil($total_records / $pageSize);

                if ($page > $total_page && $scourse <=0 ) {
                    echo '<script>alert("没有记录了！");</script>';
                    exit();
                }

               
                $rs = array();
                $rs = $DB->get_records_sql($sql,null,($page - 1) * $pageSize,$pageSize);

                foreach($rs as $k=>$v){
                    $obj = new object();
                    $obj = $v;
                    echo "<option value=\"$obj->id\">$obj->name</option>";
                }
            } else {
                //编程引用为空，显示全部的编程引用
                if( $scourse > 0 ){
                    //按照课程搜索
                    $pagesql = "select count(id) from {programming} where id in 
                        ( select cm.instance from {course_modules} cm,{modules} m where m.id=cm.module and m.`name`='programming' and cm.course=$scourse )  ";
                } else {
                    $pagesql = "select count(id) from {programming}";
                }
                //计算总行数
                $total_records = $DB->count_records_sql($pagesql);
                //总页数
                $total_page = ceil($total_records / $pageSize);

                if ($page > $total_page && $scourse<=0) {
                    echo '<script>alert("没有记录了！");</script>';
                    exit();
                }

                 $rs = array(); 
                 if( $scourse > 0){
                     //按照课程搜索
                     $sql = "SELECT distinct(id),name from {programming} where id  in 
( select cm.instance from {course_modules} cm,{modules} m where m.id=cm.module and m.`name`='programming' and cm.course=$scourse ) ";
                     $rs = $DB->get_records_sql($sql,null,($page - 1) * $pageSize,$pageSize);
                 } else {
                    $rs = $DB->get_records('programming',null,'','id,name',($page - 1) * $pageSize,$pageSize);
                 }
                 foreach($rs as $k=>$v){
                     $obj = new object();
                     $obj = $v;
                    echo "<option value=\"$obj->id\">$obj->name</option>";
                 }
            }

        } else {
            //未选中章节
            
            $page = $_REQUEST['page'] <= 0 ? 1 : $_REQUEST['page'];

            link_get_not_in_detail($course, $page,$scourse);
        }
    } else if (isset($_REQUEST['do']) && $_REQUEST['do'] == "rebuild") {
        //清除课程的缓存
        
        //课程id
        $course = required_param('course', PARAM_INT);
        
        rebuild_course_cache($course);
    }
    //
} else if (isset($_REQUEST['do']) && $_REQUEST['do'] == "search") {
    //关键字搜索
    $search = $_REQUEST['key'];

    if (!empty($search)) {
         
          $pagesql = "select count(id) from {programming} where name like '%$search%' ";
            //计算总行数
          $total_records = $DB->count_records_sql($pagesql);
          //总页数
          $total_page = ceil($total_records / $pageSize);

          if ($total_records <=0) {
              echo '<script>alert("没有符合的记录！");</script>';
              exit();
          }
          if ($page > $total_page) {
              echo '<script>alert("没有记录了！");</script>';
              exit();
          }

          //选中了章节
          //$sql = "select id,name from {programming} where name like '%$search%' limit " . ($page - 1) * $pageSize . ",$pageSize ";
          $rs = array();
          $rs = $DB->get_records_sql("select id,name from {programming} where name like '%$search%' ",null,($page - 1) * $pageSize,$pageSize);
          foreach($rs as $k=>$v){
              $obj = new object();
              $obj = $v;
              echo "<option value=\"$obj->id\">$obj->name</option>";
          }
          
    } else {
        
    }
}

//判断权限
function check_is_teacher($courseorid){
    global $DB,$USER,$SITE;
    if (!empty($courseorid)) {
        if (is_object($courseorid)) {
            $course = $courseorid;
        } else if ($courseorid == SITEID) {
            $course = clone($SITE);
        } else {
            $course = $DB->get_record('course', array('id' => $courseorid), '*', MUST_EXIST);
        }
    }
    $access = false;
    
    $access = link_is_admin();
    
    //判断是否为该课程教师
    $sql = "select u.id,c.fullname,u.lastname,u.firstname FROM
        {user} u,
        {course} c,
        {role_assignments} ra,
        {context} mc 
       WHERE
        u.id=ra.userid and ra.roleid in (1,2,3) and c.id=mc.instanceid and ra.contextid=mc.id and c.id=? ";
    $rs = array();
    $rs = $DB->get_records_sql($sql,array($course->id));
    foreach($rs as $k=>$v){
        $obj = new object();
        $obj = $v;
        if($obj->id==$USER->id){
            $access = true;
        }
    }
    
    if(!$access){
        //权限不足，需要登录
        throw new require_login_exception('Invalid course login-as access');
        redirect($CFG->wwwroot .'/enrol/index.php?id='. $course->id);
    }
    
   // return $access;
}
function link_is_admin(){
    global $DB,$USER,$SITE,$CFG;
    $access = false;
    //判断是否为网站管理员
    $rawsql = "
SELECT id,firstname,lastname,username,email FROM {user}
                 WHERE id <> '1' AND deleted = 0 AND confirmed = 1 AND id IN ($CFG->siteadmins) ORDER BY lastname, firstname, id";
    $rs = array();
    $rs = $DB->get_records_sql($rawsql);
    foreach($rs as $k=>$v){
        $obj = new object();
        $obj = $v;
        if($obj->id==$USER->id){
            $access = true;
        }
    }
    return $access;
}

//数据库遍历
    //遍历课程列表

 function link_course($id) {
        
        global $CFG,$DB,$USER;
        //需要根据用户的权限，显示不同的课程
        //教师只有编辑自己的课程权限
        //管理员可以编辑全部的课程权限
        $admins = $CFG->siteadmins;
        $siteadmins = explode(',', $admins);
        array_unshift($siteadmins, "tr");
        $flag = false;
        
        if(array_search($USER->id, $siteadmins)){
            $flag = true; //管理员权限
        }
        
       if(!$flag){
           $sql= "select c.id,c.shortname FROM
            {user}  u,
            {course}  c,
            {role_assignments}  ra,
            {context}  mc 
           WHERE
            c.id != 1 and u.id=ra.userid and ra.roleid in (1,2,3) and c.id=mc.instanceid and ra.contextid=mc.id and u.id=$USER->id";
       }else{
            $sql = "select id,shortname from {course} where id != 1 order by id";
       }
        
        $rs = array();
        $rs = $DB->get_records_sql($sql);
        foreach($rs as $k=>$v){
            $obj = new object();
            $obj = $v;
            if ($id == $obj->id) {
                echo "<option  selected=\"selected\" value=\"$obj->id\">$obj->shortname</option>";
            } else {
                echo "<option value=\"$obj->id\">$obj->shortname</option>";
            }
        }
    }
    
    /**
     * 按照课程搜索
     */
    function search_link_course($id) {
        
        global $DB;
      
        $sql = "select id,shortname from {course} where id != 1 order by id";
        
        $rs = array();
        $rs = $DB->get_records_sql($sql);
        foreach($rs as $k=>$v){
            $obj = new object();
            $obj = $v;
            echo "<option value=\"$obj->id\">$obj->shortname</option>";
        }
    }

    //打印课程列表引用的编程插件
    // $id -- course id
    function link_get_detail($id) {
        
        global $DB,$pageSize,$page;
        
        $total_records = $DB->count_records_sql("select count(p.id) from {programming} p,{course_modules} cm,{modules} m 
                where cm.course = $id and cm.instance=p.id and m.id=cm.module and m.`name`= 'programming' ");
        //总页数
        $total_page = ceil($total_records / $pageSize);
        if ($page > $total_page && $total_records > $pageSize) {
            echo '<script>alert("没有记录！");</script>';
            exit();
        }
        //没有记录
        if($total_records <=0){
            return false;
        }
        
        $rs = array();
        $sql = "select p.id,p.name from {programming} p,{course_modules} cm,{modules} m 
                where cm.course = $id and cm.instance=p.id and m.id=cm.module and m.`name`= 'programming' order by p.id asc";
        $rs = $DB->get_records_sql($sql,null,($page - 1) * $pageSize,$pageSize);
        foreach($rs as $k=>$v){
            $obj = new object();
            $obj = $v;
            echo "<option value=\"$obj->id\">$obj->name</option>";
        }
        
    }

    //打印课程列表，没有引用到的编程插件
    // $id -- course id
    function link_get_not_in_detail($id, $page = 1,$scourse=0) {
        
        global $pageSize,$DB;
        if($scourse>0){
            $totalsql = "select count(p.id) from {programming} p where p.id in 
    (select cm.instance from {programming} p,{course_modules} cm,{modules} m 
                where cm.course = $scourse and cm.instance=p.id and m.id=cm.module and m.`name`= 'programming')";
        } else {
            $totalsql = "select count(p.id) from {programming} p where p.id not in 
    (select cm.instance from {programming} p,{course_modules} cm,{modules} m 
                where cm.course = $id and cm.instance=p.id and m.id=cm.module and m.`name`= 'programming')";
        }
        $total_records = $DB->count_records_sql($totalsql);
        //总页数
      $total_page = ceil($total_records / $pageSize);
        if ($page > $total_page&&$scourse<=0) {
            echo '<script>alert("没有记录了！");</script>';
            exit();
        }
        if($scourse>0){
             $sql = "select p.id,p.name from {programming} p where p.id  in 
    (select cm.instance from {programming} p,{course_modules} cm,{modules} m 
                where cm.course = $scourse and cm.instance=p.id and m.id=cm.module and m.`name`= 'programming') ";
        } else {
            $sql = "select p.id,p.name from {programming} p where p.id not in 
    (select cm.instance from {programming} p,{course_modules} cm,{modules} m 
                    where cm.course = $id and cm.instance=p.id and m.id=cm.module and m.`name`= 'programming') ";
        }
        $rs = array();
        $rs = $DB->get_records_sql($sql,null,($page - 1) * $pageSize,$pageSize);
        foreach($rs as $k=>$v){
            $obj = new object();
            $obj = $v;
             echo "<option value=\"$obj->id\">$obj->name</option>";
        }
        
    }

    //打印课程里面的章节列表
    // $id -- course id
    function link_get_course_section($id) {
        global $DB;
        $sql = "select id,course,section,name from {course_sections} s where s.course=$id and section != 0 ";
        $rs = array();
        $rs = $DB->get_records_sql($sql);
        foreach($rs as $k=>$v){
            $obj = new object();
            $obj = $v;
            if (!empty($obj->name)) {
                echo "<option value=\"$obj->id\"> $obj->name </option>";
            } else {
                echo "<option value=\"$obj->id\"> 主题 $obj->section </option>";
            }
        }
    }

?>