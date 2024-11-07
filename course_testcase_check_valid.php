<?php

echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
   require ('../../config.php');

if(function_exists('set_time_limit')){
    set_time_limit(3000);
}
$buffer = ini_get('output_buffering');
echo str_repeat(' ',$buffer+1);     //防止浏览器缓存
ob_end_flush();     //关闭缓存
ini_set('memory_limit','1024M');
    echo '<h1>检测以下数据，输出bz2解压读出数据为-4:</h1>';
   $page=1;
   //读1500条记录
   for($i=0;$i<10;$i++){
   $init_memory = memory_get_usage();
   ob_start();
       $pagesize = 100;
       
   //之前转换的方法 (测试的表)
    $strsql="SELECT t.*,p.name,p.id,p.course FROM  mdl_programming p join mdl_programming_tests t on  t.programmingid=p.id where (t.gzinput is not null or t.gzoutput is not null ) limit " . ($page-1)*$pagesize.','.$pagesize . "";
    
//    新的转换方法 (服务器上最新的表，应该使用这个)
//    $strsql="SELECT * FROM mdl_programming_tests t , mdl_programming p where t.programmingid=p.id  limit " . ($page-1)*$pagesize.','.$pagesize . "";
    echo $strsql.'<br/>';
    echo '正在处理：'. ($page-1)*$pagesize.','.$pagesize .'条记录<br/>';
    flush;
    // 执行sql查询
    $result = $DB->get_records_sql($strsql);
        foreach($result as $key => $value){
            $obj = new stdClass();
            $obj = $value;
      
            //output
                if(!is_null($obj->gzoutput)){
                    $bz_output = '';
                    $bz_output = bzdecompress($obj->gzoutput);
                     if($bz_output=='-4'){
                        echo $obj->course.':'.$obj->id .'-'.$obj->name .'-'.$obj->seq . '--ouput--不正常的数据<br/>';
                        flush;
                    }
                }
                //input
                if(!is_null($obj->gzinput)){
                    $bz_input = '';
                    $bz_input = bzdecompress($obj->gzinput);
                    if($bz_input=='-4'){
                          echo $obj->course.':'.$obj->id .'-'.$obj->name .'-'.$obj->seq.'--inpu--不正常的数据<br/>';
                          flush;
                    }
                }
         unset($bz_output); unset($bz_input);  unset($obj);
        }
    ob_flush();
    flush();

    sleep(1);
    $page++;
        $final_memory = memory_get_usage();
        $final_memory = ceil(($final_memory - $init_memory)/1024);
        echo '<br/>当前使用的内存是：<h1>'.$final_memory.' kb </h1>';
        flush;
   }

    
    
?>