<?php
/**
 * Created by PhpStorm.
 * User: hjw
 * Date: 2017/9/7
 * Time: 14:38
 */

namespace Pc\Controller;
use Pc\Server\PageModel;
class HotelUserController extends BaseController
{
    public function _initialize(){
        //创建模型
        $this->model = 'User';
        parent::_initialize();
    }
    public function index(){
        $post=I('post.');
        $where = array();
        $hwhere=array();
        $hwhere['h.status'] = array('neq','-1');
        $where['u.type'] = 2;
        if(!empty($post['name'])){
            $where['u.real_name'] = array('like','%'.$post['name'].'%');
            $this->assign('wname',$post['name']);
        }
        if(!empty($post['hotel_id'])){
            $where['u.hotel_id'] = $post['hotel_id'];
            $this->assign('whotel',$post['hotel_id']);
        }
        if(!empty($post['role_id'])){
            $where['u.role_id'] = $post['role_id'];
            $this->assign('wtype',$post['role_id']);
        }
        $userInfo=session('USERINFO');
        switch ($userInfo['role_id']){
            case 2://销售人员
                //获取该销售人员所有绑定的酒店
                if(empty($where['u.hotel_id'])){
                    $hotelGetIds=M('hotel_get')->where(['sale_id'=>$userInfo['id'],'is_default'=>1])->field('h_id')->select();
                    $hotelIds = i_array_column($hotelGetIds,'h_id');
                    if($hotelIds){
                        $where['u.hotel_id'] = array('in',$hotelIds);
                    }
                }
                $where['u.status'] = 1;
                $hwhere['hg.sale_id'] = $userInfo['id'];
                $hwhere['hg.is_default'] = 1;
                break;
            case 1://超级管理员
                $hwhere['hg.is_default'] = 1;
                break;
            case 3://销售经理
            case 9://平台总经理
                $where['u.status'] = 1;
                $hwhere['hg.is_default'] = 1;
                break;
            case 12://酒店总经理
                $where['u.status'] = 1;
                $where['u.hotel_id'] = $userInfo['hotel_id'];
                $hwhere['h.id'] = $userInfo['hotel_id'];
                $hwhere['hg.is_default'] = 1;
                break;
        }
        $count=M('User')->alias('u')
            ->join(' right join yx_role as r ON u.role_id=r.id')
            ->join(' right join yx_hotel as h ON u.hotel_id=h.id')
            ->where($where)
            ->count();
        $page = new PageModel($count, C('PAGE_SIZE'));
        $first=$page->firstRow;
        $page->lastSuffix = false;
        $listRow=$page->listRows;

        $list=M('User')
            ->alias('u')
            ->join(' right join yx_role as r ON u.role_id=r.id')
            ->join(' right join yx_hotel as h ON u.hotel_id=h.id')
            ->where($where)
            ->limit($first,$listRow)
            ->order('u.id desc')
            ->field('u.* , r.name as rname , h.name as hname')
            ->select();
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        
        $hotel = M('HotelGet') ->alias('hg')
            ->join('left join yx_hotel as h ON h.id=hg.h_id')
            ->where($hwhere)
            ->field('h.name,h.id')
            ->select();
        $pageHtml=$page->show();
        $role = M('role')->where(array('type'=>2))->field('name,id')->select();
        $this->assign('hotel', $hotel);
        $this->assign('role', $role);
        $this->assign('row', $list);
        $this->assign('pageHtml', $pageHtml);
        $this->display();
    }
    //显示酒店员工信息
    public function indexOld(){
        $user = $_SESSION['USERINFO'];
        //酒店总经理
        if($user['role_id']==12){
            $name = I('post.name');
            $hotel_id = I('post.hotel_id');
            $role_id = I('post.role_id');
            $wheres= array('u.status'=>1,'u.type'=>2);
            if(!empty($role_id)) {
                $wheres['u.role_id']= $role_id;
            }
            if(!empty($hotel_id)) {
                $wheres['u.hotel_id']=$hotel_id;
            }
            if(!empty($name)) {
                $wheres['u.name']=array('like','%'.$name.'%');
            }
            $wheres['u.status'] = 1;
            $wheres['u.type'] = 2;
            $wheres['u.hotel_id']=$user['hotel_id'];
            if($name!=null || $hotel_id!=null || $role_id!=null){
                $row = M('user') ->alias('u')
                    ->join('yx_role as r ON u.role_id=r.id')
                    ->join('yx_hotel as h ON u.hotel_id=h.id')
                    ->field('u.* , r.name as rname , h.name as hname')
                    ->where($wheres)
                    ->select();
            }else{
                //分页
                $p = I('get.p', 1, 'intval');
                $count=M('user') ->alias('u')
                    ->join('yx_role as r ON u.role_id=r.id')
                    ->join('yx_hotel as h ON u.hotel_id=h.id')
                    ->where(array('u.status'=>1,'r.type'=>2,'h.id'=>$user['hotel_id']))
                    ->count();

                $listRows =20;
                $page = new PageModel($count, $listRows);  //自己获取请求中的页面直接使用
                $page->lastSuffix = false;
                $pageHtml = $page->show();
                $row = M('user') ->alias('u')
                    ->join('yx_role as r ON u.role_id=r.id')
                    ->join('yx_hotel as h ON u.hotel_id=h.id')
                    ->field('u.* , r.name as rname , h.name as hname')
                    ->where(array('u.status'=>1,'r.type'=>2,'u.hotel_id'=>$user['hotel_id']))
                    ->page($page->firstRow=$p,$page->listRows)
                    ->select();
            }

            //将当前列表的url保存到cookie中,为了 删除,添加,修改状态,编辑 之后跳转到该地址
            cookie('__forward__', $_SERVER['REQUEST_URI']);
            $hotel = M('hotel')->where(['id'=>$user['hotel_id']])->field('name,id')->select();
            $role = M('role')->where(array('type'=>2))->field('name,id')->select();
            $this->assign('hotel', $hotel);
            $this->assign('role', $role);
            $this->assign('row', $row);
            $this->assign('pageHtml', $pageHtml);
            $this->display();
        }elseif($user['role_id']==2){
            //销售人员
            $name = I('post.name');
            $hotel_id = I('post.hotel_id');
            $role_id = I('post.role_id');
            $wheres= array('u.status'=>1,'u.type'=>2);
            if(!empty($role_id)) {
                $wheres['u.role_id']= $role_id;
            }
            if(!empty($hotel_id)) {
                $wheres['u.hotel_id']=$hotel_id;
            }
            if(!empty($name)) {
                $wheres['u.name']=array('like','%'.$name.'%');
            }
            $wheres['hg.sale_id']= $user['id'];
            $wheres['hg.is_default']=1;
            $wheres['u.status']=1;
            $wheres['h.status']=1;
            $wheres['u.type']=2;
            $wheres['h.is_sign']=1;
            if($name!=null || $hotel_id!=null || $role_id!=null){
                $row = M('user ') ->alias('u')
                    ->join('yx_hotel_get as hg ON hg.h_id=u.hotel_id')
                    ->join('yx_hotel as h ON h.id=hg.h_id')
                    ->join('yx_role as r ON u.role_id=r.id')
                    ->field('u.* , r.name as rname , h.name as hname')
                    ->where($wheres)
                    ->select();
            }else{
                //分页
                $p = I('get.p', 1, 'intval');
                $count = M('user ') ->alias('u')
                    ->join('yx_hotel_get as hg ON hg.h_id=u.hotel_id')
                    ->join('yx_hotel as h ON h.id=hg.h_id')
                    ->join('yx_role as r ON u.role_id=r.id')
                    ->field('u.* , r.name as rname , h.name as hname')
                    ->where(array('h.status'=>1,'hg.sale_id'=>$user['id'],'hg.is_default'=>1,'u.status'=>1,'u.type'=>2,'h.is_sign'=>1))
                    ->count();
                $listRows =20;
                $page = new PageModel($count, $listRows);  //自己获取请求中的页面直接使用
                $page->lastSuffix = false;
                $pageHtml = $page->show();
                $row = M('user ') ->alias('u')
                    ->join('yx_hotel_get as hg ON hg.h_id=u.hotel_id')
                    ->join('yx_hotel as h ON h.id=hg.h_id')
                    ->join('yx_role as r ON u.role_id=r.id')
                    ->field('u.* , r.name as rname , h.name as hname')
                    ->where(array('h.status'=>1,'hg.sale_id'=>$user['id'],'hg.is_default'=>1,'u.status'=>1,'u.type'=>2,'h.is_sign'=>1))
                    ->page($page->firstRow=$p,$page->listRows)
                    ->select();
                //var_dump($row);exit;
            }

            //将当前列表的url保存到cookie中,为了 删除,添加,修改状态,编辑 之后跳转到该地址
            cookie('__forward__', $_SERVER['REQUEST_URI']);
            $hotel = M('hotel_get') ->alias('hg')
                ->join('yx_hotel as h ON h.id=hg.h_id')
                ->where(array('hg.is_default'=>1,'hg.sale_id'=>$user['id'],'h.is_sign'=>1,'h.status'=>1))
                ->field('h.name,h.id')
                ->select();
            $role = M('role')->where(array('type'=>2))->field('name,id')->select();
            $this->assign('hotel', $hotel);
            $this->assign('role', $role);
            $this->assign('row', $row);
            $this->assign('pageHtml', $pageHtml);
            $this->display();
        }else{


            //admin用户
            $name = I('post.name');
            $hotel_id = I('post.hotel_id');
            $role_id = I('post.role_id');
            if(!empty($role_id)) {
                $wheres['u.role_id']= $role_id;
            }
             if(!empty($hotel_id)) {
                 $wheres['u.hotel_id']=$hotel_id;
             }
            if(!empty($name)) {
                $wheres['u.name']=array('like','%'.$name.'%');
            }
            $wheres['hg.is_default']=1;
            $wheres['u.status']=1;
            $wheres['u.type']=2;
            $wheres['h.is_sign']=1;
            if($name!=null || $hotel_id!=null || $role_id!=null){
                $row = M('user') ->alias('u')
                    ->join('yx_hotel_get as hg ON hg.h_id=u.hotel_id')
                    ->join('yx_hotel as h ON h.id=hg.h_id')
                    ->join('yx_role as r ON u.role_id=r.id')
                    ->field('u.* , r.name as rname , h.name as hname')
                    ->where($wheres)
                    ->select();
            }else{
                //分页
                $p = I('get.p', 1, 'intval');
                $count = M('user ') ->alias('u')
                    ->join('yx_hotel_get as hg ON hg.h_id=u.hotel_id')
                    ->join('yx_hotel as h ON h.id=hg.h_id')
                    ->join('yx_role as r ON u.role_id=r.id')
                    ->field('u.* , r.name as rname , h.name as hname')
                    ->where(array('hg.is_default'=>1,'u.status'=>1,'u.type'=>2,'h.is_sign'=>1))
                    ->count();
                $listRows =20;
                $page = new PageModel($count, $listRows);  //自己获取请求中的页面直接使用
                $page->lastSuffix = false;
                $pageHtml = $page->show();
                $row = M('user ') ->alias('u')
                    ->join('yx_hotel_get as hg ON hg.h_id=u.hotel_id')
                    ->join('yx_hotel as h ON h.id=hg.h_id')
                    ->join('yx_role as r ON u.role_id=r.id')
                    ->field('u.* , r.name as rname , h.name as hname')
                    ->where(array('hg.is_default'=>1,'u.status'=>1,'u.type'=>2,'h.is_sign'=>1))
                    ->page($page->firstRow=$p,$page->listRows)
                    ->select();
            }

            //将当前列表的url保存到cookie中,为了 删除,添加,修改状态,编辑 之后跳转到该地址
            cookie('__forward__', $_SERVER['REQUEST_URI']);
            $hotel = M('hotel_get') ->alias('hg')
                ->join('yx_hotel as h ON h.id=hg.h_id')
                ->where(array('hg.is_default'=>1,'h.is_sign'=>1,'h.status'=>1))
                ->field('h.name,h.id')
                ->select();
            $role = M('role')->where(array('type'=>2))->field('name,id')->select();
            $this->assign('hotel', $hotel);
            $this->assign('role', $role);
            $this->assign('row', $row);
            $this->assign('pageHtml', $pageHtml);
            $this->display();
        }
    }


    //添加酒店员工
    public function _before_edit_view(){
        $user = $_SESSION['USERINFO'];
        if($user['role_id']==12){
            $row = M('hotel')->where(['id'=>$user['hotel_id']])->field('name,id')->select();
        }elseif($user['role_id']==2){
            $row = M('hotel_get') ->alias('hg')
                ->join('yx_hotel as h ON h.id=hg.h_id')
                ->where(array('hg.is_default'=>1,'hg.sale_id'=>$user['id'],'h.status'=>1))
                ->field('h.name,h.id')
                ->select();
        }elseif($user['role_id']==1){
            $row = M('hotel')->where(array('status'=>1))->field('name,id')->select();
        }else{
            $row = M('hotel_get') ->alias('hg')
                ->join('yx_hotel as h ON h.id=hg.h_id')
                ->where(array('hg.is_default'=>1,'h.status'=>1))
                ->field('h.name,h.id')
                ->select();
            
        }
        $this->assign('row', $row);

        $role = M('role')->where(array('type'=>2))->field('name,id')->select();
        $this->assign('role', $role);
    }

    //查看
    public function sel(){
        $id = I('get.id');
        $row = M('user') ->alias('u')
            ->join('yx_role as r ON u.role_id=r.id')
            ->join('yx_hotel as h ON u.hotel_id=h.id')
            ->field('u.* , r.name as rname , h.name as hname')
            ->where(['u.id'=>$id])
            ->find();
        $this->assign('row', $row);
        $this->display();
    }

    //解除微信绑定
    public function wxdel(){
        $model=M('user')->where(['id'=>$_GET['id']]);
        $model->uuid=null;
        $model->save();
        $this->redirect('index');
    }

    public function hotelUserOperation(){
        if(IS_POST){
            if ($this->model->create() !== false) {
            }else{
                ajax_return(0, $this->model->getError());
                exit;
            }
            $postData=I('post.');
            if(empty($postData['session']) || $postData['session'] != session('hotelUser')){
                ajax_return(0,'请不要重复提交');
                exit;
            }
            if(!$this->model->create()){
                ajax_return(0,$this->model->getError());
            }else{
                $res=$this->model->operation();
                if($res){
                    session('hotelUser','hotelUser');
                }
                $this->admin_ajax_return($res);
            }
        }else{
            $id=I('get.id');
            $this->_before_edit_view();
            if(!empty($id)){
                $info = $this->model->getInfo($id);
                $this->assign($info);
                $redonly = I('get.redonly');
                if ($redonly) {
                    $this->assign('redonly', $redonly);
                }
            }
            session('hotelUser',date('Y-m-d H:i:s',time()));
            $this->assign('operation',session('hotelUser'));
            $this->display('edit');
        }
    }
    public function delUser(){
        $id=I('post.id');
        $result=array('code'=>0,'message'=>"删除失败");
        $userInfo=M('User')->where(array('id'=>$id))->find();
        if($userInfo['status'] == '-1'){
            $result['message'] = '该账号是已删除的账号，不能重复删除';
        }else{
            $res=M('User')->where(array('id'=>$id))->setField('status','-1');
            if($res){
                $result['code'] = 1;
                $result['message'] = '删除成功';
            }
        }
        $this->ajaxReturn($result);
    }
}