<?php
/**
 * LoginController.class.php
 * 登录
 * @author: wy901216
 * @date: 2017/9/4  11:39
 */

namespace Pc\Controller;
use Pc\Model\UserRoleModel;

class LoginController extends BaseController{
    public function _initialize()
    {
        $this->model = D('User');
    }

    /**
     * 登录页面
     */
    public function index(){
        $this->display();
    }
    /**
     * 验证登录
     */
    public function check_login(){
        //{"captcha":yzm,"username":username,"password":password},
        $remember = I('post.remember');
        $username = I('post.username');
        if(empty($username)){
            ajax_return(0,"用户账号不能为空");
        }
        $password = I('post.password');
      if(strlen($password)>15){
          ajax_return(0,"密码长度为1~15位");
          return false;
      };
        if(empty($password)){
            ajax_return(0,"密码不能为空");
        }
        
        //先验证验证码
        $captcha = I('post.captcha');
        $verify = new \Think\Verify();
        if(!$verify->check($captcha)){
            ajax_return(0,"验证码错误");
            return;
        };
        //验证用户姓名
        $res=$this->model->where(array('name'=>$username,'status'=>1))->find();
        if(empty($res)){
            ajax_return(0,"用户账号不存在或已禁用");
        }else{
            $pwd = md5(md5($password).$res['salt']);
            if($pwd == $res['password']){
                //保存管理员登录信息
                session('USERINFO',$res);
                if($remember == 1){
                    cookie('USERINFO',$res,7*24*3600);
                }
                ajax_return(1,"登录成功",U("Index/index"));
            }else{
                ajax_return(0,"密码错误");
            }
        }
    }

    /**
     * 退出登录
     */
    public function login_out(){
        session('USERINFO',null);
        cookie('USERINFO',null);
        $session = session('USERINFO');
        $cookie  = cookie('USERINFO');
        if(empty($session) && empty($cookie)){
            ajax_return(1,"退出成功",U('Login/index'));
        }
    }
    /**
     * 修改密码
     */
    public function up_pwd(){
        if(IS_POST){
            $POST=I('post.');
            if(empty($POST['oldPwd'])){
                ajax_return(0,"请填写原始密码");
                return;
            }
            if(empty($POST['newPwd'])){
                ajax_return(0,"请填写新密码");
                return;
            }
            if(strlen($POST['newPwd'])<6||strlen($POST['newPwd'])>15){
                ajax_return(0,"请输入6-15位的新密码");
                return;
            }
            if(empty($POST['secondPwd'])){
                ajax_return(0,"请再次输入密码");
                return;
            }
            //获取当前管理员信息
            $info=session('USERINFO')?session('USERINFO'):cookie('USERINFO');
            //判断原始密码是否正确
            if($info['password']!==md5(md5($POST['oldPwd']).'lnlx')){
                ajax_return(0,"原始密码不正确");
                return;
            }
            if($POST['newPwd']!=$POST['secondPwd']){
                ajax_return(0,"两次输入的密码不一致，请重新输入");
            }else{
                //修改密码
                $res=$this->model->where(array('id'=>$info['id']))->save(array('password'=>md5(md5($POST['newPwd']).'lnlx')));
                if($res!=false){
                    //修改成功，清空SESSION,COOKIE,跳转到登录页面
                    session('USERINFO',null);
                    cookie('USERINFO',null);
                    ajax_return(1,"修改密码成功",U("Login/index"));
                    return;
                }
            }
        }else{
            $this->display('edit');
        }
    }

//    public function test(){
//        $data['salt'] = 'ABCD';
//        $data['name'] = 'admin';
//        $password = '123456';
//        $data['password'] = md5(md5($password).$data['salt']);
//        $data['ctime'] = time();
//        $this->model->add($data);
//    }

     public function test(){
         dump(md5(md5('123456').'c776'));
         dump(UserRoleModel::makePassword('123456','c776'));
     }

}