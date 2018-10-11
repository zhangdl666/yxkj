<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/10/25
 * Time: 14:25
 */
namespace Wx\Controller;
use Wx\Controller\BaseController;
class UserController extends BaseController{
    public function _initialize(){
        $this->model = 'User';
        parent::_initialize();
    }
    public function getUserInfo(){
        $userInfo=session('USERINFO');
        /*if(!empty($userInfo['img'])){
            if(!file_exists($userInfo['img'])){
                $userInfo['img'] = '';
            }
        }*/
        $userInfo['role_name']=M('Role')->field('name')->where(array('id'=>$userInfo['role_id']))->getField('name');
        $userInfo['hotel_name']=M('Hotel')->where(['id'=>$userInfo['hotel_id']])->getField('name');
        //var_dump($userInfo);exit;
        $this->assign('user',$userInfo);
        $this->display('userInfo');
    }
    public function edit(){
        $type=I('get.type');
        $userInfo=session('USERINFO');
        if($type == 'mobile'){
            $title='电话号码修改';
        }elseif($type == 'age'){
            $title='年龄修改';
        }
        $this->assign('title',$title);
        $data=array('field'=>$type);
        $data['value'] = $userInfo[$type];
        $this->assign('data',$data);
        $this->assign('userId',$userInfo['id']);
        $this->display();
    }
    public function operation(){
        $postData=I('post.');
        $postData[$postData['field']] = $postData['value'];
        unset($postData['field']);
        unset($postData['value']);
        $result=$this->model->save($postData);
        /*if($result){
            $user=$this->model->where(['id'=>$postData['id']])->field('salt,id,parent_id,name,real_name,mobile,img,sex,age,hotel_id,role_id')->find();
            session('USERINFO',$user);
        }*/
        $this->admin_ajax_return($result);
        //var_dump($postData);exit;
    }
    //保存头像
    public function saveImg(){
        $img=I('post.img');
        $userInfo=session('USERINFO');
        $res=$this->model->where(['id'=>$userInfo['id']])->setField('img',$img);
        /*if($res){
            $user=$this->model->where(['id'=>$userInfo['id']])->field('salt,id,parent_id,name,real_name,mobile,img,sex,age,hotel_id,role_id')->find();
            session('USERINFO',$user);
        }*/
        $this->admin_ajax_return($res);
    }
    public function changePwd(){
        if(!IS_POST){
            $this->display();
        }else{
            $result=array('code'=>0,'message'=>'修改密码失败');
            $postData=I('post.');
            $userInfo=session('USERINFO');
            $oldPass=M('User')->where(['id'=>$userInfo['id']])->getField('password');
            if($oldPass != md5(md5($postData['oldPwd']).$userInfo['salt'])){
                $result['message'] = '输入的旧密码不正确';
                $this->ajaxReturn($result);
            }else{
                $res=M('User')->where(['id'=>$userInfo['id']])->setField('password',md5(md5($postData['newPwd']).$userInfo['salt']));
                if($res){
                    $result['code'] = 1;
                    $result['message'] = '密码修改成功';
                }
                $this->ajaxReturn($result);
            }
        }
    }
    public function changeSex(){
        $sex=I('post.sex');
        $userInfo=session('USERINFO');
        $res=$this->model->where(['id'=>$userInfo['id']])->setField('sex',$sex);
        /*if($res){
            $user=$this->model->where(['id'=>$userInfo['id']])->field('salt,id,parent_id,name,real_name,mobile,img,sex,age,hotel_id,role_id')->find();
            session('USERINFO',$user);
        }*/
        $this->admin_ajax_return($res);
    }
    public function signOut(){
        $userInfo=session('USERINFO');
        $uuid=M('User')->where(['id'=>$userInfo['id']])->getField('uuid');
        if(!empty($uuid)){
            $res=M('User')->where(['id'=>$userInfo['id']])->setField('uuid','');
            if($res){
                session('USERINFO',null);
            }
            $this->admin_ajax_return($res);
        }else{
            session('USERINFO',null);
            $this->admin_ajax_return(1);
        }
    }
}