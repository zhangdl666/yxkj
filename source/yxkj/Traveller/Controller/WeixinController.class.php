<?php
/**
 * WeixinController.class.php
 * 微信端 微信回调控制器
 * @author baddl
 * @date   2017-10-12 10:39
 */
namespace Traveller\Controller;
use Think\Controller;

class WeixinController extends Controller{
	/**
	 * 网页授权回调 获取access_token openid
	 */
	public function get_token_openid(){
		$user_data = $_REQUEST;
		//file_put_contents('./openfile.txt', var_export($openid,true),FILE_APPEND);
		//查看是否存在用户
		$uid = M('User')->where(array('uuid'=>$user_data['openid']))->getField('id');
		if(empty($uid)){
			$is_name = M('User')->where(array('name'=>$user_data['nickname']))->getField('id');
            $udata['name']= $user_data['nickname'];
            $udata['img'] = $user_data['headimgurl'];
            $udata['sex'] = $user_data['sex'];
            $udata['uuid']= $user_data['openid'];
            $udata['type']= 3;
			if($is_name){
                M('User')->where(array('id'=>$is_name))->setField($udata);
                $result =$is_name;
                session('TRAVEID',$is_name);
			}else{
                $result = M('User')->add($udata);
                //修改用户与设备绑定关系表

                session('TRAVEID',$result);
            }
		}else{
			session('TRAVEID',$uid);
            $result =$uid;
		}
		session('TRAVELLEROPENID',$user_data['openid']);
		header('Location:'.U('CheckinInfo/index',array('id'=>$result)));
	}

	/**
     * 用户和设备的绑定关系
     * @author baddl
     * @datetime 2017-11-1 15:06
     * @param string $openid
     */
	public function getubind($openid){
		$url = 'http:/api.bjhike.com/api/v2_userBind';
		$params = array('companyToken'=>C('HIKE.companyToken'),'openid'=>$openid);
        $query_string = is_array($params) ? http_build_query($params) : $params;
        $geturl = $query_string ? $url . (stripos($url, "?") !== FALSE ? "&" : "?") . $query_string : $url;

        $re_data = $this->http_curl($geturl);
        //$re_data = '{"code":200,"openid":"o6jtgwzSfHugHcnMeB8OnyDFy6A4","devices":[{"mac":"D0BAE41B4C81","deviceid":"gh_73d4995abdb8_e22a727c0261ef66","owner":1,"pm25":"45","apm25":"42"}],"message":""}';
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