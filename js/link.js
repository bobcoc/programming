	
	$(document).ready(function(){
            //章节id
            var $section = 0;
            var $courseid = $("#ccourse :selected").val();
            var $objs_second = new Object;
            
          $("#second").click(function(){
              $objs_second = $(this).val();
          });
            //课程id
          $("#ccourse").change(function(){
                $courseid = $(this).val();
                window.location.href="link.php?id="+$courseid;
                //修改 课程选项后，同步刷新 章节与编程练习
            });
            
            //选择不同的章节，刷新 引用的编程连续 和 所有的编程练习
            $("#section").click(function(){
            
                if($section!=$(this).val()){
                               $section = $(this).val(); 
                               $("#first").empty();
                               if($('#keyword').val()==''&&$('#scourse').val()<=0){
                                    $("#second").empty();
                               }
                               $("#page_link").attr("value",1);
                               $("#page_all").attr("value",1);//设置默认第一页
                 $.ajax({
                        type:"POST",
                        url:"link_lib.php",
                        data:"do=get_link_p&section="+$section+"&course="+$courseid+"",
                        success:function(msg){
                            $("#first").append(msg);
                        }
                    });
                if($('#keyword').val()==''&&$('#scourse').val()<=0){
                 $.ajax({
                     type:"POST",
                     url:"link_lib.php",
                     data:"do=get_link_r&page=1&section="+$section+"&course="+$courseid+"",
                     success:function(msg){
                            $("#second").append(msg);
                     }
                 });
                }
                }
            });
            //查看全部
            $("#viewall").click(function(){
                 $("#first").empty();
                 $("#second").empty();
                 $("#search_txt").val('');
                 $('#keyword').val('');
                 $("#scourse").attr("value",0);
                 $("#section").attr("value",0);//ie
                 $("#section option:selected").attr('selected','');//ff
                 $section=0;
                 $.ajax({
                        type:"POST",
                        url:"link_lib.php",
                        data:"do=get_link_p&section=0&course="+$courseid+"",
                        success:function(msg){
                            $("#first").append(msg);
                             $("#page_link").attr("value",1);
                        }
                    });
                 $.ajax({
                     type:"POST",
                     url:"link_lib.php",
                     data:"do=get_link_r&section=0&page=1&course="+$courseid+"",
                     success:function(msg){
                            $("#second").append(msg);
                            $("#page_all").attr("value",1);//设置默认第一页
                     }
                 });
            });
          
            //左边往右边添加
		$("#add").click(function(){
                    if($section==0){
                        alert("请选择章节");
                        return false;
                    }
			var $objs = $("#first :selected");
                      // 编程插件的id 
                      var $pids = "";
                      $objs.each(function(i){
                          $pids += $(this).val()+","; 
                      });
                      // 课程id  alert(courseid);
                    $.ajax({
                          type:"POST",
                          url:"link_lib.php",
                          data:"do=remove&section="+$section+"&pids="+$pids+"&course="+$courseid+"",
                          success:function(msg){
                              	$("#second").append($objs);
                          }
                      });
		});
                   
                   
                   $("#test").click(function(){
                   //查看变量
                   var checkText=$("#section").find("option:selected").text();
                    var courseName=$("#ccourse").find("option:selected").text();
                    $("#sql").text("当前的课程是："+courseName+" -> "+checkText+" course id:"+$courseid + " section id:"+$section);
                   });
                   
                //右边往左边添加
		$("#remove").click(function(){
                    if($section==0){
                        alert("请选择章节");
                        return false;
                    }
			var $objs = $("#second :selected");
                        
                         // 编程插件的id 
                      var $pids = "";
                      $objs.each(function(i){
                          $pids += $(this).val()+","; 
                      });
                    $.ajax({
                          type:"POST",
                          url:"link_lib.php",
                          data:"do=add&section="+$section+"&pids="+$pids+"&course="+$courseid+"",
                          success:function(msg){
                              		$("#first").append($objs);
                          }
                      });
		});
        $("#next_pro").click(function(){
        //分页的url
           var $page_all = Number($("#page_all").attr("value"))+1;
           $scourse = $("#scourse").val();
                    $data = '';
                    if($('#keyword').val()!=''){
                        //带搜索的分页
                        $data = "do=search&key="+$search_txt+"&page="+$page_all;
                    }else{
                        $data = "do=get_link_r&section="+$section+"&course="+$courseid+"&page="+$page_all+"&scourse="+$scourse;
                    }
        $.ajax({
                          type:"POST",
                          url:"link_lib.php",
                          data:$data,
                          success:function(msg){
                                $("#second").append(msg);
                                $("#second option:selected").attr("selected","");
                                $("#second option:last").attr("selected","selected");
                                $("#page_all").attr("value",$page_all);
                          }
                      });
        });
        $("#scourse").change(function(){
        //按照课程搜索
            $scourse = $("#scourse").val();
            $("#second").empty();
            $.ajax({
                     type:"POST",
                     url:"link_lib.php",
                     data:"do=get_link_r&page=1&section="+$section+"&course="+$courseid+"&scourse="+$scourse,
                     success:function(msg){
                            $("#second").append(msg);
                     }
             });
        });
        $("#next_link").click(function(){
            //引用的分页url
            var $page_link = Number($("#page_link").attr("value"))+1;
            $.ajax({
                type:"POST",
                url:"link_lib.php",
                data : "do=get_link_p&section="+$section+"&course="+$courseid+"&page="+$page_link,
                success:function(msg){
                    $("#first").append(msg);
                    $("#first option:selected").attr("selected","");
                    $("#first option:last").attr("selected","selected");
                    $("#page_link").attr("value",$page_link);
                }
            });
        });
        $("#clear_btn").click(function(){
            $("#search_txt").val('');
            $('#keyword').val('');
            $("#second").empty();
            $("#page_all").attr("value",1);
            $("#page_link").attr("value",1);
            $("#scourse").attr("value",0);
            $.ajax({
                     type:"POST",
                     url:"link_lib.php",
                     data:"do=get_link_r&page=1&section="+$section+"&course="+$courseid+"",
                     success:function(msg){
                            $("#second").append(msg);
                     }
                 });
        });
        $("#search_btn").click(function(){
            $search_txt = $("#search_txt").val();
            if($search_txt==''){
                $('#keyword').val('');
                alert('请输入您要搜索的内容');
                return false;
            }
            $('#keyword').val($search_txt);
            $("#second").empty();
            $("#page_all").attr("value",1);
            var $page_all = 1;
            $.ajax({
                type:"POST",
                url:"link_lib.php",
                data:"do=search&key="+$search_txt+"&page="+$page_all,
                success:function(msg){
                    $("#second").append(msg);
                }
            });
        });
        $("#update_btn").click(function(){
            $.ajax({
                          type:"POST",
                          url:"link_lib.php",
                          data:"do=rebuild&course="+$courseid,
                          success:function(msg){
                              alert("课程缓存更新完成！");
                          }
                      });
        });
		
	});
	