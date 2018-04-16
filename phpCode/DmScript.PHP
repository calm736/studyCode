<?php
/**
 * 价值输出数据挖掘
 * Created by PhpStorm.
 * User: lilinke
 * Date: 2018/4/12
 * Time: 16:32
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
    outLogLine('DmScript  script get invaild input param.argv:['.serialize($argv).']', 'w');
}

$strScriptName = 'DmScript';
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
    outLogLine('DmScript ready run.', 'n', true);
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
        outLogLine('DmScript is runing.', 'n', true);
        exit;
    }
    outLogLine('DmScript begin runing.', 'n', true);

    $getObj = new DmScriptAction();
    $getObj->_execute();
    $easyScriptObj->runSuccess();
    outLogLine('DmScript run success.', 'n', true);
}catch(Exception $e){
    if($easyScriptObj !== false){
        outLogLine('DmScript run fail!'.$easyScriptObj->getErr2String(), 'w');
    }else{
        outLogLine('DmScript run fail![null]', 'w');
    }
    outLogLine('DmScript fail.'.$easyScriptObj->getErr2String());
    exit;
}
outLogLine('DmScript finish.', 'n', true);


class DmScriptAction{

    public static $fpShenTieBang=null;
    public static $strShenTieBangPathName = 'shenTieBangData';
    public static $fpShenTieBangReply=null;
    public static $strShenTieBangReplyPathName = 'shenTieBangReplyData';
    public static $fpShouYeOriginalRowData=null;//未过滤
    public static $strShouYeOriginaRowDatalPathName = 'shouYeOriginalRowData';//未过滤
    public static $fpShouYeOriginal=null;
    public static $strShouYeOriginalPathName = 'shouYeOriginalData';
    public static $arrLineRowData = array();
    public static $arrFiltrateWordList = array('咨询','求助','广告','销售','推广','扣扣',
        '加微信', 'vx','活动','盖楼','顶帖','点赞','转发','投票','镇楼','更新',);
    public static $intStandardTime = 0;
    public static function  _execute(){
        self::$intStandardTime = strtotime(date('Ymd',strtotime('-2 day')));
        $fileName = 'thread_top_'.date('Ymd',strtotime('-2 day'));
        $cmd= ' wget ftp://cp01-forum-dm01-05.cp01.baidu.com/home/forum/tbdc/data/tieba_mds_recommend_thread_ctr_day/'.$fileName;
        exec($cmd);
        $tmpFile = fopen('./'.$fileName ,'r');
        if ($tmpFile == false){
            return 0;
        }
        while (!feof($tmpFile)){
            $arrRowData = array();
            $line = trim(fgets($tmpFile));
            $arrLine = explode("\t", $line);
            if(empty($line) || count($arrLine) != 7){
                outLogLine('error line'.'w');
                continue;
            }
            $arrRowData['tid'] = $arrLine[0];//thread_id
            $arrRowData['exp_cnt'] = $arrLine[4];//总曝光次数
            $arrRowData['click_cnt'] = $arrLine[5];//总点击次数
            $arrRowData['ctr'] = $arrLine[6];//CTR
            self::getPostData($arrRowData);
        }
    }

    public static function getPostData($arrRowData){
        if(empty($arrRowData) || count($arrRowData) != 4){
            outLogLine('getShenTieBangDataAndReplyData:err Input:['.serialize($arrRowData).']');
            return false;
        }
        $intThreadId = $arrRowData['tid'];
        if(self::$fpShenTieBang == null){self::$fpShenTieBang = fopen(DATAPATH.'/'.self::$strShenTieBangPathName,'w+') or die("open ".self::$strShenTieBangPathName." failed");}//神贴榜
        if(self::$fpShenTieBangReply == null){self::$fpShenTieBangReply = fopen(DATAPATH.'/'.self::$strShenTieBangReplyPathName,'w+')or die("open ".self::$strShenTieBangReplyPathName." failed");}//神贴榜回复
        if(self::$fpShouYeOriginalRowData == null){self::$fpShouYeOriginalRowData = fopen(DATAPATH.'/'.self::$strShouYeOriginaRowDatalPathName,'w+')or die("open ".self::$strShouYeOriginaRowDatalPathName." failed");}//首页也原创未过滤
        $intOffset = 0;
        $LastPostId = 0;
        $IntreTryCnt = 5;
        $bolIsGetDataOver = false;
        $arrFirstLouMes = array();//主楼的相关信息
        $arrGLastPostInfo = array();
        while($IntreTryCnt > 0){
            usleep(10);
            $arrInput = array (
                'thread_id' => $intThreadId,
                'res_num' => 90,
                'offset' => $intOffset,
                'see_author' => 0,
                'has_comment' => 1,
                'has_mask' => 0,
                'has_ext' => 1,
                'need_set_pv' => 1,
                'structured_content' => 1,
            );
            $arrOutput = Tieba_Service::call ( 'post', 'getPostsByThreadId', $arrInput, null, null, 'post', 'php', 'utf-8');
            if (false == $arrOutput || !isset($arrOutput['errno']) || 0 != $arrOutput['errno']){
                $IntreTryCnt --;
                continue;
            }
            if($arrOutput['output']['output'][0]['is_thread_deleted'] == 1){
                $bolIsGetDataOver = true;
                outLogLine('has deleted arrInput:['.serialize($arrInput).'] output:['.serialize($arrOutput).']');
                break;
            }
            if(empty($arrOutput['output']['output'][0]['post_infos'])) {$bolIsGetDataOver=true;break;}
            $intCount = count($arrOutput['output']['output'][0]['post_infos']);
            $arrLastPostInfo = $arrOutput['output']['output'][0]['post_infos'][$intCount -1];
            if(intval($arrLastPostInfo['post_id']) == $LastPostId){
                $bolIsGetDataOver = true;
                break;
            }
            $IntreTryCnt = 5;
            foreach($arrOutput['output']['output'][0]['post_infos'] as $arrPostInfo){
                $arrPostInfo['title'] = str_replace("\n",'',$arrPostInfo['title']);
                $intOffset++;
                $LastPostId = intval($arrPostInfo['post_id']);
                $arrPostInfo['total_post_num'] = intval($arrOutput['output']['output'][0]['total_post_num']);//主题帖总回复数
                $arrPostInfo['valid_post_num'] = $arrOutput['output']['output'][0]['valid_post_num'];//主题帖有效数
                $arrPostInfo['exp_cnt'] = $arrRowData['exp_cnt'];// 主题贴总曝光次数
                $arrPostInfo['click_cnt'] = $arrRowData['click_cnt'];//主题贴总点击次数
                $arrPostInfo['ctr'] = $arrRowData['ctr'];//主题贴CTR
                $arrGLastPostInfo = $arrPostInfo;
                if($arrPostInfo['thread_type'] != 0){
                    $bolIsGetDataOver = true;
                    break;
                }
                if(self::bolHitCommonFiltrateStrategy($arrPostInfo,1)){
                    if($arrPostInfo['post_no'] > 1){ continue;}
                    $arrPostInfo['content'] = array();//首楼特殊对待 需要统计首楼相关信息
                }
                                self::getFirstLouData($arrPostInfo,$arrFirstLouMes);//同时神贴榜数据写入文件
                self::getShenTieBangReply($arrPostInfo,$arrFirstLouMes);
                self::getShouYeOrignelData($arrPostInfo,$arrFirstLouMes,$intOffset,$bolIsGetDataOver);
                //var_dump($arrPostInfo);
            }
        }
        if($IntreTryCnt == 0){
            outLogLine('call service failed');
            echo $intThreadId."\n";
            return true;
        }
        self::getShouYeOrignelData($arrGLastPostInfo,$arrFirstLouMes,$intOffset,true);
    }
    public static function getShouYeOrignelData($arrPostInfo,&$arrFirstLouMes,$offsetId,$bolIsGetDataOver){
        //拉取前七天的数据
        if($arrFirstLouMes['first_thread_create_time'] <= (self::$intStandardTime - 7*86400)){
            return;
        }
        if($bolIsGetDataOver['post_no'] > 1 && $bolIsGetDataOver == false && $offsetId <= 200){
            $arrPicAndWordsCnt = self::_getWordsAndPicsCnt($arrPostInfo['content']);
            //抓取楼主前200层带图文内容回复
            if($arrPicAndWordsCnt['wordsCnt'] > 0 && $arrFirstLouMes['picCnt'] > 0){
                $arrFirstLouMes['reply_pic_cnt'] += intval($arrFirstLouMes['reply_pic_cnt']);
            }
        }else if ($bolIsGetDataOver == true){//获取数据完毕
            //1.图片总数不满足10张，不抓取 2.主贴图片大于1张，主贴有图无文字内容不抓取；
            if(($arrFirstLouMes['picCnt']>1 && $arrFirstLouMes['wordsCnt'] <= 0)
                || ($arrFirstLouMes['reply_pic_cnt']+$arrFirstLouMes['picCnt']) < 10){
                return ;
            }
            $strThreadUrl = 'http://tieba.baidu.com/p/'.strval($arrPostInfo['thread_id']).'?see_lz=1';//只看楼主
            //tid/title / 链接（只看楼主）/首页曝光 / 首页点击 / ctr /
            $strShouYeOrignelMes = $arrPostInfo['thread_id']."\t".$arrFirstLouMes['title']."\t".$strThreadUrl."\t".$arrPostInfo['exp_cnt']."\t".$arrPostInfo['click_cnt']."\t".$arrPostInfo['ctr']."\n";
            echo $strShouYeOrignelMes;
            if(fwrite(self::$fpShouYeOriginalRowData,$strShouYeOrignelMes) === false){
                outLogLine('ShouYeYuanChuang:write file falied');
                echo $arrPostInfo['thread_id']."\n";
            }
        }
    }
    public static function getFirstLouData($arrPostInfo,&$arrFirstLouMes){
        if($arrPostInfo['post_no'] == 1){//首楼
            $arrFirstLouMes = self::_getWordsAndPicsCnt($arrPostInfo['content']);
            $arrFirstLouMes['title'] = $arrPostInfo['title'];
            $arrFirstLouMes['first_thread_create_time'] = $arrPostInfo['now_time'];//首楼的发帖时间
            //一天前数据
            if($arrFirstLouMes['wordsCnt'] >= 200 && $arrFirstLouMes['picCnt'] > 1
                && $arrFirstLouMes['first_thread_create_time'] >= self::$intStandardTime -86400){
                $strThreadUrl = 'http://tieba.baidu.com/p/'.$arrPostInfo['thread_id'];
                //tid / title / 链接（帖子链接）/ 首页曝光 / 首页点击 / ctr/总回复数
                $strShenTieBangMes = $arrPostInfo['thread_id']."\t".$arrPostInfo['title']."\t".$strThreadUrl."\t".$arrPostInfo['exp_cnt']."\t".$arrPostInfo['click_cnt']."\t".$arrPostInfo['ctr']."\t".$arrPostInfo['valid_post_num']."\n";
                echo $strShenTieBangMes;
                if(fwrite(self::$fpShenTieBang,$strShenTieBangMes) === false){
                    outLogLine('ShenTieBang: write file failed. strShenTieBangMes['.$strShenTieBangMes.']');
                    echo $arrPostInfo['thread_id']."\n";
                }
            }
        }
    }

    /**
     * 抓取回复贴
     * @param $arrPostInfo
     */
    public static function getShenTieBangReply($arrPostInfo,$arrFirstLouMes){
        //1. 回复中0点赞评论不抓取 2 总回复数小于20的不抓取
        //拉取前一天的数据
        if($arrFirstLouMes['first_thread_create_time'] <= (self::$intStandardTime-86400) || $arrPostInfo['post_no'] == 1
            ||$arrPostInfo['comment_num'] < 20 || ($arrPostInfo['agree_num'] == 0 && $arrPostInfo['comment_num'] ==0)){
            return;
        }
        $strReplyUrl = 'https://tieba.baidu.com/p/'.$arrPostInfo['thread_id'].'?pid='.$arrPostInfo['post_id'].'#'.$arrPostInfo['post_id'];
        $strContent = self::processContent($arrPostInfo['content']);
        $strContent = str_replace("\n",'',$strContent);
        //tid / pid / 链接（楼层链接）/ 评论数（楼中楼）／content（回复后的）／点赞数（对楼层的点赞）／发帖时间（回复的发帖时间）
        $strShenTieBangReply = $arrPostInfo['thread_id']."\t".$arrPostInfo['post_id']."\t".$strReplyUrl."\t".intval($arrPostInfo['comment_num'])."\t".$strContent."\t".intval($arrPostInfo['agree_num'])."\t".$arrPostInfo['now_time']."\n";
        echo $strShenTieBangReply;
        if(fwrite(self::$fpShenTieBangReply,$strShenTieBangReply) === false){
            outLogLine('ShenHuiFuBangHuiFu: write file failed. strShenTieBangReply:['.$strShenTieBangReply.']');
            echo $arrPostInfo['thread_id']."\n";
        }
    }
    /**
     *统计文字数和图片数
     * @param unknown $content
     * @param number $length
     * @return string
     */
    private static function _getWordsAndPicsCnt($arrContents)
    {
        $arrResult = array('wordsCnt'=>0,'picCnt'=>0);
        if (empty($arrContents)) {
            return $arrResult;
        }
        $strWords = '';
        foreach ($arrContents as $eleContent) {
            if($eleContent['tag'] == 'plainText'){
                $strWords .= $eleContent['value'];
            }else if($eleContent['tag'] == 'img' && $eleContent['class'] == 'BDE_Image'){
                $arrResult['picCnt'] =  $arrResult['picCnt'] + 1;
            }
        }
        $arrResult['wordsCnt'] = mb_strlen($strWords,'utf8');
        return $arrResult;
    }

    /**
     * @param null $string
     * @return int
     */
    public static function utf8_strlen($string = null) {
        // 将字符串分解为单元
        preg_match_all("/./us", $string, $match);
        // 返回单元个数
        return count($match[0]);
    }

    private function processContent($content){
        $strContent="";
        $isNeedNewline = false;//是否需要换行符
        foreach ($content as $value){
            if($value['tag']=='plainText'){
                if($isNeedNewline === true){
                    $strContent = $strContent."<br/>";
                }
                $strContent=$strContent.$value['value'];
                $isNeedNewline = true;
            }elseif ($value['tag']=='img' && ($value['class']=='BDE_Smiley' || $value['class']=='BDE_Image')){
                if($isNeedNewline === true){
                    $strContent = $strContent."<br/>";
                }
                $strContent = $strContent.'<img class="'.$value['class'].'" pic_type="'.$value['pic_type'].'" width="'.$value['width'].'" height="'.$value['height'].'" src="'.$value['src'].'" >';
                $isNeedNewline = true;
            }elseif($value['tag']=='br'){
                $isNeedNewline = false;
                $strContent = $strContent."<br/>";
            }
        }
        return $strContent;
    }

    /**
     * 是否命中过滤策略
     * @param $arrInfo
     * @return bool
     */
    public static function bolHitCommonFiltrateStrategy($arrInfo,$bolStructuredContent=0){
        if($arrInfo['thread_type'] != 0 ){//只抓主题帖
            return true;
        }
        //非结构化帖子内容
        $strContent = '';
        if($bolStructuredContent == 0){
            $strContent = $arrInfo['content'];
        }else if($bolStructuredContent == 1){
            foreach ($arrInfo['content'] as $arrItemValue){
                if($arrItemValue['tag'] == 'plainText'){
                    $strContent .= $arrItemValue['value'];
                }
            }
        }
        //var_dump($strContent);
        //通篇英文 没有汉字
        if(strlen($strContent) > 0 &&strlen($strContent) == mb_strlen($strContent,'utf8')){
           return true;
        }
        //命中过滤词汇
        foreach (self::$arrFiltrateWordList as $word){
            if(strpos($strContent,$word) !== false){ return true;}
        }
        return false;
    }
}
