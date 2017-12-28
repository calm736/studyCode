<?php
class person{
	public $name = 'Tom';
	public $gender;
	static $money = 10000;
	public function __construct(){
		echo '这里是父类',PHP_EOL;
	}
	public function say(){
		echo $this->name,"\tis\t",$this->gender,"\r\n";
	}
}
class family extends person{
	public $name;//子类继承费用类属性  若子类没有则继承 若存在则按子类的属性来(所以php的继承属性相当于重写)
	public $gender;
	public $age;
	static $money =10000;
	public function __construct(){
		parent::__construct();//调用父类的构造
		echo '这里是子类',PHP_EOL;
	}
	public function say(){
		parent::say();
		echo $this->name,"\tis\t",$this->gender,",and is\t",$this->age,PHP_EOL;
	}
	public function cry(){
		echo parent::$money,PHP_EOL;
		echo "%>_<%",PHP_EOL;
		echo self::$money,PHP_EOL;
		echo '(*^_^*)';
	}
}
$poor = new family();
$poor->name =  'Lee';
$poor->gender = 'female';
$poor->age = 25;
$poor->say();
$poor->cry();
