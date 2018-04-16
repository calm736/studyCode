<?php
/**
 * 检测帖子是否原创
 */

define('EASYSCRIPT_DEBUG', false);          //debug 模式
define('EASYSCRIPT_THROW_EXEPTION', true);  //抛异常模式

define('ROOT_PATH', dirname(__FILE__) . '/../../../../');
define('SCRIPTNAME', basename(__FILE__, ".php"));   //定义脚本名
define('BASEPATH', dirname(__FILE__));
define('CONFPATH', BASEPATH . "/conf");
define('DATAPATH', BASEPATH . "/data");
define('LOGPATH', "./log/content/script/deliveryplatform");
define('IS_ORP_RUNTIME', true);
set_include_path(get_include_path() . PATH_SEPARATOR . BASEPATH . '/../../');
//require_once ROOT_PATH . "app/common/script/grab/lib/util.php";

/**
 * 获取当前毫秒级时间戳
 * @param：null
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
function outLogLine($strMsg, $type = 'w', $bolEcho = false, $bolLineInfo = true)
{
	$strFileName = '';
	$intLineNo = 0;
	if ($bolLineInfo) {
		if (defined('DEBUG_BACKTRACE_IGNORE_ARGS')) {
			$arrTrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
		} else {
			$arrTrace = debug_backtrace();
		}
		if (!empty($arrTrace)) {
			$strFileName = basename($arrTrace[0]['file']);
			$intLineNo = intval($arrTrace[0]['line']);
			//$strMsg = $strFileName . ":Line($intLineNo):" . $strMsg;
		}
	}
	if ('n' == $type) {
		Bingo_Log::notice($strMsg, '', $strFileName, $intLineNo);
	} else if ('w' == $type) {
		Bingo_Log::warning($strMsg, '', $strFileName, $intLineNo);
	} else if ('f' == $type) {
		Bingo_Log::fatal($strMsg, '', $strFileName, $intLineNo);
	} else {
		Bingo_Log::warning($strMsg, '', $strFileName, $intLineNo);
	}
	if ($bolEcho) {
		$strLogPrefix = '';
		if(!empty($strFileName)){
			$strLogPrefix = $strFileName;
		}
		if(0 < $intLineNo){
			$strLogPrefix = $strFileName.'['.$intLineNo.']';
		}
		if(!empty($strLogPrefix)){
			$strMsg = $strLogPrefix.' '.$strMsg;
		}
		echo $strMsg . PHP_EOL;
	}
}
$parseType = 1;
$useNewToolFlag = 0;
$judgePicNum = 0;
$dataMultiCallFlag = 0;
$arrCfgList = array();
$putNum = 30;
$manualSiteTab = '';
$dlvryModel = 1;
if(!empty($argv) && 2 <= count($argv)){
	$parseType = intval($argv[1]);
	$useNewToolFlag = intval($argv[2]);
	$judgePicNum = intval($argv[3]);
	$dataMultiCallFlag = intval($argv[4]);
	$arrCfgList = explode(',', $strCfglist);
	isset($argv[2])&&!empty($argv[2]) ? $putNum = intval($argv[2]) : 0;
	isset($argv[3])&&!empty($argv[3])?$manualSiteTab = strval($argv[3]):0;
	isset($argv[4])&&!empty($argv[4])?$dlvryModel = intval($argv[4]):1;
}else{
	outLogLine('tieba_detectThreadOriginal run fail.invalid param:['.var_export($argv, true).']', 'w', true);
	//exit(0);
}


define('LOG', 'log');
Bingo_Log::init(array(
	LOG => array(
		'file' => LOGPATH . "/" . SCRIPTNAME . ".log",
		'level' => 0x0f,
	),
), LOG);

$easyScriptObj = false;
try {
	outLogLine('tieba_detectThreadOriginal script ready run.', 'n', true);
	$ScriptConf = array(
		'memory_limit' => '1024M',
		'data_path' => DATAPATH,
		'conf_path' => CONFPATH,
		'lock_file_name' => SCRIPTNAME . '.lock',
		'done_file_name' => SCRIPTNAME . '.done',
		'db_alias' => array(
			'im' => 'forum_content',
		),
		'conf_alias' => array(
			'main' => 'main.conf',
		),
	);

	$easyScriptObj = new Util_EasyScript($ScriptConf);
	//防止脚本重复执行
	/*if( $easyScriptObj->checkScriptIsRuning() === true ){
	outLogLine('tieba_detectThreadOriginal is runing.');
	exit;
	}*/
	outLogLine('tieba_detectThreadOriginal script begin runing.', 'n', true);

	$deliveryGrab = new detectThreadOriginalAction();
	$deliveryGrab->execute($parseType, $useNewToolFlag, $judgePicNum, $dataMultiCallFlag);

	$easyScriptObj->runSuccess();
	outLogLine('tieba_detectThreadOriginal script run success.', 'n', true);
} catch (Exception $e) {
	if ($easyScriptObj !== false) {
		outLogLine('tieba_detectThreadOriginal script run fail!' . $easyScriptObj->getErr2String(), 'w', true);
	} else {
		outLogLine('tieba_detectThreadOriginal script run fail![null]', 'w', true);
	}
	outLogLine('tieba_detectThreadOriginal run fail.' . $easyScriptObj->getErr2String(), 'w', true);
	exit;
}
outLogLine('tieba_detectThreadOriginal run finish.', 'n', true);

class detectThreadOriginalAction{
	const THREAD_URL_PREFIX = 'https://tieba.baidu.com/p/';
	
	public static function execute($parseType = 1, $useNewToolFlag = 0, $judgePicNum = 0, $dataMultiCallFlag = 0){
		
		$fileDir = DATAPATH.'/tofeed';
		$arrFilePath = array();
		$sumThreadCount = array();
		self::_traverseDir($fileDir, $arrFilePath);
        //var_dump($arrFilePath);var_dump('=================');exit(1);
		foreach ($arrFilePath as $filePath){
			//读取数据文件
			$arrFTPDataRows = array();
			outLogLine('current process file name:['.$filePath.']', 'n', true);
			self::_getFTPDataFromFile($filePath, $arrFTPDataRows);
			outLogLine('from file get data row num:['.count($arrFTPDataRows).']', 'n', true);
			if(empty($arrFTPDataRows)){
				outLogLine('current process file name:['.$filePath.'] no get data');
				continue;
			}
			$sumThreadCount['original_file_row_num'] += count($arrFTPDataRows);
			
			//解析文件数据内容
			$arrThreadUrlList = array();
			self::_parseFileContent($arrFTPDataRows, $parseType, $arrThreadUrlList);
			$sumThreadCount['original_tid_count'] += count($arrThreadUrlList);
			outLogLine('from file get thread url count:['.count($arrThreadUrlList).']', 'n', true);
			if(empty($arrThreadUrlList)){
				continue;
			}
			//过滤帖子内容字数少于300的帖子
			if(1 == $dataMultiCallFlag){
				$arrThreadDataList = array();
				self::_multiGetThreadData($arrThreadUrlList, $arrThreadDataList);
			}
			foreach ($arrThreadUrlList as $tid => $tUrl){
				if($tid <= 0 || empty($tUrl)){
					continue;
				}
				$postInfos = null;
				if(1 == $dataMultiCallFlag && !empty($arrThreadDataList)){
					$postInfos = $arrThreadDataList[$tid];
				}else{
					$postInfos = self::_getPostInfoByTid($tid);
				}
				if(null == $postInfos || empty($postInfos)){
					unset($arrThreadUrlList[$tid]);
					continue;
				}
				$onePostInfo=$postInfos['post_infos'][0];
				if(empty($onePostInfo['content'])){
					unset($arrThreadUrlList[$tid]);
					continue;
				}
				$arrResult = self::_getWordsAndPicsCnt($onePostInfo['content']);
				if($arrResult['wordsCnt'] <= 300){
					unset($arrThreadUrlList[$tid]);
					continue;
				}
				if(1 == $judgePicNum){
					if($arrResult['picCnt'] < 3){
						unset($arrThreadUrlList[$tid]);
						continue;
					}
				}
			}
			
			$sumThreadCount['one_filter_count'] += count($arrThreadUrlList);
			outLogLine('take off delete and wordCnt > 300 thread url count:['.count($arrThreadUrlList).']', 'n', true);
			if(empty($arrThreadUrlList)){
				continue;
			}
			//检测是否是抓取的数据
			$arrTids = array_keys($arrThreadUrlList);
			$arrTids = array_filter($arrTids);
			$arTids = array_unique($arrTids);
			$arrDetectResult = array();
			$arrTidChunks = array_chunk($arrTids, 50);
			outLogLine('thread url chunk count:['.count($arrTidChunks).']', 'n', true);
			foreach ($arrTidChunks as $arrCk){
				$arrDlvryResult = self::_getDlvryResultByTids($arrCk);
				if(empty($arrDlvryResult)){
					continue;
				}
				foreach ($arrCk as $tid){
					if(isset($arrDlvryResult[$tid]) && !empty($arrDlvryResult[$tid])){
						unset($arrThreadUrlList[$tid]);
						continue;
					}
				}
			}
			$sumThreadCount['two_filter_count'] += count($arrThreadUrlList);
			//检测是否原创
			$arrThreadUrlListChunks = array_chunk($arrThreadUrlList, 50, true);
			foreach ($arrThreadUrlListChunks as $arrUrlCk){
				$arrUrlListCk = array_values($arrUrlCk);
				if(empty($arrUrlListCk)){
					continue;
				}
				if(1 == $useNewToolFlag){
					$arrDetCkResult = self::_detectThreadIsOriginalNew($arrUrlListCk);
				}else{
					$arrDetCkResult = self::_detectThreadIsOriginal($arrUrlListCk);
				}
				if(!empty($arrDetCkResult)){
					$arrDetectResult += $arrDetCkResult;
				}
				usleep(1000000);
			}
			$sumThreadCount['detect_count'] += count($arrDetectResult);
			outLogLine('get detect result count:['.count($arrDetectResult).']', 'n', true);
			if(empty($arrDetectResult)){
				continue;
			}
			//打印或其他处理返回结果
			$arrOriginalThread = array();
			$arrNotOriginalTid = array();
			$saveData = '';
			foreach ($arrDetectResult as $tid => $arrInfo){
				
				if(1 == $useNewToolFlag){
					if('OK' == $arrInfo['detect_info'][1]
							&& (1 == $arrInfo['detect_info'][3]
									|| (2 == $arrInfo['detect_info'][3] && 'True' == $arrInfo['detect_info'][4]))
					){
						$arrOriginalThread[$tid] = $arrInfo;
						$tmp = $tid.' '.$arrInfo['detect_info'][13].' '.$arrInfo['url'];
						empty($saveData)?$saveData = $tmp:$saveData .= ("\n".$tmp);
					}else{
						$arrNotOriginalTid[] = $tid;
					}
				}else{
					if('OK' == $arrInfo['detect_info'][1] && 12 <= intval($arrInfo['detect_info'][13])){
						$arrOriginalThread[$tid] = $arrInfo;
						$tmp = $tid.' '.$arrInfo['detect_info'][13].' '.$arrInfo['url'];
						empty($saveData)?$saveData = $tmp:$saveData .= ("\n".$tmp);
					}else{
						$arrNotOriginalTid[] = $tid;
					}
				}
			}
			$sumThreadCount['thread_original_count'] += count($arrOriginalThread);
			outLogLine('this thread ir original result count:['.count($arrOriginalThread).']', 'n', true);
			if(!empty($saveData)){
				self::_saveDataToFile($saveData);
			}
			if(!empty($arrNotOriginalTid)){
				$saveNotOrgData = join("\n", $arrNotOriginalTid);
				self::_saveDataToFile($saveNotOrgData, DATAPATH.'/out_not_original_result.txt');
			}
		}
		outLogLine('this time run data out:['.var_export($sumThreadCount, true).']', 'n', true);
	}
	
	private static function _parseFileContent($arrFTPDataRows, $parseType, &$arrThreadUrlList){
		if(empty($arrFTPDataRows)){
			return false;
		}
		if(1 == $parseType){
			foreach($arrFTPDataRows as $strRow){
				if(empty($strRow)){
					continue;
				}
				$arrRow = explode("\t", $strRow);
				if(empty($arrRow) || intval($arrRow[0]) <= 0){
					continue;
				}
				$arrThreadUrlList[$arrRow[0]] = self::THREAD_URL_PREFIX.$arrRow[0];
			}
		}else if(2 == $parseType){
			foreach ($arrFTPDataRows as $strRow){
				if(empty($strRow)){
					continue;
				}
				$isMatched = preg_match('/-?[1-9]\d*/', $strRow, $matches);
				if(1 == $isMatched && !empty($matches) && 0 < $matches[0]){
					$arrThreadUrlList[$matches[0]] = 'https://'.$strRow;
				}
			}
		}
		return true;
	}
	
	private static function _multiGetThreadData($arrThreadUrlList, &$arrThreadDataList){
		if(empty($arrThreadUrlList)){
			return false;
		}
		$arrInput = array (
			//'thread_id' => $tid,
			'res_num' => 1,
			'offset' => 0,
			'see_author' => 1,
			'has_comment' => 0,
			'has_mask' => 0,
			'has_ext' => 0,
			'structured_content' => 1,
		);
		$arrReqParamsByIndex = array();
		foreach ($arrThreadUrlList as $tid => $tUrl){
			$arrInput['thread_id'] = $tid;
			$arrReqParam = array();
			$arrReqParam['serviceName'] = 'post';
			$arrReqParam['method'] = 'getPostsByThreadId';
			$arrReqParam['input'] = $arrInput;
			$arrReqParam['ie'] = 'utf-8';
			$arrReqParamsByIndex[$tid] = $arrReqParam;
		}
		if(empty($arrReqParamsByIndex)){
			return false;
		}
		$arrOutList = Util_BatchMulticall::multiCallService($arrReqParamsByIndex, 'batch_detect_thread_data_get', 100);
		foreach ($arrOutList as $tidIndex => $arrOut){
			if($arrOut === false ||$arrOut ['errno'] !== Tieba_Errcode::ERR_SUCCESS){
				continue;
			}
			$arrList = $arrOut ['output']['output'][0];
			if($arrList['is_thread_deleted'] ==1){
				continue;
			}
			$arrThreadDataList[$tidIndex] = $arrList;
		}
		return true;
	}
	
	private static function _saveDataToFile($saveData, $filePath = ''){
		if(empty($saveData)){
			return false;
		}
		if(empty($filePath)){
			$filePath = DATAPATH.'/out_original_result.txt';
		}
		$saveData .= "\n";
		$ret = file_put_contents($filePath, $saveData, FILE_APPEND);
		return ret;
	}
	
	private static function _detectThreadIsOriginalNew($arrUrlList){
		if(empty($arrUrlList)){
			return null;
		}
		$tmpPath = DATAPATH.'/tmp_url_list.txt';
		$tmpSave = join("\n", $arrUrlList);
		if(false === file_put_contents($tmpPath, $tmpSave)){
			unlink($tmpPath);
			return null;
		}
		$tmpOutPath = DATAPATH.'/tmp_out.txt';
		// > '.$outFilePath.' 2>/dev/null
		$cmd = 'cat '.$tmpPath.' | '.BASEPATH.'/origin_detect/ror_client_new | grep -r \'tieba\' > '.$tmpOutPath.' 2>/dev/null';
		$out = system($cmd, $retval);
		$arrRet = array();
		$out = file_get_contents($tmpOutPath);
		if(false !== $out && !empty($out)){
			$arrOutRows = explode("\n", $out);
			foreach ($arrOutRows as $row){
				$arrRow = explode("\t", $row);
				if(empty($arrRow)){
					continue;
				}
				$url = $arrRow[0];
				$isMatched = preg_match('/-?[1-9]\d*/', $url, $matches);
				if(1 == $isMatched && !empty($matches) && 0 < $matches[0]){
					$arrRet[$matches[0]] = array(
						'tid' => $matches[0],
						'url' => $url,
						'detect_info' => $arrRow,
					);
				}
			}
		}
		unlink($tmpPath);
		unlink($tmpOutPath);
		return $arrRet;
	}
	
	private static function _detectThreadIsOriginal($arrUrlList){
		if(empty($arrUrlList)){
			return null;
		}
		$tmpPath = DATAPATH.'/tmp_url_list.txt';
		$tmpSave = join("\n", $arrUrlList);
		if(false === file_put_contents($tmpPath, $tmpSave)){
			unlink($tmpPath);
			return null;
		}
		$tmpOutPath = DATAPATH.'/tmp_out.txt';
		// > '.$outFilePath.' 2>/dev/null
		$cmd = 'cat '.$tmpPath.' | '.BASEPATH.'/origin_detect/ror_client -c '.BASEPATH.'/origin_detect/client.conf | grep -r \'tieba\' > '.$tmpOutPath.' 2>/dev/null';
		$out = system($cmd, $retval);
		$arrRet = array();
		$out = file_get_contents($tmpOutPath);
		if(false !== $out && !empty($out)){
			$arrOutRows = explode("\n", $out);
			foreach ($arrOutRows as $row){
				$arrRow = explode("\t", $row);
				if(empty($arrRow)){
					continue;
				}
				$url = $arrRow[0];
				$isMatched = preg_match('/-?[1-9]\d*/', $url, $matches);
				if(1 == $isMatched && !empty($matches) && 0 < $matches[0]){
					$arrRet[$matches[0]] = array(
						'tid' => $matches[0],
						'url' => $url,
						'detect_info' => $arrRow,
					);
				}
			}
		}
		unlink($tmpPath);
		unlink($tmpOutPath);
		return $arrRet;
	}
	
	private static function _getFTPDataFromFile($filePath, &$arrFTPDataRows){
		if(empty($filePath) || !file_exists($filePath)){
			return false;
		}
		$fileContent = file_get_contents($filePath);
		if(empty($fileContent)){
			return false;
		}
		$arrFTPDataRows = explode("\n", $fileContent);
		$arrFTPDataRows = array_filter($arrFTPDataRows);
		return true;
	}
	
	private static function _traverseDir($filedir, &$arrFilePath) {
		// 打开目录
		$dir = @dir ( $filedir );
		// 列出目录中的文件
		while ( ($file = $dir->read ()) !== false ) {
			if (is_dir ( $filedir . "/" . $file ) && ($file != ".") && ($file != "..")) {
				// 递归遍历子目录
				self::_traverseDir ( $filedir . "/" . $file, $arrFilePath );
			} if(!is_dir ( $filedir . "/" . $file ) && 0 === strpos($file, '.')){
				//隐藏文件
			}else {
				// 输出文件完整路径
				$filePath = $filedir . "/" . $file;
				$arrFilePath [] = $filePath;
			}
		}
		$dir->close ();
	}
	
	
	
	private static function _getDlvryResultByTids($arrTids){
		if(empty($arrTids)){
			return null;
		}
		$arrInput = array(
			'thread_id' => $arrTids,
			'ignore_limit' => 1,
			'not_need_append_field' => 1,
		);
		$arrOut = Tieba_Service::call('content', 'getDlvryResultByCond', $arrInput, null, null, 'post', 'php', 'utf-8');
		if(false == $arrOut || !isset($arrOut['errno'])
				|| Tieba_Errcode::ERR_SUCCESS != $arrOut['errno']){
			return null;
		}
		$arrRet = array();
		foreach ($arrOut['data'] as $arrItem){
			if($arrItem['thread_id'] <= 0){
				continue;
			}
			$arrRet[$arrItem['thread_id']] = $arrItem;
		}
		return $arrRet;
	}
	
	private static function _getWordsAndPicsCnt($arrContents){
		$arrResult = array('wordsCnt'=>0,'picCnt'=>0);
		if (empty($arrContents)) {
			return $arrResult;
		}
		$title = '';
		$strWords = '';
		foreach ($arrContents as $arrContent) {
			if(empty($arrContent['tag'])){
				continue;
			}
			if('img' == $arrContent['tag'] 
					&& 'BDE_Image' == $arrContent['class']
					&& !empty($arrContent['src'])){
				$arrResult['picCnt'] += 1;
			}
			if('plainText' == $arrContent['tag'] && !empty($arrContent['value'])){
				$strWords .= $arrContent['value'];
			}
			
		}
		$arrResult['wordsCnt'] = self::utf8_strlen($strWords);
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
	
	private static function _getPostInfoByTid($tid,$serviceName = 'getPostsByThreadId'){
		if($tid <= 0){
			return null;
		}
		$arrInput = array (
			'thread_id' => $tid,
			'res_num' => 1,
			'offset' => 0,
			'see_author' => 1,
			'has_comment' => 0,
			'has_mask' => 0,
			'has_ext' => 0,
			'structured_content' => 1,
		);
		$arrOutput = Tieba_Service::call ( 'post', $serviceName, $arrInput, null, null, 'post', 'php', 'utf-8');
		if ($arrOutput === false ||$arrOutput ['errno'] !== Tieba_Errcode::ERR_SUCCESS) {
			outLogLine( sprintf ( 'service error: %s_%s [%s] [%s]', 'post', $serviceName, serialize ( $arrInput ), serialize ( $arrOutput ) ) );
			return null;
		}
		$arrList = $arrOutput ['output'] ['output'] [0];
		if($arrList['is_thread_deleted'] ==1){
			return null;
		}
		return $arrList;
	}
}
