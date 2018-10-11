<?php
/**
 * Author: ' Silent
 * Time: 2017/11/9 14:44
 */

namespace Pc\Controller;


use Think\Controller;

class PHPSocketController extends Controller
{
    /**
     *
     */
    public function newMessage()
    {
        // 指明给谁推送，为空表示向所有在线用户推送
        $to_uid = '123456';
        // 推送的url地址，使用自己的服务器地址
        $push_api_url = "http://".get_client_ip().":2121/";
        $post_data = array("type" => "publish", "content" => "22", "to" => $to_uid);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $push_api_url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Expect:"));
        $return = curl_exec($ch);
        curl_close($ch);
    }
}