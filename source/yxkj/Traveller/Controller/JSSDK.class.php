<?php
namespace Traveller\Controller;

class JSSDK {
  private $appId;

  public function __construct($appId) {
    $this->appId = $appId;
  }

  public function getSignPackage() {
    $jsapiTicket = $this->getJsApiTicket();
    // 注意 URL 一定要动态获取，不能 hardcode.
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $url = "$protocol$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

    $timestamp = time();
    $nonceStr = $this->createNonceStr();

    // 这里参数的顺序要按照 key 值 ASCII 码升序排序
    $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";

    $signature = sha1($string);

    $signPackage = array(
      "appId"     => $this->appId,
      "nonceStr"  => $nonceStr,
      "timestamp" => $timestamp,
      "url"       => $url,
      "signature" => $signature,
      "rawString" => $string
    );
    return $signPackage; 
  }

  private function createNonceStr($length = 16) {
    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    $str = "";
    for ($i = 0; $i < $length; $i++) {
      $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
    }
    return $str;
  }

  private function getJsApiTicket(){
    $access_tokeen_url = 'http://nodetoken.bjhike.com/gettoken/?wx_id=76';
    $at_res = json_decode($this->httpGet($access_tokeen_url));
    //file_put_contents('./at_file.txt', var_export($at_res,true),FILE_APPEND);
    /*print_r($at_res);
    echo '<br/>';*/
    return $at_res->ticket; 



   /* // jsapi_ticket 应该全局存储与更新，以下代码以写入到文件中做示例
    $jsapi_ticket = session('jsapi_ticket');
    //$data = json_decode(file_get_contents("jsapi_ticket.json"));
    if (empty($jsapi_ticket) || $jsapi_ticket['expire_time'] < time()) {
      $accessToken = $this->getAccessToken();
      // 如果是企业号用以下 URL 获取 ticket
      // $url = "https://qyapi.weixin.qq.com/cgi-bin/get_jsapi_ticket?access_token=$accessToken";
      $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=$accessToken";
      $res = json_decode($this->httpGet($url));
      if (empty($res->errcode)) {
        $session_data['expire_time'] = time() + 7000;
        $session_data['jsapi_ticket'] = $res->ticket;
        session('jsapi_ticket',$session_data);
        return $res->ticket;
      }
    } else {
      $ticket = $jsapi_ticket['jsapi_ticket'];
    }

    return $ticket;*/
  }

  public function getAccessToken(){
    $access_tokeen_url = 'http://nodetoken.bjhike.com/gettoken/?wx_id=76';
    $at_res = json_decode($this->httpGet($access_tokeen_url));
    //file_put_contents('./at_file.txt', var_export($at_res,true),FILE_APPEND);
    print_r($at_res);
    echo '<br/>';
    echo $at_res->ticket;
    //echo "<br/>".'ticket:'.$at_res['ticket'];
    exit;
    return $at_res->access_token;
  }

  private function httpGet($url) {
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_TIMEOUT, 500);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($curl, CURLOPT_URL, $url);

    $res = curl_exec($curl);
    curl_close($curl);

    return $res;
  }
}