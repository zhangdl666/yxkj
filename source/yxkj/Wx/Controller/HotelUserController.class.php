<?php
/**
 * Created by PhpStorm.
 * User: hjw
 * Date: 2017/9/19
 * Time: 10:50
 */

namespace Wx\Controller;

class HotelUserController extends BaseController
{
    public function _initialize(){
        $this->model = 'User';
        parent::_initialize();
    }
    public function index(){
        $userInfo=session('USERINFO');
        $where=array();
        $where['u.type'] = 2;
        $hwhere=array();
        $hotel=I('get.hotel');
        if(!empty($hotel)){
            $where['u.hotel_id'] = $hotel;
            $this->assign('whotel_id',$hotel);
        }
        $type=I('get.type');
        if(!empty($type)){
            $where['u.role_id'] = $type;
            $this->assign('wtype_id',$type);
        }
        $like=I('get.like');
        if(!empty($like)){
            $where['u.real_name'] = array('like','%'.$like.'%');
            $this->assign('wlike',$like);
        }

        $hwhere['hg.is_default'] = 1;
        switch ($userInfo['role_id']){
            case 2://销售人员
                if(empty($where['u.hotel_id'])){
                    $hotelGetIds=M('hotel_get')->where(['sale_id'=>$userInfo['id'],'is_default'=>1])->field('h_id')->select();
                    $hotelIds= i_array_column($hotelGetIds,'h_id');
                    if($hotelIds){
                       $where['u.hotel_id'] = array('in',$hotelIds); 
                    }
                }
                $where['u.status'] = 1;
                $hwhere['hg.sale_id'] = $userInfo['id'];
                break;
            case 3://销售经理
                $where['u.status'] = 1;
                break;
            case 9://平台总经理
                break;
            case 12://酒店总经理
                $where['u.hotel_id'] = $userInfo['hotel_id'];
                $hwhere['h.id'] = $userInfo['hotel_id'];
                break;

        }
        $row = M('user') ->alias('u')
            ->join(' LEFT JOIN yx_hotel as h ON h.id=u.hotel_id')
            ->join(' LEFT JOIN yx_role as r ON u.role_id=r.id')
            ->field('u.* , r.name as rname , h.name as hname,h.img as himg')
            ->where($where)
            ->order('u.id desc')
            ->select();
        foreach ($row as $key => &$value) {
            $himg = explode(',', $value['himg']);
            $value['himg'] = $himg[0];
            $img = explode(',', $value['img']);
            $value['img'] = $img[0];
        }
        $hotel = M('Hotel') ->alias('h')
            ->join('yx_hotel_get as hg ON h.id=hg.h_id')
            ->where($hwhere)
            ->field('h.name,h.id')
            ->select();
        $role = M('role')->where(array('type'=>2))->field('name,id')->select();
        $this->assign('rows',$row);
        $this->assign('hotel', $hotel);
        $this->assign('role', $role);
        $this->display();
    }

    //展示列表
    public function indexOld(){
        $role_id = $_SESSION['USERINFO']['role_id'];
        $user = $_SESSION['USERINFO'];
        //销售人员
        if($role_id==2){
            $like = I('like');
            //判断有没有搜索用户类型
            if(I('type')!=null) {
                $wheres['u.role_id']= I('type');
                $wheres['hg.sale_id']= $user['id'];
                $wheres['hg.is_default']=1;
                $wheres['u.status']=1;
                $wheres['h.status']=1;
                $wheres['u.type']=2;
                $wheres['h.is_sign']=1;
            }
            //判断有没有搜索酒店
            if(I('hotel')!=null) {
                $wheres['u.hotel_id']=I('hotel');
                $wheres['hg.sale_id']= $user['id'];
                $wheres['hg.is_default']=1;
                $wheres['u.status']=1;
                $wheres['h.status']=1;
                $wheres['u.type']=2;
                $wheres['h.is_sign']=1;
            }
            //判断有没有搜索框
            if($like!=null) {
                $wheres['u.real_name']=array('u.status'=>1,'u.type'=>2,'like','%'.$like.'%');
                $wheres['hg.sale_id']= $user['id'];
                $wheres['hg.is_default']=1;
                $wheres['u.status']=1;
                $wheres['h.status']=1;
                $wheres['u.type']=2;
                $wheres['h.is_sign']=1;
            }
            if($like!=null || I('hotel')!=null || I('type')!=null){
                $row = M('user') ->alias('u')
                    ->join('yx_hotel_get as hg ON hg.h_id=u.hotel_id')
                    ->join('yx_hotel as h ON h.id=hg.h_id')
                    ->join('yx_role as r ON u.role_id=r.id')
                    ->field('u.* , r.name as rname , h.name as hname,h.img as himg')
                    ->order('h.id desc')
                    ->where($wheres)
                    ->select();
            }else{
                //查询出自己认领的酒店下面人员
                $row = M('user') ->alias('u')
                    ->join('yx_hotel_get as hg ON hg.h_id=u.hotel_id')
                    ->join('yx_hotel as h ON h.id=hg.h_id')
                    ->join('yx_role as r ON u.role_id=r.id')
                    ->field('u.* , r.name as rname , h.name as hname,h.img as himg')
                    ->where(array('h.status'=>1,'hg.sale_id'=>$user['id'],'hg.is_default'=>1,'u.status'=>1,'u.type'=>2 ,'h.is_sign'=>1))
                    ->order('h.id desc')
                    ->select();
            }

            //自己认领的酒店作为添加选择
            $hotel = M('hotel_get') ->alias('hg')
                ->join(' right join yx_hotel as h ON h.id=hg.h_id')
                ->where(array('hg.is_default'=>1,'hg.sale_id'=>$user['id'],'h.is_sign'=>1,'h.status'=>1))
                ->field('h.name,h.id')
                ->select();
            //将当前列表的url保存到cookie中,为了 删除,添加,修改状态,编辑 之后跳转到该地址
            cookie('__forwards__', $_SERVER['REQUEST_URI']);
            //        $this->assign('hoteltype',$hoteltype);
            $role = M('role')->where(array('type'=>2))->field('name,id')->select();
            $this->assign('row', $row);
            $this->assign('hotel', $hotel);
            $this->assign('role', $role);
            $this->display('index');
        }else{
            //搜索
            $like = I('like');
            $where['yx_user.real_name']= ['like','%'.$like.'%'];
           if ($role_id==3){
                //销售经理
                $where['yx_user.role_id']=2;
                $where['yx_user.status']=1;
               $where['yx_hotel.status']=1;
                $hotel = M('hotel')->field('name,id')->select();
            }elseif ($role_id==12){
                //酒店总经理
                $where['yx_role.type']=2;
                $where['yx_user.status']=1;
                $where['yx_hotel.status']=1;
                $where['yx_user.hotel_id']=$user['hotel_id'];
                $hotel = M('hotel')->where(['id'=>$user['hotel_id']])->field('name,id')->select();
            }elseif ($role_id==9){
                //平台总经理
                $where['yx_user.status']=1;
                $where['yx_hotel.status']=1;
                $hotel = M('hotel')->field('name,id')->select();
            }

            if(I('hotel') !== ''){
                $where['yx_hotel.id'] =I('hotel');
            }
            if(I('type') !== ''){
                $where['yx_user.role_id'] =I('type');
            }
//            $model = 'SaleView';
            //$row = D('user')->getList($model);
            $row = M('user')
                ->join('yx_role  ON yx_role.id=yx_user.role_id')
                ->join('yx_hotel  ON yx_hotel.id=yx_user.hotel_id')
                ->field('yx_user.* , yx_role.name as rname ,yx_hotel.name as hname , yx_hotel.img as himg')
                ->where($where)
                ->order('yx_user.id desc')
                ->select();
            //将当前列表的url保存到cookie中,为了 删除,添加,修改状态,编辑 之后跳转到该地址
            cookie('__forwards__', $_SERVER['REQUEST_URI']);
            $role = M('role')->where(array('type'=>2))->field('name,id')->select();
            $this->assign('row', $row);
            $this->assign('hotel', $hotel);
            $this->assign('role', $role);
            $this->display('index');
        }
    }


    /**
     * 加载更多数据
     */
    public function show_more_data($key, $num = 10){
        $get_datas = D("user")->showMoreDatas($key,$num);
        //var_dump($get_datas);exit;
        if ($get_datas) {
            $this->get_ajax_return(1,'加载成功！',$get_datas);
        } else {
            $this->get_ajax_return(0,'加载失败！');
        }
    }

        //跳转到添加页面
    public function add(){
        $role_id = $_SESSION['USERINFO']['role_id'];
        $user = $_SESSION['USERINFO'];
        //销售人员
        if($role_id==2){
            $hotel = M('hotel_get') ->alias('hg')
                ->join('yx_hotel as h ON h.id=hg.h_id')
                ->where(array('hg.is_default'=>1,'hg.sale_id'=>$user['id'],'h.status'=>1))
                ->field('h.name,h.id')
                ->select();
            //$hotel=M('hotel')->select();
            $role=M('role')->where(['type'=>2])->select();
        }elseif ($role_id==12){
            //酒店总经理
            $hotel = M('hotel')->where(['id'=>$user['hotel_id']])->field('name,id')->select();
            $role=M('role')->where(['type'=>2])->select();
        }
        $this->assign('hotel', $hotel);
        $this->assign('role', $role);
        $this->display();
    }

    //跳转到修改页面
    public function modify(){
        $role_id = $_SESSION['USERINFO']['role_id'];
        $userid = $_SESSION['USERINFO'];
        //销售人员
        if($role_id==2) {
            $uid = I('get.id');
            $user = M('user')->where(['id' => $uid])->find();
            $hotel = M('hotel_get') ->alias('hg')
                ->join('yx_hotel as h ON h.id=hg.h_id')
                ->where(array('hg.is_default'=>1,'hg.sale_id'=>$userid['id'],'h.status'=>1))
                ->field('h.name,h.id')
                ->select();
            $role = M('role')->where(['type'=>2])->select();
            $data_hotel=M('hotel')->where(['hotel'=>$user['h_id']])->field('name,id')->find();
            $data_role = M('role')->where(['type'=>2,'id'=>$user['role_id']])->field('name,id')->find();
            $this->assign('user', $user);
            $this->assign('hotel', $hotel);
            $this->assign('role', $role);
            $this->assign('data_role', $data_role);
            $this->assign('data_hotel', $data_hotel);
            $this->display('edit');
        }elseif ($role_id==3){
            //销售经理
            $uid = I('get.id');
            $user = M('user')->where(['id' => $uid])->find();
            $hotel = M('hotel')->where(['id'=>$user['hotel_id']])->field('name')->find();
            $role = M('role')->where(['id'=>$user['role_id']])->field('name')->find();
            $this->assign('user', $user);
            $this->assign('hotel', $hotel);
            $this->assign('role', $role);
            $this->display('sel');
        }elseif ($role_id==12){
            //酒店总经理
            $uid = I('get.id');
            $user = M('user')->where(['id' => $uid])->find();
            $hotel = M('hotel')->where(['id'=>$user['hotel_id']])->field('name,id')->select();
            $role = M('role')->where(['type'=>2])->select();
            $data_hotel=M('hotel')->where(['hotel'=>$user['h_id']])->field('name,id')->find();
            $data_role = M('role')->where(['type'=>2,'id'=>$user['role_id']])->field('name,id')->find();
            $this->assign('data_role', $data_role);
            $this->assign('data_hotel', $data_hotel);
            $this->assign('user', $user);
            $this->assign('hotel', $hotel);
            $this->assign('role', $role);
            $this->display('edit');
        }elseif ($role_id==9){
            //平台总经理
            $uid = I('get.id');
            $user = M('user')->where(['id' => $uid])->find();
            $hotel = M('hotel')->where(['id'=>$user['hotel_id']])->field('name')->find();
            $role = M('role')->where(['id'=>$user['role_id']])->field('name')->find();
            $this->assign('user', $user);
            $this->assign('hotel', $hotel);
            $this->assign('role', $role);
            $this->display('sel');
        }
    }

    //添加数据
    public function edit()
    {
        $id = $_POST['id'];
        //添加
        if (empty($id)) {

            if (!D('User')->create()) {
                // 如果创建失败 表示验证没有通过 输出错误提示信息
               ajax_return(0, D('User')->getError());
            } else {
                $model = M('user');
                $data = $_POST;
                $s = chr(rand(97, 122)) . rand(1, 9) . chr(rand(97, 122)) . rand(1, 9);;
                $model->salt = $s;
                $model->password = md5(md5($data['password']) . $s);
                $model->name = $data['name'];
                $model->real_name = $data['real_name'];
                $model->mobile = $data['mobile'];
                $model->hotel_id = $data['hid'];
                $model->role_id = $data['rid'];
                $model->type = 2;
                $model->add();
                ajax_return(1,'添加成功',U('index'));
            }
        } //编辑
        else {
            if (!D('User')->create()) {
                // 如果创建失败 表示验证没有通过 输出错误提示信息
               ajax_return(0, D('User')->getError());
               exit;
            }

            //判断是否修改密码
            $model = M('user')->where(['id' => $id]);
            $data = $_POST;
            if (!empty($data['password'])) {
                $paw = I('post.password');
                $s = chr(rand(97, 122)) . rand(1, 9) . chr(rand(97, 122)) . rand(1, 9);;
                $model->salt = $s;
                $model->password = md5(md5($paw) . $s);
            } else {
                $pwd = I('post.pwd');
                $model->password = $pwd;
            }
            $model->name = $data['name'];
            $model->real_name = $data['real_name'];
            $model->mobile = $data['mobile'];
            $model->hotel_id = $data['hid'];
            $model->role_id = $data['rid'];
            $model->save();
            ajax_return(1,'修改成功',U('index'));
        }

    }

    //删除
    public function del(){
        $model=M('user')->where(['id'=>$_GET['id']]);
        $model->status=-1;
        $model->save();
        $this->redirect('index');

    }

}