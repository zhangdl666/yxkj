<?php
/**
 * WeixinController.class.php
 * 微信端 微信回调控制器
 * @author baddl
 * @date   2017-10-12 10:39
 */
namespace Wx\Controller;
use Think\Controller;

class WeixinController extends Controller{
	/**
	 * 网页授权回调 获取access_token openid
	 */
	public function get_token_openid(){
		$wx_config = C('WEIXIN');
		$url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid='.$wx_config['AppID'].'&secret='.$wx_config['AppSecret'].'&code='.I('get.code').'&grant_type=authorization_code';
		$re_data = $this->http_curl($url);
		$re_data = json_decode($re_data);
		if(empty($re_data->errcode)){
			$openid = $re_data->unionid?$re_data->unionid : $re_data->openid;
			//$oid=$userInfo['unionid']?$userInfo['unionid']:$userInfo['openid'];
			//$access_info = array('ACCESS_TOKEN'=>$re_data->access_token,'ACCESS_TIME'=>time(),'EXPIRES_IN'=>$re_data->expires_in,'OPENID'=>$openid);
			//判断用户是否关注公众号
			//$this->is_attention($access_info['ACCESS_TOKEN'],$access_info['OPENID']);
			session('ACCESS_INFO.OPENID',$openid);
			header('Location:'.U('Index/index'));
		}else{
			echo $re_data->errmsg;
			exit;
		}
	}

	/**
	 * 用户是否关注公众号
	 */
	/*public function is_attention($token,$openid){
		$attention_uri = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$token.'&openid='.$openid;
		$re_data = $this->http_curl($attention_uri);
		$re_data = json_decode($re_data);
		if(empty($re_data->errcode)){
			if ($subscribe->subscribe !== 1) {
				echo'未关注！';
			}
		}else{
			return $re_data['errmsg'];
		}
	}*/


	/**
	 * 微信事件推送
	 */
	public function fun(){
		

		//file_put_contents('./wx.txt', var_export($_REQUEST,true)."\r\n",FILE_APPEND);
		/* 微信验证服务器 */
		$data = $_POST ? $_POST : $_GET;
		$echostr = $data['echostr'];
		$signature=$data['signature'];

		$data_arr['timestamp'] = $data['timestamp'];
		$data_arr['nonce'] = $data['nonce'];
		$data_arr['token'] = '7ewG95jG6EeWMUTbykmDfRf0dZDviG7R';

		//排序
		sort($data_arr);
		//得字符串
		$tmpStr = implode($data_arr);
		//哈希算法加密
        $tmpStr = sha1($tmpStr);
        if($tmpStr == $signature ){
            ob_clean();
			echo $data['echostr'];
			exit;
        }else{
            return false;
        }
	}

	private $_msg_template = array(
        'text' => '<xml><ToUserName><![CDATA[%s]]></ToUserName><FromUserName><![CDATA[%s]]></FromUserName><CreateTime>%s</CreateTime><MsgType><![CDATA[text]]></MsgType><Content><![CDATA[%s]]></Content></xml>',//文本回复XML模板
        'image' => '<xml><ToUserName><![CDATA[%s]]></ToUserName><FromUserName><![CDATA[%s]]></FromUserName><CreateTime>%s</CreateTime><MsgType><![CDATA[image]]></MsgType><Image><MediaId><![CDATA[%s]]></MediaId></Image></xml>',//图片回复XML模板
        'music' => '<xml><ToUserName><![CDATA[%s]]></ToUserName><FromUserName><![CDATA[%s]]></FromUserName><CreateTime>%s</CreateTime><MsgType><![CDATA[music]]></MsgType><Music><Title><![CDATA[%s]]></Title><Description><![CDATA[%s]]></Description><MusicUrl><![CDATA[%s]]></MusicUrl><HQMusicUrl><![CDATA[%s]]></HQMusicUrl><ThumbMediaId><![CDATA[%s]]></ThumbMediaId></Music></xml>',//音乐模板
        'news' => '<xml><ToUserName><![CDATA[%s]]></ToUserName><FromUserName><![CDATA[%s]]></FromUserName><CreateTime>%s</CreateTime><MsgType><![CDATA[news]]></MsgType><ArticleCount>%s</ArticleCount><Articles>%s</Articles></xml>',// 新闻主体
        'news_item' => '<item><Title><![CDATA[%s]]></Title><Description><![CDATA[%s]]></Description><PicUrl><![CDATA[%s]]></PicUrl><Url><![CDATA[%s]]></Url></item>',//某个新闻模板
    );

	private $_device_template = array(
		'device' => '<xml><ToUserName><![CDATA[%s]]></ToUserName><FromUserName><![CDATA[%s]]></FromUserName><CreateTime>%u</CreateTime><MsgType><![CDATA[%s]]></MsgType><DeviceType><![CDATA[%s]]></DeviceType><DeviceID><![CDATA[%s]]></DeviceID><SessionID>%u</SessionID><Content><![CDATA[%s]]></Content></xml>',
	);
	/**
     * 发送文本信息
     * @param  [type] $to      目标用户ID
     * @param  [type] $from    来源用户ID
     * @param  [type] $content 内容
     * @return [type]          [description]
     */
	private function _msgText($to, $from, $content) {
        $response = sprintf($this->_msg_template['text'], $to, $from, time(), $content);
        die($response);
    }
    //接收文本做处理
	private function _doText($request_xml){
		$content = '你好';
        $this->_msgText($request_xml->FromUserName, $request_xml->ToUserName, $content);
	}
	//关注后做的事件
	private function _doSubscribe($request_xml){
        //处理该关注事件，向用户发送关注信息
        $content = '你好';
        $this->_msgText($request_xml->FromUserName, $request_xml->ToUserName, $content);
    }
    //硬件绑定后做的处理
    private function _doBind($request_xml){
    	$content = 'device_text ';
        $this->_msgText($request_xml->FromUserName, $request_xml->ToUserName, $content, $request_xml->DeviceType, $request_xml->DeviceID,base64_encode('启动'));
    }

	/**
	 * 菜单创建
	 */
	public function create_menu(){
		$access_info = session('ACCESS_INFO');
        //微信时间是否过期
       if (empty($access_info['ACCESS_TIME']) || floor((time() - $access_info['ACCESS_TIME']) % 86400 % 60) >= 7200) {
       	$access_token = $this->get_access_token();
       }else{
       	$access_token = $access_info['ACCESS_TOKEN'];
       }
		$cm_url = 'https://api.weixin.qq.com/cgi-bin/menu/create?access_token='.$access_token;
		$cm_data['button'][] = array('type'=>'view','name'=>urlencode('员工入口'),'url'=>WEB_URL.U('Index/index'));
		$cm_data['button'][] = array('type'=>'view','name'=>urlencode('旅客入住'),'url'=>WEB_URL.U('Traveller/CheckinInfo/index'));
		$data = urldecode(json_encode($cm_data));
		var_dump($data);
		$re_data = $this->http_curl($cm_url,$data);
		$re_data = json_decode($re_data);
		file_put_contents('./wx_menu.txt', var_export($re_data,true)."\r\n",FILE_APPEND);
	}

	/**
	 * 获取ACCESS_TOKEN
	 */
	public function get_access_token(){
		$wx_config = C('WEIXIN');
		$url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$wx_config['AppID'].'&secret='.$wx_config['AppSecret'];
		$re_data = $this->http_curl($url);
		$re_data = json_decode($re_data);
		//file_put_contents('./access_token.txt', var_export($re_data,true)."\r\n",FILE_APPEND);
		return $re_data->access_token;
	}

	/**
     * 用户和设备的绑定关系
     * @author baddl
     * @datetime 2017-11-1 15:06
     * @param string $openid
     */
	public function getubind(){
		$url = 'http:/api.bjhike.com/api/v2_userBind';
		$openid = 'o6jtgwzSfHugHcnMeB8OnyDFy6A4';
		$params = array('companyToken'=>C('HIKE.companyToken'),'openid'=>$openid);
        $query_string = is_array($params) ? http_build_query($params) : $params;
        $geturl = $query_string ? $url . (stripos($url, "?") !== FALSE ? "&" : "?") . $query_string : $url;

        $re_data = $this->http_curl($geturl);
        $re_data = '{"code":200,"openid":"o6jtgwzSfHugHcnMeB8OnyDFy6A4","devices":[{"mac":"D0BAE41B4C81","deviceid":"gh_73d4995abdb8_e22a727c0261ef66","owner":1,"pm25":"45","apm25":"42"}],"message":""}';
		$re_data = json_decode($re_data);

		if($re_data->code == 200){
			//
			foreach ($re_data->devices as $key => $value) {
				if($value->owner == 1){
					echo 'MAC:'.$value->mac;
					echo "<br/>Device:".$value->deviceid;
					break;
				}
			}
		}else{
			echo $re_data->message;
		}

		
	}

	/**
	 * @name   curl获取信息
	 * @param  string 		$url  接收数据的api
	 * @param  array/string $data 提交的数据
	 */
	public function http_curl($url,$data=array()){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		//要求程序必须在30秒内完成,负责到30秒后放到后台执行
		curl_setopt($ch,CURLOPT_TIMEOUT,30);
		//设置获取的信息以文件流的形式返回，而不是直接输出
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		//是否检测服务器的证书是否由正规浏览器认证过的授权CA颁发的
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		//是否检测服务器的域名与证书上的是否一致
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

		if($data){
			// post数据
			curl_setopt($ch, CURLOPT_POST, 1);
			// post的变量
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		}else{
			//设置头文件的信息作为数据流输出
			curl_setopt($ch, CURLOPT_HEADER, 0);
		}

		$output = curl_exec($ch);
		curl_close($ch);
		
		//打印获得的数据
		return $output;
	}
}