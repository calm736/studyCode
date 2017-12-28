<?php
$html = file_get_contents("http://www.baidu.com/");
print_r($html);
$fp = fopen('http://www.baidu.com/','r');
print_r(stream_get_meta_data($fp));
fclose($fp);
