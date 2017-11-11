<?php
//概念
//this是指向当前对象的指针（可以看成C里面的指针），self是指向当前类的指针，parent是指向父类的指针
//类的声明 [关键字 abstract 或 final] class  类名
class Person{
        //类成员变量声明  [关键字] 成员变量名
        var $intAge;//没有特别属性时用var声明 一旦有别的关键字修饰就要将var去掉
        public $strName;
        private $strPhoneNumber;
        private $intSex;
        public static $strAddress;
        //构造函数
        function __construct(){
                echo "create a object\n";
        }
        //析构
        function __destruct(){
                echo "delect a object\n";
        }
        public function clear(){
                $this->strName=null;
                $this->strPhoneNumber=null;
                $this->intAge=null;
        }
        public function init($strPhoneNu){
                $this->strPhoneNumber = $strPhoneNu;


        }
        public function printPvt()
        {
                echo $this->strPhoneNumber."\n";
        }
        //类方法定义
        function eat(){//默认public属性
                echo "He is eating...\n";
        } 
        public function say(){
                echo "He is saying\n";
                $this->walk();
        }
        private function walk(){
                echo "He is walking\n";
        }

        public static function run(){
                echo "He is running\n";
        }
        //php 预定义方法不需要用户自己调用 在特殊情况下自动调用 魔术方法
        //__set 完成对私成员的赋值操作 不用用户自己调用
        public function __set($name,$val){
                if($name === 'strPhoneNumber'){
                        $this->strPhoneNumber=strval($val);
                }else if($name === 'intSex'){
                        echo "===================\n";
                        $this->$name = intval($val);
                }
        }
        public function __get($name){
                if(isset($this->$name)){
                        return $this->$name;
                }
                return null;
        }


};
/*Person::run();
//实例化对象
// $对象名 = new 类名称[参数列表]
$onePerson = new Person();
$onePerson->strName = strval('lilinke');//对象访问类成员是不加$
$onePerson->strPhoneNumber=strval('13009710953'); //私有成员在不可在类外使用 有__set 函数可以使用
$onePerson->intSex = intval(1);
//$onePerson->init(strval('13009710953'));
//$onePerson->strAddress=strval('2402');//静态成员不属于对象
Person::$strAddress=strval('2402');//静态成语使用属于类 使用类名访问
echo $onePerson->strName."\n";
//$onePerson->printPvt();
echo $onePerson->strPhoneNumber."\n";
echo $onePerson->intSex."\n";
//echo ($OnePerson->intSex===1)? '男':'女'."\n";
echo Person::$strAddress."\n";


$onePerson->eat();
$onePerson->say();
$onePerson = null;//释放一个对象 比unset 要好*/
//echo "============================================\n";
