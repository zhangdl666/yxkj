<?php
/**
 * Created by PhpStorm.
 * User: hjw
 * Date: 2017/10/13
 * Time: 15:53
 */

namespace Pc\Controller;
use Pc\Model\HotelModel;

class HotelMaintainController extends BaseController
{

    //初始化
    public function _initialize()
    {
        $this->model = 'hotelType';
        parent::_initialize();
    }

    public function Maintain()
    {
        $hid = $_SESSION['USERINFO']['hotel_id'];
        $htype =M('hotelType') ->field('name,id')->select();
        $hotel =M('hotel')->where(['id'=>$hid])->find();
        $ser=explode(',',$hotel['service_ids']);
        if($hotel['img']){
            $hotel['imgs'] = explode(',',$hotel['img']);
        }
        $service =M('hotelSerivce')->field('name,id')->select();
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        $this->assign('htype', $htype);
        $this->assign('hotel', $hotel);
        $this->assign('hid', $hid);
        $this->assign('ser', $ser);
        $this->assign('service', $service);
        $this->display('maintain');
    }

    //修改数据
    public function edit(){
        $rules = HotelModel::getEditRules();
        if (!D('Hotel')->validate($rules)->create($_POST)) {
           $info =  D('Hotel')->getError();
            ajax_return(0,$info);

        }else{
            $hid = $_POST['h_id'];
            $data = I('post.');
            $service=implode(',',$data['service']);
            if($data['img']==null){
                $img=M('hotel')->where(['id'=>$hid])->field('img')->find();
                $hotel=M('hotel')->where(['id'=>$hid]);
                $data['provice']= $data['provice'];
                $data['city'] 	= $data['city'];
                $data['county'] = $data['county'];
                $hotel->provice=$data['provice'];
                $hotel->city=$data['city'];
                $hotel->county=$data['county'];
                $hotel->ht_id=$data['ht_id'];
                $hotel->name=$data['name'];
                $hotel->area=$data['area'];
                $hotel->tell=$data['tell'];
                $hotel->shang_name=$data['shang_name'];
                $hotel->shang_tell=$data['shang_tell'];
                $hotel->service_ids=$service;
                $hotel->img=$img;
                $res=$hotel->save();
            }else{
                $hotel=M('hotel')->where(['id'=>$hid]);
                $data['provice']= $data['provice'];
                $data['city'] 	= $data['city'];
                $data['county'] = $data['county'];
                $hotel->provice=$data['provice'];
                $hotel->city=$data['city'];
                $hotel->county=$data['county'];
                $hotel->ht_id=$data['ht_id'];
                $hotel->name=$data['name'];
                $hotel->area=$data['area'];
                $hotel->tell=$data['tell'];
                $hotel->shang_name=$data['shang_name'];
                $hotel->shang_tell=$data['shang_tell'];
                $hotel->service_ids=$service;
                $hotel->img=$data['img'];
                $res=$hotel->save();
            }
                ajax_return(1,'操作成功',cookie('__forward__'));

        }

    }
}