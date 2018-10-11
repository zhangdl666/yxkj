<?php
namespace Wx\Controller;

class JSSDK {
  private $appId;
  private $appSecret;

  public function __construct($appId, $appSecret) {
    $this->appId = $appId;
    $this->appSecret = $appSecret;
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

  private function getJsApiTicket($num=0){
    // jsapi_ticket 应该全局存储与更新，以下代码以写入到文件中做示例
    $jsapi_ticket = session('jsapi_ticket');
    //$data = json_decode(file_get_contents("jsapi_ticket.json"));
    if (empty($jsapi_ticket) || $jsapi_ticket['expire_time'] < time()) {
      $accessToken = $this->getAccessToken();
      // 如果是企业号用以下 URL 获取 ticket
      //$url = "https://qyapi.weixin.qq.com/cgi-bin/get_jsapi_ticket?access_token=$accessToken";
      $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=$accessToken";
      //file_put_contents('./Uploads/weixin_config.txt', "\r\nticket:".$url,FILE_APPEND);
      $res = json_decode($this->httpGet($url));
      if (empty($res->errcode)) {
        $session_data['expire_time'] = time() + 7000;
        $session_data['jsapi_ticket'] = $res->ticket;
        session('jsapi_ticket',$session_data);
        return $res->ticket;
      }elseif($num < 5){
        ++$num;
        $url_at = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$this->appId&secret=$this->appSecret";
        $res_at = json_decode($this->httpGet($url_at));
        if (empty($res_at->errcode)) {
          $access_info = session('ACCESS_INFO');
          if(!empty($access_info)){
            session('ACCESS_INFO.ACCESS_TOKEN',$res_at->access_token);
            session('ACCESS_INFO.ACCESS_TIME',time());
            session('ACCESS_INFO.EXPIRES_IN',$res_at->expires_in);
          }else{
            $access_info = array('ACCESS_TOKEN'=>$res_at->access_token,'ACCESS_TIME'=>time(),'EXPIRES_IN'=>$res_at->expires_in);
            session('ACCESS_INFO',$access_info);
          }
        }
        return $this->getJsApiTicket($num);
      }
    } else {
      $ticket = $jsapi_ticket['jsapi_ticket'];
    }

    return $ticket;
  }

  public function getAccessToken() {
    // access_token 应该全局存储与更新，以下代码以写入到文件中做示例
    //$data = json_decode(file_get_contents("access_token.json"));
    $access_info = session('ACCESS_INFO');
    
    //微信时间是否过期
    if(empty($access_info['ACCESS_TIME']) || floor((time()-$access_info['ACCESS_TIME'])%86400%60) >= 7200){
      // 如果是企业号用以下URL获取access_token
      // $url = "https://qyapi.weixin.qq.com/cgi-bin/gettoken?corpid=$this->appId&corpsecret=$this->appSecret";
      $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$this->appId&secret=$this->appSecret";
      //file_put_contents('./Uploads/weixin_config.txt', "\r\nAccessToken:".$url,FILE_APPEND);
      $res = json_decode($this->httpGet($url));
      if (empty($res->errcode)) {
        if(!empty($access_info)){
          $access_info = array('OPENID'=>$access_info['OPENID'],'ACCESS_TOKEN'=>$re_data->access_token,'ACCESS_TIME'=>time(),'EXPIRES_IN'=>$re_data->expires_in);
          session('ACCESS_INFO',$access_info);
          /*session('ACCESS_INFO.ACCESS_TOKEN',$res->access_token);
          session('ACCESS_INFO.ACCESS_TIME',time());
          session('ACCESS_INFO.EXPIRES_IN',$res->expires_in);*/
        }else{
          $access_info = array('ACCESS_TOKEN'=>$res->access_token,'ACCESS_TIME'=>time(),'EXPIRES_IN'=>$res->expires_in);
          session('ACCESS_INFO',$access_info);
        }
        return $res->access_token;
        /*$session_data['expire_time'] = time() + 7000;
        $session_data['access_token'] = $access_token;
        session('access_token',$session_data);*/
        /*$fp = fopen("access_token.json", "w");
        fwrite($fp, json_encode($data));
        fclose($fp);*/
      }
    } else {
      $access_token = $access_info['ACCESS_TOKEN'];
    }
    return $access_token;
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