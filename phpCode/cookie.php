<?php
header('Content-Type: text/html; charset=utf-8');

$cookie_file = dirname(__FILE__).'/cookie.txt';
//$cookie_file = tempnam("tmp","cookie");

//先获取cookies并保存
$url = "http://toutiao.iiilab.com/";
$ch = curl_init($url); //初始化
curl_setopt($ch, CURLOPT_HEADER, 0); //不返回header部分
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); //返回字符串，而非直接输出
curl_setopt($ch, CURLOPT_COOKIEJAR,  $cookie_file); //存储cookies
curl_exec($ch);
curl_close($ch);
