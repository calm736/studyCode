<?php
/*异常和错误处理
  PHP一般不能自动抛出异常 需要手动抛出才可以捕获
 */
/* // ex1
   $a = null;
   try{
   $a = 5/0;
   echo $a,PHP_EOL;

   }catch(exception $e){
   $e->getMessage();
   $a = -1;
   }
   echo $a;*/
function Div($a,$b){
	if($b === 0){
		throw  new exception('除数不能为零');//手动抛出异常 
	}
	return $a/$b;
}


$a = null;
try{
	$a = Div(5,0);
	echo $a,PHP_EOL;

}catch(exception $e){
	echo $e->getMessage();
	echo PHP_EOL;
}
