<?php
try{
$dsn = "mysql:host=127.0.0.1;dbname=llkTestDb";
$db = new PDO($dsn,'root','0604');

$sql = "select * from pdoT";
$query = $db->prepare($sql);
$query->execute();
var_dump($query->fetchAll(PDO::FETCH_ASSOC));
}catch(PDOException $err){
	echo $err->getMessage()."\n";
}
