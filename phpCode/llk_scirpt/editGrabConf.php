<?php
//http头
$appendHttpHeader = array(
                'Host' => 'photo.hupu.com',
                'Referer' => 'http://photo.hupu.com',
                );
//post_id 提取的方式
$arrPostIdRule = array('pattern' => "/([:?\.\/p])/",'id'=> 6);
//列表页抓取规则
$arrListObjectRule =array(
                'self_node' => array('class'=> 'piclist3','id' => 0),
                'next'=> array(
                        'self_node' => array('class' => 'pictable','id'=> 0),
                        'next'=>0
                        )
                );
$arrListItemNodeRule = array(
                'self_node' => array('tag'=> 'td','id' => 0),
                'child_node' => array( 'self_node'=>array('tag'=> 'a','class' => 'ku')),
                'next' => array(
                        'self_node' => array('tag'=>'a','class'=> 'ku' , 'id'=> 0),//id 表示属于父节点的第几个元素
                        'child_node' => array('tag' => 'img' , 'border' => 0),
                        'content_page_url' => 'href',
                        'next' => array(
                                'self_node' => array('tag' => 'img','id' => 0),
                                'child_node' => 0,
                                'cover_img_url' => 'src',
                                'next' => 0,
                            ),
                        ),
                );
/*$arrListItemNodeRule = array(
                'self_node' => array('tag' => 'tr'),
                'child_node' => array('tag' => 'td'),
                'next' => $arrListItemNodeRule,
                );*/
$arrListItemNodeRule1 = array(
                'self_node' => array('tag'=> 'td','id' => 0),
                'child_node' => array( 'self_node'=>array('tag'=> 'a','class')),
                'next' => array(
                        'self_node' => array('tag'=>'dl','id'=> 0),//id 表示属于父节点的第几个元素
                        'child_node' => array('tag' => 'dt'),
                        'next' => array(
                            'self_node' => array('tag' => 'dt' , 'id' => 0),
                            'child_node' => array('tag' => 'a', 'target' => '_blank',),
                            'next' => array(
                                    'self_node' => array('tag' => 'a','id' =>0),
                                    'child_node' => 0,
                                    'next' => 0,
                                    'title' => 'plaintext',
                                ),
                            ),
                        ),
                );
/*$arrListItemNodeRule1 = array(
                'self_node' => array('tag' => 'tr'),
                'child_node' => array('tag' => 'td'),
                'next' => $arrListItemNodeRule1,
                );*/
$arrListFerchNodeRule =array($arrListItemNodeRule,$arrListItemNodeRule1);
//详情页抓取规则
$arrContentListNodeRule = array('self_node' => array('class'=> 'tongbox','id' => 0),
                                'next'=> array(
                                        'self_node' => array('tag' => 'ul','id' => 0),
                                        'next' => 0,
                            ),
                        );
$txtNodeObjRule = array(
                'self_node' => array('tag' => 'p',),
                'child_node' => 0,
                'content_txt'=>'plaintext');
$picNodeObjRule = array(
                'self_node' => array('tag' => 'a'),
                'child_node' => array('self_node' => array('tag' => 'img', 'onload' => 'small_img_onload(this)')),//为了确定指定节点
                'next' => array(
                          'self_node' => array('tag' => 'img','onload' => 'small_img_onload(this)','id' => 0),
                          'content_img' => 'oksrc')
                );
$picNodeObjRule1 = array(
                'self_node' => array('tag' => 'center',),
                'child_node' => array('self_node' => array('tag' => 'img', 'class' => 'scrollLoading'),),
                'next' => array(
                    'self_node' =>array('tag' => 'img','class' => 'scrollLoading','id' => 0),
                    'content_img' => 'src')
                );
$arrContentFerchNodeRule = array(/*$txtNodeObjRule,*/$picNodeObjRule,/*$picNodeObjRule1*/);
$arrGrabRule = array(
        'list_tag' => 'td',//列表页轮询的tag
        'list_node'=>$arrListObjectRule,
        'list_item_nodes'=>$arrListFerchNodeRule,
        'content_tag' => 'a',//详情页轮询的tag
        'content_node'=>$arrContentListNodeRule,
        'content_item_nodes'=> $arrContentFerchNodeRule,
);
$deal_data_rule = array(
        'content_img' => array(
                    'op_condition' => array('method' => 'match','pattern' =>"/[0-9]+x[0-9]+/",'value' =>1 ),
                    'operate' => array(array('method'=> 'repalce','pattern' => "/_[0-9]+x[0-9]+/",'value' => ''),
                                    array('method' => 'add','pattern' => "",'value' => 'http:','direction' => -1),//-1 add字符串 加到匹配之前 1 加载之后  0 加载中间
                                   ),
        ),
        'content_txt' => array(
                    'op_condition' => array(),//对所有文本内容执行操作
                    'operate' => array(array('method' => 'repalce','pattern' => "/[a-zA-Z]+://[^\s]*/"),'value' => '',),//替换url
        ),
        'content_page_url' => array(
                    'op_condition' =>array(),
                    'operate' => array( array('method' => 'add', 'pattern'=> '' , 'value' => 'http://photo.hupu.com','direction' => '-1'),),
        ), 
        'cover_img_url' => array(
                    'op_condition' => array(),
                    'operate' => array(array('method' => 'add' , 'pattern' => '' , 'value' => 'http:', 'direction' => '-1')),
        ),
        'add_content' => array('place' => -1,'type' => 'content_txt','value' => '转载自'),//添加段落 
);
$arrSiteConf = array(
        'append_http_header'=> $appendHttpHeader,
        'grab_rule' => $arrGrabRule,
        'post_id_rule' => $arrPostIdRule,
        'deal_data_rule' => $deal_data_rule,
);
$jsonEncode = json_encode($arrSiteConf);
echo $jsonEncode;
