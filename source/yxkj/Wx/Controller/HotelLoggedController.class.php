<?php
/**
 * Author: ' Silent
 * Time: 2017/11/13 17:28
 */

namespace Wx\Controller;
class HotelLoggedController extends BaseController
{
    public $model = 'hotel';
    public function _initialize()
    {
        parent::_initialize();
    }

    //到添加页面
    public function logged(){
        $htype =M('hotelType') ->field('name,id')->order('id desc')->select();
        $rtype =M('roomType') ->field('name,id')->select();
        $sale =M('user')->where(['role_id'=>2]) ->field('real_name as name,id')->select();
        //上传图片所需
        $appid = C('WEIXIN.AppID');
        $appSecret = C('WEIXIN.AppSecret');
        $jssdk = new JSSDK($appid,$appSecret);
        $sign_package = $jssdk->getSignPackage();
        $this->assign($sign_package);
        $this->assign('htype', $htype);
        $this->assign('rtype', $rtype);
        $this->assign('sale', $sale);
        $this->display('add');
    }

    //添加数据
    public function add(){
        $data=$_POST;
        //酒店表
        $hotel=D('hotel');
        if (!$hotel->create()){
            // 如果创建失败 表示验证没有通过 输出错误提示信息
            ajax_return(0,$hotel->getError());
        }else{
            // 验证通过 可以进行其他数据操作
            $pcc  = explode(',', $data['pcc_area']);
            if($pcc[0]==null){
                $error='所属地区不能为空';
                ajax_return(0,$error);
                exit;
            }
            if(!empty($data['rname']) && $data['room_num'] == 0){
                $error='房间数量不能为空';
                ajax_return(0,$error);
                exit;
            }
            $data['provice']= $pcc[0];
            $data['city'] 	= $pcc[1];
            $data['county'] = $pcc[2];
            $data['sn']     = get_reimbursement_sn();
            $data['ctime']  = time();
            if($data['status'] && $data['sale']){
                $data['is_get'] = 1;
            }else{
                $data['is_get'] = 0;
            }
            $hid=M('Hotel')->add($data);

            if($data['status'] && $data['sale']){
                $hotel_get=M('hotel_get');
                $hotel_get->sale_id =$data['sale'];
                $hotel_get->h_id    =$hid;
                $hotel_get->ctime   =time();
                $hotel_get->add();
            }else{
                //给销售人员推送消息
                $user_idarr = M('User')->field('id')->where(array('role_id'=>2,'status'=>1))->select();
                if($data['status'] && empty($data['sale']) && $user_idarr){
                    $oper_title = '待处理酒店认领工单';
                    $msg_content= '您有一个酒店待认领,编号：'.$data['sn'];
                    $user_ids = implode(',', i_array_column($user_idarr,'id'));
                    $oper_url = 'ClaimHotel/index';
                    has_oper($msg_content,$user_ids,$oper_url,$oper_title);
                }
            }
            
            //酒店客房类型关联表
            M('hotelRoomType')->where(['h_id'=>$hid])->delete();
            foreach ($data['rname1'] as $key=>$value) {
                if($value[0]){
                    $hrt['h_id']    =$hid;
                    $hrt['rt_id']   =$key;
                    $hrt['room_num']=$value[0];
                    $hrt_data[] = $hrt;
                }
                
            }
            if($hrt_data){
                M('HotelRoomType')->addAll($hrt_data);
            }
            

            ajax_return(1,'添加成功','/Wx/Hotel/index');
        }
    }

    //到修改页面
    public function jumpedit(){
        $hid=$_GET['id'];
        $htype =M('hotelType') ->field('name,id')->select();
        $hrt =M('hotel_room_type')->alias('h')
            ->join('yx_room_type as r ON r.id = h.rt_id')
            ->where(['h.h_id'=>$hid])
            ->field('r.name,h.room_num,r.id')
            ->select();
        $hotel =M('hotel')->where(['id'=>$hid])->find();
        $sale =M('user')->where(['role_id'=>2]) ->field('real_name as name,id')->select();
        $sid = M('hotel_get')->alias('hg')
            ->join('yx_user as u  ON u.id=hg.sale_id')
            ->where(['hg.h_id'=>$hid])
            ->find();
        $sid['img'] = explode(',', $sid['img']);
        $ht =M('hotelType')->where(['id'=>$hotel['ht_id']])->field('name,id')->find();
        //上传图片所需
        $appid = C('WEIXIN.AppID');
        $appSecret = C('WEIXIN.AppSecret');
        $jssdk = new JSSDK($appid,$appSecret);
        $sign_package = $jssdk->getSignPackage();
        $this->assign($sign_package);
        $this->assign('htype', $htype);
        $this->assign('sid', $sid);
        $this->assign('ht', $ht);
        $this->assign('hotel', $hotel);
        $this->assign('hrt', $hrt);
        $this->assign('hid', $hid);
        $this->assign('sale', $sale);
        $this->display('edit');
    }

    //修改数据
    public function edit(){
        $data=$_POST;
        $hid = $_SESSION['USERINFO']['hotel_id'];
        //酒店表
        $pcc  = explode(',', $data['pcc_area']);
        $img=M('hotel')->where(['id'=>$hid])->field('img')->find();
        $data['provice']= $pcc[0];
        $data['city'] 	= $pcc[1];
        $data['county'] = $pcc[2];
        if($data['sale']){
            $data['is_get'] = 1;
        }

        M('Hotel')->where(['id'=>$data['id']])->save($data);

        //酒店客房类型关联表
        M('HotelRoomType')->where(['h_id'=>$data['id']])->delete();
        foreach ($data['rname1'] as $key=>$value) {
            if($value[0]){
                $hrt['h_id']    =$data['id'];
                $hrt['rt_id']   =$key;
                $hrt['room_num']=$value[0];
                $hrt_data[] = $hrt;
            }
        }
        if($hrt_data){
            M('HotelRoomType')->add($hrt_data);
        }
        
        if($data['sale']){
            M('hotel_get')->where(['h_id'=>$data['id']])->save(array('is_default'=>0));
            $hotel_get=M('hotel_get')->where(['h_id'=>$data['id']]);
            $hotel_get->sale_id= $data['sale'];
            $hotel_get->ctime  = time();
            $hotel_get->add();
        }
        $this->redirect('/Wx/Hotel/index');
    }
}