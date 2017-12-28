<?php
//连接本地的 Redis 服务
//测试解决文件冲突
$redis = new Redis();
$redis->connect('127.0.0.1', 6379);
echo "Connection to server sucessfully";
//查看服务是否运行
echo "Server is running: " . $redis->ping()."\n";

echo $redis->get('llk')."\n";
