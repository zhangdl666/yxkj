<?php
/**
 * Created by PhpStorm.
 * User: hjw
 * Date: 2017/9/21
 * Time: 13:48
 */

namespace Wx\Controller;
use Pc\Model\HotelModel;
use Think\Controller;

class HotelController extends BaseController
{

    //展示酒店信息
    public function index()
    {
        $like = $_GET['like'];
        if($like){
           $wheres['h.name']= ['like','%'.$like.'%'];
           $this->assign('wlike',$like); 
        }
        
        //搜索条件以及前端显示
        if(I('type') == -1){
            $type['name'] = '未认领';
            $type['is_get'] = -1;
            $wheres['h.is_get'] = 0;
            $this->assign('wis_get',-1);
        }elseif(I('type') == 1){
            $type['name'] = '已认领';
            $type['is_get'] = 1;
            $wheres['h.is_get'] = 1;
            $this->assign('wis_get',1);
        }
        if(I('get.hotel') !== ''){
            $hoteltype = D('HotelType') -> where(array('id' =>I('get.hotel')))->field('id,name') ->find();
            $wheres['h.ht_id'] =I('get.hotel');
            $this->assign('wht_id',I('get.hotel'));
        }
        $hotel = M('HotelType') ->field('id,name') ->order('sort,ctime desc') ->select();
        $this->assign('hoteltype',$hoteltype);
        $this->assign('type',$type);
        $this->assign('hotel',$hotel);
        $row = M('hotel')->alias('h')
            ->join('yx_hotel_type as ht  ON ht.id=h.ht_id')
            ->field('ht.name as tname ,h.*')
           ->where($wheres)
            ->order('h.id desc')
            ->select();
        foreach($row as $key => $val){
            if($val['img']){
               $imgs = explode(',', $val['img']);
               $row[$key]['img'] = $imgs[0];
            }
        }
        $this->assign('like',$like);
        $this->assign('row',$row);
        $this->display('index');
    }

    /**
     * 加载更多数据
     */
    public function show_more_data($key, $num = 10){
        $get_datas = D("hotel")->showMoreDatas($key,$num);
        if ($get_datas) {
            $this->get_ajax_return(1,'加载成功！',$get_datas);
        } else {
            $this->get_ajax_return(0,'加载失败！');
        }
    }

    //查看详情
    public function jumpsel(){
        $hid=$_GET['id'];
        $hrt =M('hotel_room_type')->alias('h')
            ->join('yx_room_type as r ON r.id = h.rt_id')
            ->where(['h.h_id'=>$hid])
            ->field('r.name,h.room_num,r.id')
            ->select();
        $hotel =M('hotel')->where(['id'=>$hid])->find();
        // 认领有效期
        $date = HotelModel::claimHotelDate($hid);
        // 历史认领
        $where['l.id'] = $hid;
        $where['p.is_default'] = 0;
        $sale_name = M(HotelModel::TABLENAME_HOTEL)
                            ->alias('l')
                            ->join('left join ' . C('DB_PREFIX') . \Pc\Model\HotelGetModel::TABLENAME . ' as p on p.h_id = l.id')
                            ->join('left join ' . C('DB_PREFIX') . HotelModel::TABLENAME_USER . ' as s on p.sale_id = s.id')
                            ->field('GROUP_CONCAT(s.real_name) as sale_name')
                            ->where($where)
                            ->find();
        // 查看
        if($hotel['img']){
            $hotel['himgs'] = explode(',', $hotel['img']);
        }
        $htype =M('hotelType')->where(['id'=>$hotel['ht_id']])->field('name,id')->find();
        $sale = M('hotel_get')->alias('hg')
            ->join('yx_user as u  ON u.id=hg.sale_id')
            ->field('hg.*,u.*,u.real_name as name')
            ->where(['hg.h_id'=>$hid,'is_default'=>1])
            ->find();
        $this->assign('sale_name',$sale_name['sale_name']);
        $this->assign('date',$date);
        $this->assign('htype', $htype);
        $this->assign('hotel', $hotel);
        $this->assign('hrt', $hrt);
        $this->assign('hid', $hid);
        $this->assign('sale', $sale);
        $this->display('sel');
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
        //酒店表
        $hotel=D('hotel');
        $hotel->startTrans();
        $data=$_POST;
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
            $data['provice']= $pcc[0];
            $data['city'] 	= $pcc[1];
            $data['county'] = $pcc[2];
            if($data['status'] && $data['sale']){
                $data['is_get'] = 1;
            }else{
                $data['is_get'] = 0;
            }
            //编号
            $data['sn'] = get_reimbursement_sn();
            $hid = $hotel->add($data);

            /*$model=M('Hotel')->where(['id'=>$hid]);
            $model->sn=date('Ymd') . str_pad($hid, 6, "0", STR_PAD_LEFT);
            $model->save();*/
            if($hid){
                if($data['status'] && $data['sale']){
                    $hotel_get=M('hotel_get');
                    $hotel_get->sale_id=$data['sale'];
                    $hotel_get->h_id=$hid;
                    $hotel_get->ctime=time();
                    $hotel_get->is_default=1;
                    $hotel_get->add();
                }elseif($data['status']){
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
                $hrt=M('hotelRoomType');
                foreach ($data['rname1'] as $key=>$value) {
                    if($value[0]){
                        $hrt->h_id=$hid;
                        $hrt->rt_id=$key;
                        $hrt->room_num=$value[0];
                        $hrt->add();
                    }
                }

                $hotel->commit();
                ajax_return(1,'添加成功','index');
            }else{
                $hotel->rollback();
                ajax_return(0,'添加失败');
            }
        }
    }

    //到修改页面
    public function jumpedit(){
        $hid=$_GET['id'];
        $htype =M('RoomType') ->field('name,id')->select();
        $hrt =M('hotel_room_type')->alias('h')
            ->join('yx_room_type as r ON r.id = h.rt_id')
            ->where(['h.h_id'=>$hid])
            ->field('r.name,h.room_num,r.id')
            ->select();
        $hotel =M('hotel')->where(['id'=>$hid])->find();
        if($hotel['img']){
            $hotel['imgs'] = explode(',', $hotel['img']);
        }
        $sale =M('user')->where(['role_id'=>2]) ->field('real_name as name,id')->select();
        $sid = M('hotel_get')->alias('hg')
            ->join('yx_user as u  ON u.id=hg.sale_id')
            ->where(['hg.h_id'=>$hid,'is_default'=>1])
            ->find();
        $ht =M('hotelType')->where(['id'=>$hotel['ht_id']])->field('name,id')->find();
        $hotelType=M('hotelType')->field('name,id')->select();
        //上传图片所需
        $appid = C('WEIXIN.AppID');
        $appSecret = C('WEIXIN.AppSecret');
        $jssdk = new JSSDK($appid,$appSecret);
        $sign_package = $jssdk->getSignPackage();
        $this->assign($sign_package);
        foreach ($htype as &$val){
            foreach ($hrt as $v){
                if($v['id'] == $val['id']){
                    $val['room_num'] = $v['room_num'];
                    break;
                }
            }
        }
        $this->assign('hotelType',$hotelType);
        $this->assign('htype', $htype);
        $this->assign('sid', $sid);
        $this->assign('ht', $ht);
        $this->assign('hotel', $hotel);
        $this->assign('hid', $hid);
        $this->assign('sale', $sale);
        $this->display('edit');
    }

    //修改数据
    public function edit(){
        M('Hotel')->startTrans();
        $data=$_POST;
        $oldHotel=M('Hotel')->where(array('id'=>$data['id']))->find();
        //$hid = $_SESSION['USERINFO']['hotel_id'];
        //酒店表
        $pcc  = explode(',', $data['pcc_area']);
//        if($data['img']==null){
            //$img=M('hotel')->where(['id'=>$data['id']])->field('img')->find();
            $data['provice']= $pcc[0];
            $data['city'] 	= $pcc[1];
            $data['county'] = $pcc[2];
            if(($data['status'] && $data['sale'] && empty($data['is_sign'])) || ($data['sale'] && empty($data['status']))){
                $data['is_get'] = 1;
                $data['get_time'] = time();
            }else{
                $data['is_get'] = 0;
            }
            $data['utime'] = time();
            $result = M('Hotel')->where(['id'=>$data['id']])->save($data);
            //酒店客房类型关联表
            M('hotelRoomType')->where(['h_id'=>$data['id']])->delete();
            $hrt=M('hotelRoomType');
            foreach ($data['rname1'] as $key=>$value) {
                if($value[0]){
                    $hrt->h_id=$data['id'];
                    $hrt->rt_id=$key;
                    $hrt->room_num=$value[0];
                    $hrt->add();
                }
            }

            //是否之前已经分配
            $is_ditribution = M('HotelGet')->where(array('h_id' => $data['id'], 'is_default' => 1))->getField('sale_id');

            if($data['status'] && $data['sale'] && ($data['sale'] != $is_ditribution) && empty($data['is_sign'])){
                M('HotelGet')->where(array('h_id'=>$data['id']))->save(array('is_default'=>0));

                /*$hotel_get=M('hotel_get')->where(['h_id'=>$data['id']]);
                $hotel_get->sale_id=$data['sale'];
                $hotel_get->ctime=time();
                $hotel_get->save();*/
                $hg_data['h_id']    = $data['id'];
                $hg_data['sale_id'] =$data['sale'];
                $hg_data['ctime']   =time();
                M('hotel_get')->add($hg_data);
            }elseif($data['status'] && $data['sale'] && empty($is_ditribution) && empty($data['is_sign'])){
                $hg_data['h_id']    = $data['id'];
                $hg_data['sale_id'] =$data['sale'];
                $hg_data['ctime']   =time();
                M('hotel_get')->add($hg_data);
            }elseif(empty($data['is_sign']) && empty($data['sale'])){
                M('HotelGet')->where(array('h_id'=>$data['id']))->save(array('is_default'=>0));
            }

            //酒店编号
            $hsno = M('Hotel')->getFieldById($data['id'],'sn');
            if(empty($data['status']) || ($data['status'] && $data['sale'])){
                //平台录入人员修改
                if(session('USERINFO.role_id')==8){
                    $user_id = M('User')->where(array('role_id'=>2,'status'=>1))->getField('id');
                    not_oper($hsno,$user_id);
                }else{
                    not_oper($hsno,session('USERINFO.id'));
                }
            }

            //是否推送认领消息
            if (empty($is_ditribution) && $data['status'] && empty($data['sale']) && empty($data['is_sign'])) {
                //给销售人员推送消息
                $user_idarr = M('User')->field('id')->where(array('role_id' => 2, 'status' => 1))->select();
                if ($user_idarr) {
                    $oper_title = '待处理酒店认领工单';
                    $msg_content = '您有一个酒店待认领,编号：' . $hsno;
                    $user_ids = implode(',', i_array_column($user_idarr, 'id'));
                    $oper_url = 'ClaimHotel/index';
                    has_oper($msg_content, $user_ids, $oper_url, $oper_title);
                }
            }

            if($result){
                M('Hotel')->commit();
                ajax_return(1,'修改成功','/Wx/Hotel/index');
            }else{
                M('Hotel')->rollback();
                ajax_return(0,'修改失败');
            }

//        }else{}

    }
}