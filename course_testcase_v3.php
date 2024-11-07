<?php
echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
require ('../../config.php');

  
if(!function_exists('bzcompress')){
    echo '请开启bz2服务';
    exit();
}

set_time_limit(0);


//操作说明
/**
 * 操作前，先备份   mdl_programming_test表
 * 本机测试 全部通过，没有出现-4的问题。
 * 一个in 对应 一个out
 
   存在问题：
    出现超时，可以刷新或者重启浏览器。必要时可以重启apache服务。
 
    需要设置的变量
     php.ini  --  realpath_cache_size=16MB
     php.ini  --  output_buffering=40960; 大一些 默认是4096
     如果要修改本页文件名，还需要更改redirectPage函数上的页面地址。*/
/**
 *   操作思路：
 *      在源文件$source_dir上，每次取前10个文件夹循环 把所有子文件存在一个数组上
 *              根据数组上的名字 对比 programing表。
 *              读取文件后，移动文件到$target_dir上
 *              处理文件上的字符串，转换，清理。
 *              对programming_test操作
 * 
 *      如果文件上对应的题库，不存在programming表，则移动到noexists目录上。
 *      在windows，复制到$target_dir后，有一些(1-2)个文件，在$source_dir上无法删除
 *      则需要人工手动处理。
 *      linux 情况未知。
 * 
 *      超大文件比如 0477px 可以手工处理
 */
$source_dir = "/www/a";
$target_dir = "/www/b";

$init_memory = memory_get_usage();
$buffer = ini_get('output_buffering');
echo str_repeat(' ',$buffer+1);     //防止浏览器缓存
ob_end_flush();     //关闭缓存
$source_arr = array();
$dirsize=0;
file_list($source_dir);

get_testcase_io($source_arr,$target_dir);
redirectPage($init_memory);

//批量去掉空格
function rename_programming(){
    global $DB;
    $sql = "select * from {programming}";
    $programminglist = $DB->get_records_sql($sql);
    
    if(!empty($programminglist)){
        foreach($programminglist as $v){
            if(strpos($v->name,']')){
                $newname = str_replace('] ', ']', $v->name);
                echo $newname.'update**************<br/>';
                 $update = array('id'=>$v->id,'name'=>$newname);
                 $DB->update_record_raw('programming', $update);
                 flush;
            }
        }
    }
}

//不使用这种方法
function handle_programming_testcase($source_dir,$target_dir,$limit=10){
    global $DB;
    //flag=0 未采集
    //test=0 未通过测试
    $sql = "SELECT * FROM {study_data_test} where name is not null and flag=0 and test=0 and id>1 limit $limit ";
    echo $sql.'<br/>';flush;
    $testlist = $DB->get_records_sql($sql);
    if(empty($testlist)){
        die('没有可以转换的test case');
    }
    $testcase = array();
    foreach($testlist as $key=>$value){
        $filename_pre = str_pad($value->nob,4,0,STR_PAD_LEFT);
        $filename = $filename_pre.$value->in_out;
        $filepath = $source_dir . '/'.$filename;
        $value->filepath=$filepath;;
        $value->name =  str_replace('] ', ']', $value->name);
        $testcase[] = $value;
        echo $value->name.'<br/>';
    }
    unset($testlist);
    flush;
  //  get_testcase_io($testcase,$source_dir,$target_dir);

}

function file_list($source_dir){
global $source_arr,$dirsize;
    $dirsize_limit=1024*1024*10;//每次处理10m的文件（已经很大了，数值更大，容易造成浏览器假死）

    if ($handle = opendir($source_dir)) {
        while (false !== ($file = readdir($handle))) {
            if ($file != "." && $file != "..") {
                if (is_dir($source_dir . "/" . $file)) {
                    //echo $source_dir . ": " . $file . "<br>"; //去掉此行显示的是所有的非目录文件
                    file_list($source_dir . "/" . $file);
                } else {
                    $str_index = strrpos($source_dir,'/');
                    $tmp_str = $source_dir;
                    $dir_name = substr($tmp_str, $str_index);
                    $dir_name = trim($dir_name,'/');
                    $filename = basename($source_dir . "/" . $file);
                    $path = dirname($source_dir . "/" . $file);
                    $source_arr[$dir_name]['dirname'] = $dir_name;
                    $source_arr[$dir_name]['filepath'] = $path;
                    $dirsize += filesize($source_dir . "/" . $file);
                    $source_arr[$dir_name]['filesize'] = $dirsize;
                    if(strrpos($filename, '.in')){
                        $filename = str_replace('.in', '', $filename);
                        $source_arr[$dir_name]['filename'][] = $filename;
                    }
                    if(count($source_arr)>5 || $dirsize >= $dirsize_limit){
                        break 1;
                    }
                }
            }
        }
    }

    $source_arr = array_slice($source_arr,0,2);

    return $source_arr;
}
function get_testcase_io($testcase,$target_dir){

    global $DB;

    foreach($testcase as $key => $value){
        $filepath = $value['filepath'];
        $strNoName = $value['dirname'];

        foreach($value['filename'] as $kx=>$vs){
            $buffer = $file_in_str = $file_out_str= '';
            $realName = $vs;
            $trim_pre = substr($strNoName, 4);
            $seq = str_replace($trim_pre, '', $vs);
            $fp_in =fopen($filepath.'/'.$realName.'.in','r');
            if(!$fp_in){
                 $fp_in = fopen($target_dir.'/'.$strNoName.'/'.$realName.'.in','r'); 
                //有可能in文件已经移动到target的可能（由于意外情况网页终止）
            }
            if($fp_in){
                    $fp_out = fopen($filepath.'/'.$realName.'.out','r');
                    if($fp_out){
                        fclose($fp_out);
                        $file = $realName;

                        //先判断是否存在课程，不存在则移走
                        $programmingName = $trim_pre.'.in';
                        $sql = "select * from {programming} where inputfile=?";

                        $programming = $DB->get_record_sql($sql,array($programmingName));

                        if(!$programming){
                            echo $programmingName.' 这个课程不存在,文件将移动到';
                            echo $target_dir.'/noexists/'.$strNoName.'<br/>';
                            rmdirs($filepath, $target_dir);
                            unset($seq);unset($file_in_str);unset($file_out_str);
                            flush;
                            break 1;
                        }

                        if($fp_in){
                            $file = $realName . '.in';
                            while(!feof($fp_in)){
                                ob_flush();
                                $buffer = fgets($fp_in,81920);//2m
                                $file_in_str .= $buffer;//输入的文件
                                flush();
                            }
                            $buffer = '';
                            if($fp_in){
                                pclose($fp_in);
                            }
                            if(!is_dir($target_dir.'/'.$strNoName)){
                                mkdir($target_dir.'/'.$strNoName, 0777);
                            }
                            if(!file_exists($target_dir.'/'.$strNoName.'/'.$file)){
                                copy($filepath.'/'.$file,$target_dir.'/'.$strNoName.'/'.$file); //拷贝到新目录
                                unlink($filepath.'/'.$file); //删除旧目录下的文件
                                if(!isEmptyDir($filepath)){
                                    rmdir($filepath); //删除空的目录
                                }
                            }
                            $file_in_str = stripcslashes($file_in_str);

                            $file_in_str = mb_ereg_replace('\r\n\r\n', '', $file_in_str);
                            $file_in_str = mb_ereg_replace('\r\n\n', '', $file_in_str);
                            $file_in_str = mb_ereg_replace(chr(13), '', $file_in_str);
                            $file_in_str = mb_ereg_replace('\n\n', '\n', $file_in_str);
                        }
                        $file = $realName;
                        $buffer = '';
                        $fp_out = fopen($filepath.'/'.$realName.'.out','r');
                        if($fp_out){
                            $file .= '.out';
                            while(!feof($fp_out)){
                                $buffer = fgets($fp_out,40960);//2m
                                $file_out_str .= $buffer;//输入的文件
                                ob_flush();
                                flush();
                            }
                            unset($buffer); 
                            if($fp_out){
                                fclose($fp_out);
                            }
                            
                            if(!is_dir($target_dir.'/'.$strNoName)){
                                mkdir($target_dir.'/'.$strNoName, 0777);
                            }

                            if(!file_exists($target_dir.'/'.$strNoName.'/'.$file)){
                                copy($filepath.'/'.$file,$target_dir.'/'.$strNoName.'/'.$file); //拷贝到新目录
                                unlink($filepath.'/'.$file); //删除旧目录下的文件
                                if(!isEmptyDir($filepath)){
                                    rmdir($filepath); //删除空的目录
                                }
                            }
                            $file_out_str = stripcslashes($file_out_str);

                            $file_out_str = mb_ereg_replace('\r\n\r\n','',$file_out_str);
                            $file_out_str = mb_ereg_replace('\r\n\n','',$file_out_str);
                            $file_out_str = mb_ereg_replace(chr(13),'',$file_out_str);
                            $file_out_str = mb_ereg_replace('\n\n','\n',$file_out_str);
                        }

                        //判断该 测试案例是否存在
                        $sql = "select id from {programming_tests} where programmingid=$programming->id and seq='$seq' limit 1";
                        $caseMsg = $DB->get_record_sql($sql);

                        if ($caseMsg) {
                            echo '当前课程--'.$programming->name  .'--'.$programming->id."--$seq 已存在*****删除后，重新添加<br/>";
                            $DB->delete_records('programming_tests',array('id'=>$caseMsg->id));
                            flush;
                        }

                        $testcase = array();
                        $testcase['programmingid'] = (int)$programming->id; // 这3个，在数据库里面读
                        $testcase['timelimit'] = (int)$programming->timelimit;
                        $testcase['memlimit'] = (int)ceil($programming->memlimit/1024);

                        $testcase['seq'] = $seq;
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

                        echo '当前课程--'.$programming->name  ."--$seq--";

                        $cmid = $DB->insert_record('programming_tests',$testcase);

                        echo $cmid . '<br/>';
                        unset($programming);unset($seq);
                        unset($file_in_str);unset($file_out_str);unset($testcase);
                        //处理完成后，重置初始参数
                        $file_in_str = '';$file_out_str = ''; // 输出 字符串
                        flush;
                    }else {
                        if($fp_out){
                            fclose($fp_out);
                        }
                        if($fp_in){
                            fclose($fp_in);
                        }
                    }
            }   else    {
                if($fp_in){
                    fclose($fp_in);
                }
            }
        }
        unset($testcase[$key]);
    }
}

function isEmptyDir( $path ){
    $dh= opendir( $path );
    while(false !== ($f = readdir($dh))){
       if($f != "." && $f != ".." )
          return true;
    }
    return false;
} 
function redirectPage($init_memory){
    global $dirsize;
    $dirsize=0;
    $page = 'course_testcase_v3.php?asf='.  rand(1, 999);
    echo '3秒后跳转到下一页，如果没有跳转<a href="'.$page.'">点击这里</a>';
    $final_memory = memory_get_usage();
    $final_memory = ceil(($final_memory - $init_memory)/1024);
    echo '<br/>当前使用的内存是：<h1>'.$final_memory.' kb </h1>';
    echo '<META HTTP-EQUIV="refresh" CONTENT="10; URL='.$page.'">';
    flush;
    exit();
}

function dir_path($path){
    $path = str_replace('\\','/',$path);
    if(substr($path,-1)!='/'){
        $path = $path  . '/';
    }
    return $path;
}

function dir_list($path,$exts,$list = array()){
    $path = dir_path($path);
    $files = glob($path.'*.*');
    foreach($files as $v){
        if(!$exts || preg_match("/\.($exts)/i", $v)){
            $list[] = $v;
            if(is_dir($v)){
                $list = dir_list($v, $exts, $list);
            }
        }
    }
    return $list;
}

//移动文件夹
function rmdirs($source,$dest){
        if(!is_dir($source)){
            return null;
        }
        $error = '';
        $source = str_replace('\\', '/', $source);
        $dest = str_replace("\\", '/', $dest);
        $filename = strrchr($source,'/');
        $filename = trim($filename,'/');
        
        $source_arr = scandir($source);
        chmod($source, 0777);
        foreach($source_arr as $key=>$val){
            if($val == '.' || $val == '..'){
                unset($source_arr[$key]);
            }else {
                 if(is_dir($source.'/'.$val)){
//                    if(@rmdir($source.'/'.$val) == 'true'){}            
//                      else
//                     rmdirs($source.'/'.$val,$dest);
                 }else{
                     if(!is_dir($dest.'/noexists/'.$filename)){
                         mkdir($dest.'/noexists/'.$filename, 0777, true);
                     }
                    copy($source.'/'.$val, $dest.'/noexists/'.$filename.'/'.$val);
                    if(!unlink($source.'/'.$val)){
                        $error = '文件夹：'.$source.'的内容删除失败，请手动删除！<br/>';
                    }
                 }
            }
        }
        if(!empty($error)){
            echo $error;
        }
        if(!isEmptyDir($source)){
            @rmdir($source);
        }
    }
?>
