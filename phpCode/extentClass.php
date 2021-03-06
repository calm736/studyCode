<?php
require_once("Person.php");
/*在子类中重写的方法访问权限一定不能低于父类被覆盖的方法的访问权限
  关键字和魔术方法
  final: 1.加载类前  该类不能被继承  2.加载方法前 该方法不能被继承
  static: 1. 修饰类成员 2.修饰类方法  类外使用类名访问 类内使用self::访问   静态方法只能访问静态成员
  const:  类内声明常量的方法 1.声明时必须被初始化且名字前面不用$ 以后不可被更改  2.一般大写 3.类内使用self,类外使用类名访问 同statici
  clone: php5使用引用来调用对象 使用clone 可以复制一份完全相同的对象
  魔术方法：
  __call:当对象调用未定义的方法时，有一定会导致程序出错并退出。 但定义了__call方法后，当调用了不存在的方法后会自动调用call方法，并使程序继续执行下去。
  __call方法后两个参数，第一个参数是不存在方法的名，第二个参数是参数列表数组
  全局函数：实现自动加载类
  __autolaod($className);
 */
class Student extends Person {
        private $strSchool;
        public $sNumber;
        //构造函数必须公函数
        public function __construct(){
                echo "create a sub class\n";
        }
        public function __destruct(){
                echo "delete a sub class\n";
        }
        public function __set($valName,$val){
                Person::__set($valName,$val);
                if($valName === 'strSchool'){
                        $this->$valName = strval($val);
                }
        }

        //可以使用继承父类方法的方式将__set __get 继承 达到访问父类私有成员的目的
        public function __get($valName){
                Person::__get($valName);
                if(isset($this->$valName)){
                        return $this->$valName;
                }
        }
        //可以定义__clone 在clone对象时重新为新对象赋值
        /*public function __clone(){
        //...
        }*/
        public function __call($fName,$args){
                echo $fName." method is not exsit\n";
                echo 'parameter:';
                print_r($args);

        }
        //自动加载类 貌似不能自动加载基类
        function __autoload($classname){ 
                $classpath='./'.$classname.'.php'; 
                if(file_exists($classpath)){ 
                        require_once($classpath); 
                } else{ 
                        //echo 'class file' . $classpath . 'not found!';
                        echo 'class file'.$classpath.'not found';
                } 
        } 
        //一定要返回一个字符串
        public function __toString(){
                return 'it is Student class';
        }
        public function printEle(){
                echo "Name:".$this->strName."\n";
                echo "PhoneNumber:".$this->strPhoneNumber."\n"; 
                echo "school:".$this->strSchool."\n";

        } 
};
$oneStudent = new Student();
echo $oneStudent."\n"; 
$oneStudent->strName='lilinke';
$oneStudent->strPhone = '13009710953';
$oneStudent->strSchool = '哈理工';

$oneStudent->printEle();
$student2 = clone $oneStudent; 
//$student2 = $oneStudent; //这里只是复制一份对象的引用
$oneStudent = null;//释放对象
$student2->printEle();

//调用不存在的方法 会自动调用__call方法使程序继续运行下去 
$student2->wy('wy','0604',23);
$student2=null;


echo  "脚本结束\n";
