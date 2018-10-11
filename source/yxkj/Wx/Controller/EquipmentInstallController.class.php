<?php
/**
 * Created by PhpStorm.
 * User: redsabre
 * Date: 2017/9/25
 * Time: 9:57
 */
//设备安装
namespace Wx\Controller;
use Org\Net\Http;
use Wx\Controller\BaseController;

class EquipmentInstallController extends BaseController{
    /**
     * 初始化
     */
    public function _initialize(){
        $this->model = 'OperOrder';
        parent::_initialize();
    }

    /**
     * 提供查询条件
     * @param  array  $wheres 查询条件
     */
    protected function _set_wheres(&$wheres){
        $wheres['type'] = 1;

        /* 角色 */
        $role_id = session('USERINFO.role_id');
        //平台工程人员
        if($role_id == 4){
            $wheres['u_id'] = session('USERINFO.id');
            $wheres['status'] = 2;
            $this->assign('estatus',2);
            $this->assign('install',1);
            $flagWhere=array(
                'type'=>1,
                'status'=>2,
                'u_id'=>$wheres['u_id'],
            );
            $flagNumRes=$this->model->where($flagWhere)->count();
            if($flagNumRes != 0){
                $this->assign('flagTwo',1);
            }
        }
        //酒店工程经理
        elseif($role_id == 11) {
            $wheres['h_id'] = session('USERINFO.hotel_id');
            $wheres['status'] = array('in','1,2');
            $this->assign('estatus',2);
            $this->assign('install',1);
            $flagWhere=array(
                'type'=>1,
                'status'=>3,
                'h_id'=>$wheres['h_id'],
            );
            $flagNumRes=$this->model->where($flagWhere)->count();
            if($flagNumRes != 0){
                $this->assign('flagThree',1);
            }
        }
        //平台工程经理
        //平台总经理
        else{
            $wheres['status'] = 1;
            $this->assign('estatus',1);
            $this->assign('distribution',1);
            if($role_id == 5){
                $flagWhere=array(
                    'type'=>1,
                    'status'=>1,
                );
                $flagNumRes=$this->model->where($flagWhere)->count();
                if($flagNumRes != 0){
                    $this->assign('flagOne',1);
                }
            }
        }

        //搜索
        $estatus = I('get.estatus');
        if($role_id == 11 && $estatus == 2){
            $wheres['status'] = array('in','1,2');
            $this->assign('estatus',$estatus);
        }elseif($estatus){
            $wheres['status'] = $estatus;
            $this->assign('estatus',$estatus);
        }
    }

    /**
     * 提供排序方式
     * @param string $orders 排序方式
     */
    protected function _set_order(&$orders){
        $orders = 'ctime desc';
    }

    /**
     * 查看详情
     */
    public function read(){
        $id = I('get.id');
        $info = $this->model->alias('obj')->field('obj.*,h.name as h_name,h.img as h_img,u.real_name as u_name,mobile,rtime,stime,etime')
                ->join('yx_hotel as h on h.id=obj.h_id')
                ->join('left join yx_user as u on u.id=obj.u_id')
                ->where('obj.id='.$id)->find();
        if($info['h_img']){
            $info['h_img'] = explode(',', $info['h_img']);
        }else{
            $info['h_img'][] = '/Public/Images/elogo.png';
        }
        
        if($info['status'] >= 2 && $info['img']){
            $info['imgs'] = explode(',', $info['img']);
        }
        $info['num_ratio'] = intval(($info['now_nume']/$info['nume']*100));
        if(!empty($info['etime']) && !empty($info['stime'])){
            $stime = date('Y-m-d',$info['stime']);
            $etime = date('Y-m-d',$info['etime']);
            if($stime == $etime){
                $info['datetime'] = $stime;
            }else{
                $info['datetime'] = $stime.'至'.$etime;
            }
        }elseif(!empty($info['stime'])){
            $info['datetime'] = date('Y-m-d',$info['stime']);
        }elseif(!empty($info['rtime'])){
            $info['datetime'] = date('Y-m-d',$info['rtime']);
        }
        $info['roll_back_status'] = $info['roll_back'];
        //查看是否有打回
        $info['roll_back'] = M('OrderBack')->field('content')->where(array('oo_id'=>$info['id']))->select();

        $this->assign($info);

        //所有房间
        $install_roo = M('OperInfo')->field('id,room_sno')->where(array('oo_id'=>$info['id'],'status'=>array('notin','2,3')))->select();
        //@auther:刘小伟 要求将房间数量改成监控设备数量
       // for($i=0; $i < $info['num']; $i++){
        for($i=0; $i < $info['nume']; $i++){
            if($i < count($install_roo)){
                $room_arr[] = array('room_sno'=>$install_roo[$i]['room_sno'],'open'=>1,'id'=>$install_roo[$i]['id']);
                continue;
            }
            $room_arr[] = array('open'=>0);
        }
        $this->assign('room_arr',$room_arr);
        $this->assign('estatus',I('get.status'));

        /*if($info['status'] == 2){
            //上传图片所需
            $appid = C('WEIXIN.AppID');
            $appSecret = C('WEIXIN.AppSecret');
            $jssdk = new JSSDK($appid,$appSecret);
            $sign_package = $jssdk->getSignPackage();
            $this->assign($sign_package);
        }*/


        //cookie('CODE_URL',)
        $this->display();
    }

    /**
     * 分配工单  
     */
    public function distribution(){
        $id = I('get.id');
        $status = $this->model->getFieldById($id,'status');
        if(in_array($status,array(1,2))){
            $this->assign(array('status'=>$status,'id'=>$id));
        }
        
        /* 工程人员 */
        $user_list = M('User')->field('id,real_name as name,mobile,img')->where(array('parent_id'=>session('USERINFO.id')))->select();
        foreach ($user_list as &$val){
            if(!empty($val['img']) && !file_exists($_SERVER['DOCUMENT_ROOT'].$val['img'])){
                $val['img'] = '';
            }
        }
        $this->assign('user_list',$user_list);

        $this->display();
    }

    /**
     * 扫一扫
     */
    public function oper(){
        $id = I('get.id');
        $sno = I('get.sno');
        $this->assign(array('oo_id'=>$id,'sno'=>$sno));        
        //合同签约房间类型
        $maintenance_items = D('RoomType')->alias('rt')->field('rt.id,rt.name')
                        ->join('left join yx_hc_room_equipment as hcre on rt.id=hcre.rt_id')
                        ->join('left join yx_oper_order as oo on hcre.hc_id=oo.hc_id')
                        ->where(array('oo.id'=>$id))->select();
        $this->assign('maintenance_items',$maintenance_items);

        /*if($equipment_info['type'] == 2){
            $status = '保养';
            $eoper = M('EquipmentOper')->where(array('type'=>1))->select();
            $this->assign(array('status'=>$status,'eoper_items'=>$eoper));
        }elseif($equipment_info['type'] == 3){
            $status = '维修';
            $eoper = M('EquipmentOper')->where(array('type'=>2))->select();
            $this->assign(array('status'=>$status,'eoper_items'=>$eoper));
        }else{*/
            $status = '安装';
            $this->assign(array('status'=>$status));
        /*}*/
        //中央空调品牌
        $air_items = M('Equipment')->field('id,name')->where(array('type'=>3))->select();
        $this->assign('air_items',$air_items);

        $this->display();
    }

    /**
     * 更换
     */
    public function oper_info(){
        $oo_id = I('get.oo_id');
        $id = I('get.id');
        //$sno = I('get.sno');
        $this->assign(array('oo_id'=>$oo_id,'id'=>$id));
        $oi_info = M('OperInfo')->getById($id);
        //中央空调品牌
        $oi_info['air_name'] = M('Equipment')->where(array('id'=>$oi_info['e_id']))->getField('name');

        $type = M('OperOrder')->getFieldById($oo_id,'type');
        if($type == 2){
            $status = '保养';
        }else{
            $status = '维修';
        }

        $this->assign($oi_info);
        $this->assign(array('status'=>$status,'eoper_items'=>'更换'));

        //合同签约房间类型
        $room_name = D('RoomType')->getFieldById($oi_info['rt_id'],'name');
        $this->assign('rt_name',$room_name);
        
        //合同签约房间类型
        /*$maintenance_items = D('RoomType')->alias('rt')->field('rt.id,rt.name')
                        ->join('left join yx_hc_room_equipment as hcre on rt.id=hcre.rt_id')
                        ->join('left join yx_oper_order as oo on hcre.hc_id=oo.hc_id')
                        ->where(array('oo.id'=>$oo_id))->select();
        $this->assign('maintenance_items',$maintenance_items);

        if($equipment_info['type'] == 2){
            $status = '保养';
            $eoper = M('EquipmentOper')->where(array('type'=>1))->select();
            $this->assign(array('status'=>$status,'eoper_items'=>$eoper));
        }elseif($equipment_info['type'] == 3){
            $status = '维修';
            $eoper = M('EquipmentOper')->where(array('type'=>2))->select();
            $this->assign(array('status'=>$status,'eoper_items'=>$eoper));
        }
        //中央空调品牌
        $air_items = M('Equipment')->field('id,name')->where(array('type'=>3))->select();
        $this->assign('air_items',$air_items);*/

        $this->display();
    }

    /**
     * 统计数据
     */
    public function getNoHaddleNum(){
        $userInfo=session('USERINFO');
        $where=array();
        $where['type'] = 1;
        if($userInfo['role_id'] == 5){//平台工程经理
            $where['status'] = 1;
        }elseif ($userInfo['role_id'] == 4){//平台工程人员
            $where['status'] = 2;
            $where['u_id'] = $userInfo['id'];
        }elseif ($userInfo['role_id'] == 11){//酒店工程经理
            $where['status'] = 3;
            $where['h_id'] = $userInfo['hotel_id'];
        }
        $num=$this->model->where($where)->count();
        return $num;
    }

    /**
     * 保养/维修更换设备
     */
    public function oioperation(){
        $data = I('post.');
        $old_mac = M('OperInfo')->getFieldById($data['id'],'equipment_sno');
        //此设备是否已经安装
        $is_install = M('OperInfo')->where(array('equipment_sno'=>$data['equipment_sno'],'status'=>1))->find();
        if($is_install){
           ajax_return(0,'此设备是存在,请更换其他设备');
           exit; 
        }

        $result = M('OperInfo')->where(array('equipment_sno'=>$old_mac))->save(array('equipment_sno'=>$data['equipment_sno']));
        if($result){
            $type = M('OperOrder')->getFieldById($data['oo_id'],'type');
            if($type == 2){
                $url = U('EquipmentUpkeep/index');
            }else{
                $url = U('EquipmentMaintain/index');
            }
            ajax_return(1,'更换成功',$url);
        }else{
            ajax_return(0,'更换失败');
        }        
    }

    /**
     * 处理打回
     */
    public function roll_back(){
        $re_status = $this->model->roll_back_oper();
        $this->admin_ajax_return($re_status);
    }
}