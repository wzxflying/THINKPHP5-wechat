<?php
/**
 * Created by PhpStorm.
 * User: wlb-71
 * Date: 2017/12/15
 * Time: 9:53
 */
return [
    'weixin' => [
        'appid' => '1',
        'secret' => '2',
        'mchid' => '',
        'key' => '',
        //这里是异步通知页面url，提交到项目的Pay控制器的notifyurl方法；
        'notify_url'=>'https://xxx.xxxx.com/index.php/Api/Wxpay/notify',

    ],
];