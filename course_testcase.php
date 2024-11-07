<?php
echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
    require ('../../config.php');

  
    if(!function_exists('bzcompress')){
        echo '请开启bz2服务';
        exit();
    }
    
function file_list($source_dir,$target_dir){
    $init_memory = memory_get_usage();
    global $DB;
    if(function_exists('set_time_limit')){
        set_time_limit(3000);
    }

    $i=1;
    $unsetcount=1; //没有下载到的案例
    $file_in_str = '';
    $file_out_str = ''; // 输出 字符串
    $file_in_sequence = 101;
    $file_out_sequence = 102; //输出 序列号
    
    $file_in_size = 0;
    
    if($handle = opendir($source_dir)){ // 打开路径
        while(false !== ($file = readdir($handle))){//循环读取目录中的文件名并赋值给$file
            if($file != "." && $file != ".."){ //排除当前路径和前一路径
                if(is_dir($source_dir.'/'.$file)){
                    //echo $source_dir.": ".$file."<br>";//去掉此行显示的是所有的非目录文件 
                    file_list($source_dir.'/'.$file,$target_dir);
//                    break 2;//只循环一个文件夹
                }else{
                //    echo $source_dir . "/".$file."<br/>";
                    $strNoName = strrev(strtok(strrev($source_dir), '/'));
                    $inoutID = ltrim(substr($strNoName,0,4),0);
                    $inoutFileName = substr($strNoName,4);
                    if(strstr($file, 'in')){
                        $file_in_size = filesize($source_dir."/".$file) ;// 单个文件超过5m 则只转换 一个in和out
                    }
                    
                    /**读文件部分 方法1-- 占用内存较少 效率低**/
                    $fp = fopen($source_dir."/".$file, "r");
                    $buffer = '';
                    if($fp){
                        while(!feof($fp)){
                            ob_flush();
                            $buffer = fgets($fp,2048);//2m

                            if(strstr($file, 'in')){
                                $file_in_str .= $buffer;//输入的文件
                            }
                            if(strstr($file, 'out')){
                                $file_out_str .= $buffer;//输出的文件
                            }
                            flush();
                        }
                        fclose($fp);
                        
                        if(!is_dir($target_dir.'/'.$strNoName)){
                            mkdir($target_dir.'/'.$strNoName, 0777);
                        }
                        if(!file_exists($target_dir.'/'.$strNoName.'/'.$file)){
                            copy($source_dir.'/'.$file,$target_dir.'/'.$strNoName.'/'.$file); //拷贝到新目录
                            unlink($source_dir.'/'.$file); //删除旧目录下的文件
                            if(!isEmptyDir($source_dir)){
                                rmdir($source_dir); //删除空的目录
                            }
                        }
                    }
                    unset($buffer); 
                    
                    if(strstr($file, 'in')){
                    $file_in_sequence = str_replace($inoutFileName, "", $file);
                    $file_in_sequence = str_replace(".in", '', $file_in_sequence);
                    $file_in_str = str_replace("\r","",$file_in_str);
                    $file_in_str = str_replace("\n\n","",$file_in_str);
                       
                    }
                    if(strstr($file, 'out')){
                        $file_out_sequence = str_replace($inoutFileName, "", $file);
                        $file_out_sequence = str_replace(".out", '', $file_out_sequence);
                        $file_out_str = str_replace("\r","",$file_out_str);
                        $file_out_str = str_replace("\n\n","",$file_out_str);
                    }
                }
            }
            // 处理，入库
            $sql = '';
            while($file_in_sequence==$file_out_sequence & !empty($file_in_sequence)){
                
                if($unsetcount>30){//此变量，必须为---偶数--- 每次不宜超过20个<有一些文件比较大，输入比较耗时>
                    redirectPage($init_memory);
                } else if(empty($inoutFileName) || empty($inoutID)){
                    $unsetcount++;
                    break 1;
                }
                $sql = "select name from {study_data_test} where in_out='$inoutFileName' and nob='$inoutID' ";
                $programmingName = $DB->get_field_sql($sql);
                if(!$programmingName){
                    $unsetcount++;
                    break 1;
                }
                $programmingName = str_replace('] ', ']', $programmingName);
                $sql = "select * from {programming} where name=? ";
                $programming = $DB->get_record_sql($sql,array($programmingName));
                if(!$programming){
                    $unsetcount++;
                    break 1;
                }
                
                //判断该 测试案例是否存在
                $sql = "select * from {programming_tests} where programmingid=$programming->id and seq='$file_in_sequence'";
                $caseMsg = $DB->get_record_sql($sql);
      
                if ($caseMsg) {
                    echo '当前课程--'.$programmingName  .'--'.$programming->id."--$file_in_sequence 已存在*****删除后，重新添加<br/>";
                /**    unset($file_in_str);unset($file_out_str);
                    $file_in_str = '';$file_out_str = ''; // 输出 字符串
                    $unsetcount++;
                    if($file_in_size>1024*1024*4){
                        redirectPage();
                    }
                    break 1;**/
                    $DB->delete_records('programming_tests',array('id'=>$caseMsg->id));
                }

                $testcase = array();
                $testcase['programmingid'] = (int)$programming->id; // 这3个，在数据库里面读
                $testcase['timelimit'] = (int)$programming->timelimit;
                $testcase['memlimit'] = (int)ceil($programming->memlimit/1024);
                
                $testcase['seq'] = $file_in_sequence;
                $testcase['input'] = $file_in_str;
                $testcase['output'] = $file_out_str;
                
                if (strlen($testcase['input']) > 1024) {
                    $testcase['gzinput'] = bzcompress($testcase['input']);
                    $testcase['input'] = mb_substr($testcase['input'], 0, 1024, 'UTF-8');
                }else{
                    $testcase['gzinput'] = null;
                }
                if (strlen($testcase['output']) > 1024) {
                    $testcase['gzoutput'] = bzcompress($testcase['output']);
                    $testcase['output'] = mb_substr($testcase['output'], 0, 1024, 'UTF-8');
                }else{
                    $testcase['gzoutput'] = null;
                }
                
                $testcase['cmdargs'] = NULL;
                
                $testcase['nproc'] =0;
                $testcase['pub'] = 1;
                $testcase['weight'] = 1;
                $testcase['memo'] = '';
                
                $testcase['timemodified'] = time();
                
                echo '当前课程--'.$programmingName  ."--$file_in_sequence--";
          
                $cmid = $DB->insert_record('programming_tests',$testcase);
    
                echo $cmid . '<br/>';
                unset($file_in_str);unset($file_out_str);unset($testcase);
                //处理完成后，重置初始参数
                $file_in_str = '';
                $file_out_str = ''; // 输出 字符串
                $file_in_sequence = 101;
                $file_out_sequence = 102; //输出 序列号
                
                if($file_in_size> 1024*1024*4 ){// 单个文件超过3m 则只转换 一个in和out
                    redirectPage($init_memory);
                }
            }
            $i++;
            if($i>30){ //此变量，必须为---偶数--- 每次不宜超过20个<有一些文件比较大，输入比较耗时>
                redirectPage($init_memory);
                
            }
            
        }
    }
}

$source_dir = "/www/ftpold";
$target_dir = "/www/ftp";

file_list($source_dir,$target_dir);

function isEmptyDir( $path )
{
    $dh= opendir( $path );
    while(false !== ($f = readdir($dh)))
    {
       if($f != "." && $f != ".." )
          return true;
    }
    return false;
} 
function redirectPage($init_memory){
    $page = 'course_testcase.php?asf='.  rand(1, 999);
    echo '3秒后跳转到下一页，如果没有跳转<a href="'.$page.'">点击这里</a>';
    $final_memory = memory_get_usage();
    $final_memory = ceil(($final_memory - $init_memory)/1024);
    echo '<br/>当前使用的内存是：<h1>'.$final_memory.' kb </h1>';
    echo '<META HTTP-EQUIV="refresh" CONTENT="3; URL='.$page.'">';
    exit();
}
?>
