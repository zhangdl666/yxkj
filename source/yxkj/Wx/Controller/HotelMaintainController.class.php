<?php
/**
 * Created by PhpStorm.
 * User: hjw
 * Date: 2017/10/13
 * Time: 15:53
 */

namespace Wx\Controller;
use Pc\Model\HotelModel;
use Think\Controller;

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
        //$hid = 13;
        $htype =M('hotelType') ->field('name,id')->select();
        $hotel =M('hotel')->where(['id'=>$_SESSION['USERINFO']['hotel_id']])->find();
        if($hotel['img']){
            $hotel['imgs'] = explode(',', $hotel['img']);
        }
        $ser=explode(',',$hotel['service_ids']);
        $service =M('hotelSerivce')->field('name,id')->select();

        $this->assign('htype', $htype);
        $this->assign('hotel', $hotel);
        //$this->assign('hid', $hid);
        $this->assign('ser', $ser);
        $this->assign('service', $service);
        $this->display('maintain');

    }

    //修改数据
    public function edit(){
        $post = I('post.');
        $rules = HotelModel::getEditRules();
        $data = array(
            'id'=>$post['id'],
            'name' =>$post['name'],
            'ht_id'=>$post['ht_id'],
            'area'=>$post['area'],
            'tell'=>$post['tell'],
            'img'=>$post['img'],
            'shang_name'=>$post['shang_name'],
            'shang_tell'=>$post['shang_tell'],
        );
        $data['service_ids'] = implode(',',$post['service']);
        $addressData=explode(',', $post['pcc_area']);
        $data['provice'] = $addressData[0];
        $data['city'] 	= $addressData[1];
        $data['county'] = $addressData[2];
        $model = M('hotel');
        if(!$model->validate($rules)->create($data)){
            ajax_return(0,$model->getError());
            return;
        }else{
            $res = $model->save($data);
            if($res){
                ajax_return(1,'操作成功',U('Index/index'));
                return;
            }else{
                ajax_return(0,'酒店信息修改失败');
                return;
            }
        }
    }
}