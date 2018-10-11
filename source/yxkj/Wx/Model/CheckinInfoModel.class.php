<?php
/**
 * Created by ${CONTROLLER_NAME}.
 * @auther: 刘小伟
 * Date: 2017/9/29
 * Time: 17:04
 */
namespace Wx\Controller;
use Wx\Model\RunInfoModel;

class CheckinInfoModel extends RunInfoModel {
    //房间详情
    public static function getmore($where){
     $roth =   M('CheckinInfo')
            ->alias('o')
            ->join('yx_hotel as h on h.id =o.h_id')
            ->join('yx_user as u on u.id = o.u_id')
            ->join('yx_room_type as r on r.id = o.rt_id')
            ->join('yx_oper_info as i on i.room_sno = o.room_sno')
            ->join('yx_hotel_type as t on t.id = h.ht_id')
            ->where($where)
            ->field('o.*,h.provice,h.city,h.county,h.area,h.img,h.name as hotel_name,t.name as type_name,r.name as room_name,u.name as user_name,i.status')
            ->order('o.ctime')
            ->find();
        return CheckinInfoModel::inafter($roth);
    }
    //酒店信息
    public static function hotelInformation($id){
        return M('Hotel')
                ->alias('o')
                ->join('yx_hotel_type as h on h.id = o.ht_id')
                ->field('o.name,o.img,o.provice,o.city,o.county,o.area,h.name as type_name,o.tell')
                ->where(array('o.id'=>$id))
                ->find();
    }
    //空气质量
    public function inafter($data){
        if(0<=  $data['in_pm']&& $data['in_pm'] <=  34){
            $data['in_air'] = '优';
        }elseif (35<=  $data['in_pm']&& $data['in_pm'] <= 74){
            $data['in_air'] = '良';
        }else{
            $data['in_air'] = '';
        }
        if(0<= $data['out_pm']&& $data['out_pm'] <= 34){
            $data['out_air'] = '优';
        }elseif (35<= $data['out_pm']&& $data['out_pm'] <= 74){
            $data['out_air'] = '良';
        }else{
            $data['out_air'] = '';
        }
        return $data;
    }


}