<?
/*阳光宽频网视频抓取 需提供正确的video_id*/
$url = _getRequestSourceUrl('21c8a5b5fec9439fb688e8ca369e6c2f');
$url = 'http://v3-tt.ixigua.com/4f0651792bf137f91f4992485ff4a777/5ad68401/video/m/2208f21df3d42714a58ac8a1a72737093b11155b20f0000aa889dc66315/';//video_url 下载视频
 $arrRes = fetchUrlGet($url);//对main_ulr base_64解码
var_dump($arrRes);
function fetchUrlGet(
                $url,
                $requestMethod = 'GET',
                $arrProxy = array(
                        'ip' => '10.208.6.103',
                        'port' => 8080,
                        ),
                $referer = 'http://www.365yg.com/',
                $connect_timeout = 10000,
                $run_timeout = 60,
                $host = '',
                $arrAppendHeaders = array(),
                $CA = false){
        $arrPostData = array(
                        'url' => urlencode($url),
                        );
        $arrHeaders = array();
        if(!empty($host)){
                $arrHeaders[] = 'Host: '.$host;
        }
        if(!empty($arrAppendHeaders)){
                $arrHeaders = array_merge($arrHeaders, $arrAppendHeaders);
        }
        $cacert = getcwd() . '/cacert.pem'; //CA根证书
        $SSL = substr($url, 0, 8) == "https://" ? true : false;

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
       // curl_setopt($curl, CURLOPT_HEADER, 1);
        //curl_setopt($curl, CURLINFO_HEADER_OUT, true);

        if('GET' == $requestMethod){
                curl_setopt($curl, CURLOPT_HTTPGET, true);
        }else if('POST' == $requestMethod){
                curl_setopt($curl, CURLOPT_POST, true);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $arrPostData);
        }else{
                curl_setopt($curl, CURLOPT_HTTPGET, true);
        }
        if(!empty($arrHeaders)){
                curl_setopt($curl, CURLOPT_HTTPHEADER, $arrHeaders);
        }
        curl_setopt($curl,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION,1);

        if(null != $arrProxy && !empty($arrProxy) && isset($arrProxy['ip']) && isset($arrProxy['port'])){
                curl_setopt($curl, CURLOPT_PROXY, $arrProxy['ip']); //代理服务器地址
                curl_setopt($curl, CURLOPT_PROXYPORT, $arrProxy['port']); //代理服务器端口
        }
        if(null != $referer && !empty($referer)){
                curl_setopt($curl, CURLOPT_REFERER, $referer);
        }

        if ($SSL && $CA) {
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);   // 只信任CA颁布的证书
                curl_setopt($curl, CURLOPT_CAINFO, $cacert); // CA根证书（用来验证的网站证书是否是CA颁布）
                curl_setopt($curl, CURLOPT_SSLCERTTYPE, 'PEM');
                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2); // 检查证书中是否设置域名，并且是否与提供的主机名匹配
        } else if ($SSL && !$CA) {
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // 信任任何证书
                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 1); // 检查证书中是否设置域名
        }else{
                curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,0);
                curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,0);
        }

        if(null != $connect_timeout && is_numeric($connect_timeout) && 0 < $connect_timeout){
                curl_setopt($curl, CURLOPT_CONNECTTIMEOUT_MS, $connect_timeout);
        }

        if(null != $run_timeout && is_numeric($run_timeout) && 0 < $run_timeout){
                curl_setopt($curl, CURLOPT_TIMEOUT, $run_timeout);
        }
        $output = curl_exec($curl);
        echo $output;
        //var_dump($output);
        $httpCode  = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        //$strOut= curl_multi_getcontent($curl);
        $errmsg = curl_error($curl);
        //var_dump($errmsg);
        $errno = curl_errno($curl);
        curl_close($curl);

        if($errno){
                Bingo_Log::warning('fetchUrlGet call fail.errno:['.$errno.'] errmsg:['.$errmsg.'] output:['.$output.']');
        }

        if(200 <= $httpCode && $httpCode<300) {
                //成功
                $arrRet = array(
                                'errno' => 0,
                                'errmsg' => 'success',
                                'data' => $output,
                               );
                return $arrRet;
        }else{
                //失败
                $arrRet = array(
                                'errno'    => $errno,
                                'errmsg' => $errmsg, //$code,
                                'data'    => array(
                                        'http_code' => $httpCode,
                                        'errmsg' => $errmsg,
                                        'output' => $output,
                                        ),
                               );
                return $arrRet;
        }
        return $arrRet;
}

function getHttpHeader($rawData){
        $arrRawRespondeData = explode("\r\n",$rawData);
        //区分状态行 http头 数据
        $arrRespondeData = array();
        foreach($arrRawRespondeData as $key =>$lineData){
            if( intval($key) === 0){
                if(strpos($lineData,'HTTP') === false){
                    return null;
                }
                $arrRespondeData['http_status_info'] = $lineData;
            } else if(strlen($lineData) == 0){//空行表示 一下是返回数据
                $arrRespondeData['body'] = $arrRawRespondeData[$key+1];
            }else{ //处理响应头
                $arrHeaderKeyAndValue = explode(':',$lineData);
                $arrRespondeData['head'][$arrHeaderKeyAndValue[0]] = $arrHeaderKeyAndValue[1];
            }
        }
        return $arrRespondeData;
}
function _getRequestSourceUrl($videoId,$uri='/video/urls/v/1/toutiao/mp4/',$host='http://ib.365yg.com'){
        if(empty($videoId)){
            Bingo_Log::warning('source videoId is empty!');
            return '';
        }
        $strRandNum = rand(99999999999999,1000000000000000);
        $param = $uri.$videoId.'?r='.$strRandNum;
        $s = _CRC32($param);
        $url = $host.$param.'&s='. $s;
        return $url;
    }

function _CRC32($str){
        $num = crc32($str);
        $strNum = sprintf("%u",$num);//对crc32算法得到的数值 一定要转换为无符号类型 否则得到的值不正确
        return strval($strNum);
}
