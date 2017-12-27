<?php
/**
 * Created by PhpStorm.
 * User: wlb-71
 * Date: 2017/12/15
 * Time: 9:53
 */
return [
    'weixin' => [
        'appid' => 'wx1fc9dbf80a0db930',
        'secret' => 'f9d6a780fb0ce4b15237ab7da8194e8b',
        'mchid' => '',
        'key' => '',
        //这里是异步通知页面url，提交到项目的Pay控制器的notifyurl方法；
        'notify_url'=>'https://xxx.xxxx.com/index.php/Api/Wxpay/notify',

    ],
    'wx'  => [
        'url' => 'https://api.weixin.qq.com/sns/jscode2session',
        'appid' => 'wx1fc9dbf80a0db930',
        'secret' => 'f9d6a780fb0ce4b15237ab7da8194e8b',
        'grant_type' => 'authorization_code'
    ]
];