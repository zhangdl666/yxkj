<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/9/25
 * Time: 14:42
 */

namespace Wx\Model;

use Pc\Model\UserRoleModel;
use Think\Model;

class SalesCountModel extends Model
{
    const TABLE_NAME_MONEY = 'return_money';
    const TABLE_NAME_HOTEL = 'hotel';
    const TABLE_NAME_ROOMS = 'hc_room_equipment';
    const TABLE_NAME_INFOS = 'oper_info';
    const TABLE_NAME_CONTS = 'hotel_contract';
    const TABLE_NAME_TYPES = 'user';

    const STAUS_ONE = 3; // 已完成

    /**
     * 销售额
     * @return mixed
     */
    public static function getSalesVolume()
    {
        $id = session('USERINFO.id');
        $role_id = session('USERINFO.role_id');
        if($role_id == 9){
            $num = M(\Pc\Model\SalesModel::TABLENAME_MONY)
                ->alias('l')
                ->join('left join ' . C('DB_PREFIX') . \Pc\Model\HotelModel::TABLENAME_HOTEL . ' as h on h.id = l.h_id')
                ->join('left join ' . C('DB_PREFIX') . \Pc\Model\HotelGetModel::TABLENAME . ' as s on s.h_id = h.id')
                ->join('left join ' . C('DB_PREFIX') . \Pc\Model\HotelModel::TABLENAME_TYPE . ' as j on j.id = h.ht_id')
                ->join('left join ' . C('DB_PREFIX') . UserRoleModel::TABLENAME_USER . ' as x on x.id = s.sale_id')
                ->join('left join ' . C('DB_PREFIX') . \Pc\Model\SalesModel::TANLENAME_EXT . ' as p on p.u_id = x.id')
                ->field('SUM(l.price) as price,j.name as type_name')
                ->where(array('s.is_default' => 1, 'l.price' => array('neq', 0),'l.status'=>3,'s.sale_id'=>array('neq',0)))
                ->group('type_name')->select();
            return array_sum(i_array_column($num,'price'));
        }else{
            $num = M(\Pc\Model\SalesModel::TABLENAME_MONY)
                ->alias('l')
                ->join('left join ' . C('DB_PREFIX') . \Pc\Model\HotelModel::TABLENAME_HOTEL . ' as h on h.id = l.h_id')
                ->join('left join ' . C('DB_PREFIX') . \Pc\Model\HotelGetModel::TABLENAME . ' as s on s.h_id = h.id')
                ->join('left join ' . C('DB_PREFIX') . \Pc\Model\HotelModel::TABLENAME_TYPE . ' as j on j.id = h.ht_id')
                ->join('left join ' . C('DB_PREFIX') . UserRoleModel::TABLENAME_USER . ' as x on x.id = s.sale_id')
                ->join('left join ' . C('DB_PREFIX') . \Pc\Model\SalesModel::TANLENAME_EXT . ' as p on p.u_id = x.id')
                ->field('SUM(l.price) as price,j.name as type_name')
                ->where(array('s.is_default' => 1, 'l.price' => array('neq', 0),'l.status'=>3,'s.sale_id'=>array('neq',0),'x.parent_id'=>$id))
                ->group('type_name')->select();
            return array_sum(i_array_column($num,'price'));
        }


    }

    /**
     * 酒店数
     * @return mixed
     */
    public static function getHotelNum()
    {
        $id=session('USERINFO.id');
        $role_id = session('USERINFO.role_id');
        if($role_id == 9){
            $res=M('hotel_get')
                ->alias('hg')
                ->join('left join ' . C('DB_PREFIX') . UserRoleModel::TABLENAME_USER . ' as h on h.id = hg.sale_id')
                ->where(array('is_default'=>1))
                ->count();
            return $res;
        }else{
            $res=M('hotel_get')
                ->alias('hg')
                ->join('left join ' . C('DB_PREFIX') . UserRoleModel::TABLENAME_USER . ' as h on h.id = hg.sale_id')
                ->where(array('h.parent_id'=>$id,'is_default'=>1))
                ->count();
            return $res;
        }


    }

    /**
     * 客房数
     * @return mixed
     */
    public static function getHotelRoom()
    {
        $id = session('USERINFO.id');
        $role_id = session('USERINFO.role_id');
        if($role_id == 9){
            $data =  M(\Pc\Model\HotelModel::TABLENAME_ROOMS)
                ->alias('l')
                ->join('left join ' . C('DB_PREFIX') . \Pc\Model\HotelModel::TABLENAME_ROOMSE . ' as s on s.rt_id = l.id')
                ->join('left join ' . C('DB_PREFIX') . \Pc\Model\HotelModel::TABLENAME_HOTEL . ' as j on j.id = s.h_id')
                ->join('left join ' . C('DB_PREFIX') . \Pc\Model\HotelModel::TABLENAME_GET . ' as e on e.h_id = j.id')
                ->join('left join ' . C('DB_PREFIX') . UserRoleModel::TABLENAME_USER . ' as h on h.id = e.sale_id')
                ->join('left join ' . C('DB_PREFIX') . \Pc\Model\SalesModel::TANLENAME_EXT . ' as p on p.u_id = h.id')
                ->field("SUM(s.r_num) as num,FROM_UNIXTIME(j.ctime,'%Y-%m') as months")
                ->where(array('j.is_sign' => 1,'e.is_default'=>1))
                ->group('months')->select();
            return array_sum(i_array_column($data,'num'));

        }else{
            $data =  M(\Pc\Model\HotelModel::TABLENAME_ROOMS)
                ->alias('l')
                ->join('left join ' . C('DB_PREFIX') . \Pc\Model\HotelModel::TABLENAME_ROOMSE . ' as s on s.rt_id = l.id')
                ->join('left join ' . C('DB_PREFIX') . \Pc\Model\HotelModel::TABLENAME_HOTEL . ' as j on j.id = s.h_id')
                ->join('left join ' . C('DB_PREFIX') . \Pc\Model\HotelModel::TABLENAME_GET . ' as e on e.h_id = j.id')
                ->join('left join ' . C('DB_PREFIX') . UserRoleModel::TABLENAME_USER . ' as h on h.id = e.sale_id')
                ->join('left join ' . C('DB_PREFIX') . \Pc\Model\SalesModel::TANLENAME_EXT . ' as p on p.u_id = h.id')
                ->field("SUM(s.r_num) as num,FROM_UNIXTIME(j.ctime,'%Y-%m') as months")
                ->where(array('j.is_sign' => 1,'h.parent_id' => $id,'e.is_default'=>1))
                ->group('months')->select();
            return array_sum(i_array_column($data,'num'));

        }

    }

    /**
     * 产品数
     * @return mixed
     */
    public static function getProductNum()
    {
        $id = session('USERINFO.id');
        $role_id = session('USERINFO.role_id');
        if($role_id == 9){
            $data = M(SalesModel::TABLENAME_CONT)
                ->alias('l')
                ->join('left join ' . C('DB_PREFIX') . SalesModel::TABLENAME_EMENT . ' as h on h.hc_id = l.id')
                ->join('left join ' . C('DB_PREFIX') . SalesModel::TABLENAME_MENT . ' as j on j.id = h.e1_id')
                ->join('left join ' . C('DB_PREFIX') . \Pc\Model\HotelModel::TABLENAME_HOTEL . ' as s on s.id = l.h_id')
                ->join('left join ' . C('DB_PREFIX') . \Pc\Model\HotelModel::TABLENAME_GET . ' as e on e.h_id = s.id')
                ->join('left join ' . C('DB_PREFIX') . UserRoleModel::TABLENAME_USER . ' as z on z.id = e.sale_id')
                ->join('left join ' . C('DB_PREFIX') . \Pc\Model\SalesModel::TANLENAME_EXT . ' as p on p.u_id = z.id')
                ->field("SUM(h.e1_num+h.e2_num) as num,FROM_UNIXTIME(l.ctime,'%Y-%m') as months")
                ->where(array('s.is_sign' => 1,'e.is_default'=>1))
                ->group('months')->select();
            return array_sum(i_array_column($data,'num'));
        }else{
            $data = M(SalesModel::TABLENAME_CONT)
                ->alias('l')
                ->join('left join ' . C('DB_PREFIX') . SalesModel::TABLENAME_EMENT . ' as h on h.hc_id = l.id')
                ->join('left join ' . C('DB_PREFIX') . SalesModel::TABLENAME_MENT . ' as j on j.id = h.e1_id')
                ->join('left join ' . C('DB_PREFIX') . \Pc\Model\HotelModel::TABLENAME_HOTEL . ' as s on s.id = l.h_id')
                ->join('left join ' . C('DB_PREFIX') . \Pc\Model\HotelModel::TABLENAME_GET . ' as e on e.h_id = s.id')
                ->join('left join ' . C('DB_PREFIX') . UserRoleModel::TABLENAME_USER . ' as z on z.id = e.sale_id')
                ->join('left join ' . C('DB_PREFIX') . \Pc\Model\SalesModel::TANLENAME_EXT . ' as p on p.u_id = z.id')
                ->where(array('z.parent_id' => $id,'s.is_sign' => 1,'e.is_default'=>1))
                ->field("SUM(h.e1_num+h.e2_num) as num,FROM_UNIXTIME(l.ctime,'%Y-%m') as months")
                ->group('months')->select();
            return array_sum(i_array_column($data,'num'));
        }

    }


    /**
     * 合同
     * @return mixed
     */
    public static function getContractNum()
    {
        $role_id = session('USERINFO.role_id');
        $id=session('USERINFO.id');
        if($role_id == 9){
            return M(self::TABLE_NAME_CONTS)
                ->alias('hc')
                ->join(' left join yx_hotel_get as hg on hg.h_id = hc.h_id')
                ->join(' left join yx_user as u on u.id = hg.sale_id')
                ->where(array('hg.is_default'=>1))
                ->count();
        }else{
            return M(self::TABLE_NAME_CONTS)
                ->alias('hc')
                ->join(' left join yx_hotel_get as hg on hg.h_id = hc.h_id')
                ->join(' left join yx_user as u on u.id = hg.sale_id')
                ->where(array('hg.is_default'=>1,'u.parent_id'=>$id))
                ->count();
        }

    }

    /**
     * 渠道数
     */
    public static function getTypesNum()
    {
        $id=session('USERINFO.id');
        $role_id = session('USERINFO.role_id');
        if($role_id == 9){
            $qd_num = M(UserRoleModel::TABLENAME_USER)
                ->alias('l')
                ->join('left join ' . C('DB_PREFIX') . \Pc\Model\SalesModel::TANLENAME_EXT . ' as p on p.u_id = l.id')
                ->where(array('p.u_id' => array('neq', 0)))
                ->field('COUNT(p.u_id) as num , (CASE WHEN p.channel_type = 1 THEN "内聘" '
                    . ' WHEN p.channel_type = 2 THEN "个人" ELSE "团队" END) as user_type_name')
                ->group('user_type_name')->select();
            return array_sum(i_array_column($qd_num,'num'));
        }else{
            $qd_num = M(UserRoleModel::TABLENAME_USER)
                ->alias('l')
                ->join('left join ' . C('DB_PREFIX') . \Pc\Model\SalesModel::TANLENAME_EXT . ' as p on p.u_id = l.id')
                ->where(array('p.u_id' => array('neq', 0),'l.parent_id'=>$id))
                ->field('COUNT(p.u_id) as num , (CASE WHEN p.channel_type = 1 THEN "内聘" '
                    . ' WHEN p.channel_type = 2 THEN "个人" ELSE "团队" END) as user_type_name')
                ->group('user_type_name')->select();
            return array_sum(i_array_column($qd_num,'num'));
        }

//        return M('SaleExt')->where(array('u_id'=>array('neq',0)))->count();
    }

}
