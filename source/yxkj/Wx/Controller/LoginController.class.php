<?php
/**
 * LoginController.class.php
 * 员工账号绑定 控制器
 * @author baddl
 * @date   2017-09-19 18:14
 */
namespace Wx\Controller;
use Think\Controller;

class LoginController extends Controller{
	/**
	 * 登录页面
	 */
	public function index(){
		$wx_config = C('WEIXIN');
        $jssdk = new JSSDK($wx_config['AppID'],$wx_config['AppSecret']);
        $sign_package = $jssdk->getSignPackage();
        //print_r($sign_package);exit;
        $this->assign($sign_package);
		/*session('USERINFO',null);
		session('ACCESS_INFO',null);
		session_destroy();*/
		$this->assign('openid',I('get.openid'));
		$this->assign('access_token',session('ACCESS_INFO.ACCESS_TOKEN'));
		
		$this->display();
	}

	/**
	 * 处理员工账号绑定
	 */
	public function dologin(){
		$data = I('post.');
		/*if(!isset($data['login'])){
			ajax_return(0,'请仔细阅读协议');
			exit;
		}*/
		$username = trim($data['name']);
		$pwd = trim($data['pwd']);
		if(empty($username)){
			ajax_return(0,'请输入账号');
			exit;
		}elseif($pwd==""){
			ajax_return(0,'请输入密码');
			exit;
		}
        if(strlen($_POST['pwd'])>15){
            ajax_return(0,"密码长度为1~15位");
            return false;
        };
		$user_info = M('User')->field('password,salt,id,parent_id,name,real_name,mobile,img,sex,age,hotel_id,role_id,uuid,status')->where(array('name'=>$data['name']))->find();
		if(empty($user_info)){
			ajax_return(0,'此账号不存在');
			exit;
		}elseif($user_info['password'] != md5(md5($data['pwd']).$user_info['salt'])){
			ajax_return(0,'密码错误,请重新输入');
			exit;
		}elseif($user_info['role_id'] == 1){
			ajax_return(0,'此帐号不能在该平台登录');
			exit;
		}elseif($user_info['status'] != 1){
            ajax_return(0,'该账号已被管理员禁用，请联系管理员');
            exit;
        }
		// 判断uuid,已经存在
        if($user_info['uuid']){
		     //判断是否相同
		    if($user_info['uuid'] != $data['uuid']){
                ajax_return(0,'当前账号与其他微信绑定,如需解绑,请联系管理员');
                exit;
            }
        }
		$re_sult = M('User')->where(array('id'=>$user_info['id']))->save(array('uuid'=>$data['uuid']));
		if($re_sult = true){
			unset($user_info['password']);
			unset($user_info['status']);
			session('USERINFO',$user_info);
			ajax_return(1,'绑定成功',U('Index/index'));
		}else{
			ajax_return(0,'绑定失败');
		}
	}
}