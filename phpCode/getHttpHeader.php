<?php
echo get('http://www.baidu.com');exit;
function get($url) {
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_HTTPGET, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //TRUE ½«curl_exec()»ñÄÅ¢Ò×·�����»أ¬¶øֱ½Óä¡£

    $header = array('User-Agent: Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.101 Safari/537.36'); //ÉÖһ¸öÄ¯ÀÆagentµÄeader
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

    curl_setopt($ch, CURLOPT_HEADER, 1); //·µ»Øesponseͷ²¿ÐϢ
    curl_setopt($ch, CURLINFO_HEADER_OUT, true); //TRUE ʱ׷×¾äµÄë×·�����´ÓPHP 5.1.3 ¿ªʼ¿Éá£Õ¸öؼüÇÊí²鿴ÇÇheader

    curl_setopt($ch, CURLOPT_URL, $url);
    $result = curl_exec($ch);

    echo curl_getinfo($ch, CURLINFO_HEADER_OUT); //¹ٷ½ÎµµÃÊÊ¡°·¢ËÇÇµÄַ�����£¬Æʵ¾ÍÇëµÄeader¡£Õ¸öÇ±½Ӳ鿴ÇÇheader£¬ÒΪÉÃÔÐ²鿴

    curl_close($ch);

    return $result;
}
