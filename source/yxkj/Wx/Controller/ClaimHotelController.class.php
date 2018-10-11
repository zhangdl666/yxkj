<?php
/**
 * ClaimHotelController.class.php
 * 后台 首页
 * @author 刘小伟
 * @date   2017-09-19 10:59
 */
namespace Wx\Controller;
use Pc\Model\HotelUserModel;
use Wx\Model\HotelGetModel;
use Wx\Model\UserObjModel;
class ClaimHotelController extends BaseController{
    protected $model ='HotelGet';
    //首页数据
    public function index()
    {
        //搜索条件以及前端显示
        if(I('get.type') == -1){
            $type['name'] = '未认领';
            $type['is_get'] = -1;
            $wheres['hotel.is_get'] = 0;
            $this->assign('wis_get',-1);
        }elseif(I('get.type') == 1){
            $type['name'] = '已认领';
            $type['is_get'] = 1;
            $wheres['hotel.is_get'] = 1;
            $this->assign('wis_get',1);
        }
        $like =I('get.like');
        if(!empty($like)){
            $wheres['hotel.name'] =  array('like', "%" . $like . "%");
            $this->assign('wlike',$like);
        }        
        if(I('get.hotel') !== ''){
            $hoteltype = D('HotelType') -> where(array('id' =>I('hotel'))) ->field('id,name') ->find();
            $wheres['hotel.ht_id'] =I('get.hotel');
            $this->assign('wht_id',I('get.hotel'));
        }
        $wheres['hotel.status'] = 1;
        $data = 'HotelcHoteltypeHotelView';
        $data_list =D('Promotion')->getList($data,$wheres,$order_str='hotel.id desc');
        cookie('__forwards__', $_SERVER['REQUEST_URI']);
        //处理酒店图片，默认显示第一张
        foreach ($data_list['datas'] as $key =>$val){
            $img = explode(',',$val['img']);
            if(!file_exists($_SERVER['DOCUMENT_ROOT'].$img[0])){
                $img[0]= '';
            }
            $data_list['datas'][$key]['img'] = $img[0];
        }

        // 已认领代表别人已经认领的酒店。不能出现销售人员本人认领的酒店。
            $UserObjModel = new UserObjModel();
            $user_id = $UserObjModel->getUserId();
            foreach ($data_list['datas'] as $keys=>$vals){
                $data_list['datas'][$keys]['hotel_status'] = HotelUserModel::checkUserStatus($user_id, $vals['id']);
                if ($data_list['datas'][$keys]['hotel_status'] == HotelUserModel::STATUS_ONE) {
                    unset($data_list['datas'][$keys]);
                }
            }

        $hotel = HotelGetModel::Hoteltype();
        $this->assign('items',$data_list['datas']);
        $this->assign('hoteltype',$hoteltype);
        $this->assign('type',$type);
        $this->assign('role',$_SESSION['USERINFO']['role_id']);
        $this->assign('hotel',$hotel);
        $this->assign('like',$like);

        $this->display();
    }
    //查看前的数据准备
    public function beforeedit(){
        $id = I('id');
        $where = array('o.id' =>$id);
        $user_id = $_SESSION['USERINFO']['id'];
        $data =HotelGetModel::oneDate($where);
        for ($i=0;$i<count($data['imgs']);$i++){
            if(!file_exists($_SERVER['DOCUMENT_ROOT'].$data['imgs'][$i])){
                $data['imgs'][$i]='';
            }
        }
        //获取当前人员与该酒店之间的关系
        $data['status_id'] =HotelUserModel::checkUserStatus($user_id,$data['id']);
       $data['role'] =$_SESSION['USERINFO']['role_id'];
        $this ->assign('data',$data);
    }
    public function edit(){
        $this ->beforeedit();
        $this ->display();
    }
    //我的认领
    public function ouser(){
        $id = $_SESSION['USERINFO']['id'];
        $data = HotelGetModel::getMyListHotel($id);
        //认领有效期
      foreach ($data as $key=>$val){
          $data[$key]['imgs'] =explode(',',$val['img']);
          $data[$key]['ytime'] = 30-floor((time()-$val['ctime'])/86400);
      }

        $hotel = HotelGetModel::Hoteltype();
        $this->assign('hotel', $hotel);
        $this->assign('items', $data);
        $this->display();
    }
    //编辑前所需数据
    public function compile(){
         $this ->beforeedit();
        //上传图片所需
        $appid = C('WEIXIN.AppID');
        $appSecret = C('WEIXIN.AppSecret');
        $jssdk = new JSSDK($appid,$appSecret);
        $sign_package = $jssdk->getSignPackage();
        $this->assign($sign_package);
        $hotel = HotelGetModel::Hoteltype();
        $this->assign('hotel', $hotel);
        $this ->display();
    }
    /**
     * 数据编辑
     */
    public function compileIn(){
        $data = I('post.');
        $pcc  = explode(',', $data['pcc_area']);
        $data['provice']= $pcc[0];
        $data['city'] 	= $pcc[1];
        $data['county'] = $pcc[2];
        $where['h_id'] = $data['id'];
        unset($data['pcc_area']);
        if(empty($data['hrtname'])){
            ajax_return(0,'请选择客房类型');
            exit;
        }else{
            //去掉多余的
            for ($i=0; $i < count($data['room_nums']); $i++) {
                if(empty($data['room_nums'][$i]) || $data['room_nums'][$i] == 0){
                    continue;
                }
                $room_nums[] = $data['room_nums'][$i];
            }
            unset($data['room_nums']);

            for($j=0; $j < count($data['hrtname']); $j++) {
                if(is_numeric($room_nums[$j]) ){
                if(empty($room_nums[$j]) ){
                    ajax_return(0,'请完善客房类型信息');
                    exit;
                }
                /* 酒店客房类型数量 */
                $ht_arr[] = array('h_id'=>$data['id'],'rt_id'=>$data['hrtname'][$j],'room_num'=>$room_nums[$j]);
                $data['room_num'] += $room_nums[$j];
                }else{
                    ajax_return(0,'房间数必须输入数字');
                    exit;
                }
            }
            unset($data['hrtname']);
        }
        /* 修改酒店信息 */
        $result =D('HotelGet') ->editor($data);
        //先删除后添加
        M('HotelRoomType') ->where(array('h_id'=>$data['id']))->delete();
        M('HotelRoomType') ->addAll($ht_arr);
        if(is_numeric($result)){
            ajax_return(1,'编辑成功',U('ouser'));
            return true;
        }else{
            ajax_return(0,$result);
            return false;
        }
    }
    /**
     *
     * 认领酒店
     */
    public function userClaim()
    {
        $hotel_id = I('post.id', 0, 'intval');
        $userid = $_SESSION['USERINFO']['id'];
        $info = HotelUserModel::getUserClaim($hotel_id,$userid);

        if($info){
            //酒店编号
            $hsno = M('Hotel')->getFieldById($hotel_id,'sn');
            not_oper($hsno,session('USERINFO.id'));  
        }

        $this->ajaxReturn($info);
    }

    /**
     * 取消认领
     */
    public function userNoClaim()
    {
        $hotel_id = I('post.id', 0, 'intval');
        $userid = $_SESSION['USERINFO']['id'];
        $info = HotelUserModel::getNoUserClaim($hotel_id, $userid);

        if($info){
            //酒店编号
            $hsno = M('Hotel')->getFieldById($hotel_id,'sn');
            not_oper($hsno,session('USERINFO.id'));

            //给销售人员推送消息
            $user_idarr = M('User')->field('id')->where(array('role_id'=>2,'status'=>1))->select();
            if($user_idarr){
                $oper_title = '待处理酒店认领工单';
                $msg_content= '您有一个酒店待认领,编号：'.$hsno;
                $user_ids = implode(',', i_array_column($user_idarr,'id'));
                $oper_url = 'ClaimHotel/index';
                has_oper($msg_content,$user_ids,$oper_url,$oper_title);
            }
        }

        $this->ajaxReturn($info);
    }
    /*
               * 未认领酒店数
               * */
    public function getNoHaddleNum(){
        if($_SESSION['USERINFO']['role_id'] == 2){
            $number = M('Hotel') -> where(array('is_get'=> 0,'status' =>1 )) ->count();

        }else{
            $number =0;
        }
        return $number;
    }


}