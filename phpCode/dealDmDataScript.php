<?php
/**
 * 处理数据挖掘数据
 *  排序 去重
 * Created by PhpStorm.
 * User: lilinke
 * Date: 2018/4/13
 * Time: 10:56
 */

define ('EASYSCRIPT_DEBUG',false);          //debug 模式
define ('EASYSCRIPT_THROW_EXEPTION',true);  //抛异常模式

define ('ROOT_PATH', dirname ( __FILE__ ) . '/../../../../' );
define ('SCRIPTNAME',basename(__FILE__,".php"));   //定义脚本名
define ('BASEPATH',dirname(__FILE__));
define ('CONFPATH',BASEPATH."/conf");
define ('DATAPATH',BASEPATH."/data");
define ('LOGPATH',"./log/common/script/grab");
define('IS_ORP_RUNTIME', true);
set_include_path(get_include_path() . PATH_SEPARATOR. BASEPATH.'/../../' . PATH_SEPARATOR. BASEPATH);

require_once ROOT_PATH . "app/common/script/grab/lib/simple_html_dom.php";
require_once ROOT_PATH . "app/common/script/grab/lib/util.php";
/**
 *
 * @param：$strClassName
 * @return：string
 */
function __autoload($strClassName)
{
    require_once str_replace('_', '/', $strClassName) . '.php';
}
spl_autoload_register('__autoload');


/**
 * 日志行打印
 * @param $strMsg
 * @param bool $bolLineInfo
 * @return null
 */
function outLogLine($strMsg, $type='w', $bolEcho = false, $bolLineInfo = true){
    $strFileName = '';
    $intLineNo = 0;
    if($bolLineInfo) {
        if (defined('DEBUG_BACKTRACE_IGNORE_ARGS')) {
            $arrTrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
        } else {
            $arrTrace = debug_backtrace();
        }
        if(!empty($arrTrace)) {
            $strFileName = basename($arrTrace[0]['file']);
            $intLineNo = intval($arrTrace[0]['line']);
            //$strMsg = $strFileName . ":Line($intLineNo):" . $strMsg;
        }
    }
    if('n' == $type){
        Bingo_Log::notice($strMsg, '', $strFileName, $intLineNo);
    }else if('w' == $type){
        Bingo_Log::warning($strMsg, '', $strFileName, $intLineNo);
    }else if('f' == $type){
        Bingo_Log::fatal($strMsg, '', $strFileName, $intLineNo);
    }else{
        Bingo_Log::warning($strMsg, '', $strFileName, $intLineNo);
    }
    if($bolEcho){
        echo $strMsg.PHP_EOL;
    }
}


$modCount = -1;
$modBase = 10;

if(null != $argv && !empty($argv) && is_array($argv) && 3 == count($argv)){
    $modBase = intval($argv[1]);
    $modCount = intval($argv[2]);
}else{
    outLogLine('dealDmDataScript  script get invaild input param.argv:['.serialize($argv).']', 'w');
}

$strScriptName = 'dealDmDataScript';
if(0 <= $modCount ){
    $strScriptName = $strScriptName.'_'.$modCount;
}else{
    $modCount = 'all';
}


define ('LOG', 'log');
Bingo_Log::init(array(
    LOG => array(
        'file'  => LOGPATH ."/". $strScriptName. ".log",
        'level' => 0x0f,
    ),
), LOG);

$easyScriptObj  = false;
try{
    outLogLine('dealDmDataScript ready run.', 'n', true);
    $ScriptConf = array(
        'memory_limit'   => '1024M',
        'data_path'      => DATAPATH,
        'conf_path'      => CONFPATH,
        'lock_file_name' => $strScriptName.'.lock',
        'done_file_name' => $strScriptName.'.done',
        'db_alias'       => array(
            'im'     => 'forum_content',
        ),
        'conf_alias'     => array(
            'main'     => 'main.conf',
        ),
    );

    $easyScriptObj = new Util_EasyScript($ScriptConf);
    //防止脚本重复执行
    if( $easyScriptObj->checkScriptIsRuning() === true ){
        outLogLine('dealDmDataScript is runing.', 'n', true);
        exit;
    }
    outLogLine('dealDmDataScript begin runing.', 'n', true);

    $getObj = new DealDmDataScriptAction();
    $getObj->_execute();
    $easyScriptObj->runSuccess();
    outLogLine('dealDmDataScript run success.', 'n', true);
}catch(Exception $e){
    if($easyScriptObj !== false){
        outLogLine('dealDmDataScript run fail!'.$easyScriptObj->getErr2String(), 'w');
    }else{
        outLogLine('dealDmDataScript run fail![null]', 'w');
    }
    outLogLine('dealDmDataScript fail.'.$easyScriptObj->getErr2String());
    exit;
}
outLogLine('dealDmDataScript finish.', 'n', true);


class DealDmDataScriptAction{

    //原始文件
    public static $fpShenTieBang=null;
    public static $strShenTieBangPathName = 'shenTieBangData';
    public static $fpShenTieBangReply=null;
    public static $strShenTieBangReplyPathName = 'shenTieBangReplyData';
    public static $fpShouYeOriginalRowData=null;//未过滤
    public static $strShouYeOriginaRowDatalPathName = 'shouYeOriginalRowData';//未过滤
    //排序 去重后的文件
    public static $fpShenTieBangSorted=null;
    public static $strShenTieBangSortedPathName = 'shenTieBangSortedData';
    public static $fpShenTieBangReplySorted=null;
    public static $strShenTieBangReplySortedPathName = 'shenTieBangReplySortedData';
    public static $fpShouYeOriginalSorted=null;
    public static $strShouYeOriginaDatalSortedPathName = 'shouYeOriginalSortedData';

    public static $arrShenTieBangTidList =array();

    //构造函数
    function __construct(){
        if(self::$fpShenTieBang == null){self::$fpShenTieBang = fopen(DATAPATH.'/'.self::$strShenTieBangPathName,'r') or die("open ".self::$strShenTieBangPathName." failed");}//神贴榜
        if(self::$fpShenTieBangReply == null){self::$fpShenTieBangReply = fopen(DATAPATH.'/'.self::$strShenTieBangReplyPathName,'r')or die("open ".self::$strShenTieBangReplyPathName." failed");}//神贴榜回复
        if(self::$fpShouYeOriginalRowData == null){self::$fpShouYeOriginalRowData = fopen(DATAPATH.'/'.self::$strShouYeOriginaRowDatalPathName,'r')or die("open ".self::$strShouYeOriginaRowDatalPathName." failed");}//首页也原创未过滤

        if(self::$fpShenTieBangSorted == null){self::$fpShenTieBangSorted = fopen(DATAPATH.'/'.self::$strShenTieBangSortedPathName,'w+') or die("open ".self::$strShenTieBangSortedPathName." failed");}//神贴榜
        if(self::$fpShenTieBangReplySorted == null){self::$fpShenTieBangReplySorted = fopen(DATAPATH.'/'.self::$strShenTieBangReplySortedPathName,'w+')or die("open ".self::$strShenTieBangReplySortedPathName." failed");}//神贴榜回复
        if(self::$fpShouYeOriginalSorted == null){self::$fpShouYeOriginalSorted = fopen(DATAPATH.'/'.self::$strShouYeOriginaDatalSortedPathName,'w+')or die("open ".self::$strShouYeOriginaDatalSortedPathName." failed");}

    }
    //析构
    function __destruct(){
        if(self::$fpShenTieBang != null){fclose(self::$fpShenTieBang);}//神贴榜
        if(self::$fpShenTieBangReply != null){fclose(self::$fpShenTieBangReply);}//神贴榜回复
        if(self::$fpShouYeOriginalRowData != null){fclose(self::$fpShouYeOriginalRowData);}//首页也原创未过滤

        if(self::$fpShenTieBangSorted != null){fclose(self::$fpShenTieBangSorted);}//神贴榜
        if(self::$fpShenTieBangReplySorted != null){fclose(self::$fpShenTieBangReplySorted);}//神贴榜回复
        if(self::$fpShouYeOriginalSorted != null){fclose(self::$fpShouYeOriginalSorted);}

    }

    public function _execute(){
     $this->sortShenTieBangData();
     $this->sortShenTieBangReplyData();
     $this->sortShouYeYuanChuangData();
    }

    /**
     * 神贴榜排序 总回复数倒序
     * 字段 tid / title / 链接（帖子链接）/ 首页曝光 / 首页点击 / ctr／总回复数
     */
    public function sortShenTieBangData(){
        if(empty(self::$strShenTieBangPathName) || empty(self::$strShenTieBangSortedPathName)){
            return false;
        }
        $fileContent = file_get_contents(DATAPATH.'/'.self::$strShenTieBangPathName);
        if(empty($fileContent)){
            return false;
        }
        $arrDataRows = explode("\n", $fileContent);
        $arrDataRows = array_filter($arrDataRows);
        $arrSortRows = array();
        foreach ($arrDataRows as $key => $strValue){
            $arrTemp = explode("\t",$strValue);
            if(count($arrTemp) != 7){continue;}//垃圾数据
            $arrSortRows[$arrTemp[6]] = $strValue;
            self::$arrShenTieBangTidList[] = $arrTemp[0];
        }
        $arrDataRows =null;//回收内存
        krsort($arrSortRows);//根据键名 降序排列
        $sortFileData = implode("\n",$arrSortRows);
        file_put_contents(DATAPATH.'/'.self::$strShenTieBangSortedPathName,$sortFileData);
    }

    /**
     * 神贴榜回复数据排序 tid / pid / 链接（楼层链接）/ 评论数（楼中楼）／content（回复后的）／点赞数（对楼层的点赞）／发帖时间（回复的发帖时间）
     * （回复数*5+点赞数）倒序
     */
    public function sortShenTieBangReplyData(){
        if(empty(self::$strShenTieBangReplyPathName) || empty(self::$strShenTieBangReplySortedPathName)){
            return false;
        }
        $fileContent = file_get_contents(DATAPATH.'/'.self::$strShenTieBangReplyPathName);
        if(empty($fileContent)){
            return false;
        }
        $arrDataRows = explode("\n", $fileContent);
        $arrDataRows = array_filter($arrDataRows);
        $arrSortRows = array();
        foreach ($arrDataRows as $strValue){
            $arrTemp = explode("\t",$strValue);
            if(count($arrTemp) != 7) { continue;}
            $arrTemp[6] = date('Ymd-H:i:s',$arrTemp[6]);
            $strValue = implode("\t",$arrTemp);
            $key = $arrTemp[3] * 5 + $arrTemp[5];
            $arrSortRows[$key] = $strValue;
        }
        $arrDataRows =null;
        krsort($arrSortRows);
        $sortFileData = implode("\n",$arrSortRows);
        file_put_contents(DATAPATH.'/'.self::$strShenTieBangReplySortedPathName,$sortFileData);
    }

    /**
     * 首页原创数据过滤+排序  ctr倒序
     *tid／title / 链接（只看楼主）／首页曝光 / 首页点击 / ctr /
     * @return bool
     */
    public function sortShouYeYuanChuangData(){
        if(empty(self::$strShouYeOriginaRowDatalPathName) || empty(self::$strShouYeOriginaDatalSortedPathName)){
            return false;
        }
        $fileContent = file_get_contents(DATAPATH.'/'.self::$strShouYeOriginaRowDatalPathName);
        if(empty($fileContent)){
            return false;
        }
        //过滤
        $arrDataRows = explode("\n", $fileContent);
        $arrDataRows = array_filter($arrDataRows);
        $arrSortRows = array();
        foreach ($arrDataRows as $strValue){
            $arrTemp = explode("\t",$strValue);
            if(count($arrTemp) != 6){ continue;}
            $key = $arrTemp[0];
            $arrSortRows[$key] = $strValue;
        }
        $arrDataRows = $arrSortRows;
        $arrSortRows = array();
        foreach (self::$arrShenTieBangTidList as $tid){
            if(array_key_exists($tid,$arrDataRows)){
                unset($arrDataRows[$tid]);
            }
        }
        //排序
        foreach ($arrDataRows as $key => $strValue){
            $arrTemp = explode("\t",$strValue);
            $key = $arrTemp[5];//ctr
            $arrSortRows[$key] = $strValue;

        }
        krsort($arrSortRows);
        $sortFileData = implode("\n",$arrSortRows);
        $arrDataRows =null;
        file_put_contents(DATAPATH.'/'.self::$strShouYeOriginaDatalSortedPathName,$sortFileData);
    }
}
