<?php
/**
  面向接口编程 interface关键字声明的类 接口是抽象类的变体 接口中的的所有方法都是抽象的，没有一个有程序实体 接口中可以有方法和常量
  在程序里接口的方法必须全部实现否则报fatal

 */
interface mobile{//声明接口
	public function run();
}
class plain implements mobile{ //一个类只可以继承一个抽象类但可以继承多个接口 用,隔开
	public function run(){
		echo "我是飞机\n";
	}
	public function fly(){
		echo "飞行\n";
	}

}
class car implements mobile{
	public function run(){
		echo "我是汽车\n";
	}
}
class machine{
	function demo(mobile $a){

		//$a->fly();
		$a->run();
	}
}
$obj = new machine();
$obj->demo(new plain());
$obj->demo(new car());
