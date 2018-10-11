<?php
/**
 * Author: ' Silent
 * Time: 2017/9/8 9:31
 */

namespace Pc\Model;


use Think\Model;

class SalesModel extends Model
{
    // 定义状态枚举
    const STATUS_SALES_ONE = 1;
    const STATUS_SALES_TWO = 2;
    const STATUS_SALES_THREE = 3;
    const STATUS_SALES_FOUR = 4;
    const STATUS_SALES_FIVE = 5;
    const STATUS_SALES_SIX = 6;

    // 定义表名
    const TABLENAME_MENT = 'equipment';   //设备类型
    const TABLENAME_CONT = 'hotel_contract';  //酒店合同
    const TABLENAME_EMENT = 'hc_room_equipment'; //酒店设备数
    const TABLENAME_MONY = 'return_money'; //回款情况表
    const TABLENAME_OPER = 'oper_info';
    const TABLENAME_ORDER = 'oper_order';
    const TANLENAME_EXT = 'sale_ext';
    const TABLENAME_CHL = 'channel_level';
    const TABLENAME_TYPE = 'equipment';

    /**
     * 统计销售额
     */
    public static function countHotelSales($where)
    {
        $id = session('USERINFO.id');
        $role_id = session('USERINFO.role_id');
        if($role_id == 9){
            $data = array();
            // 统计各省数量占比量
            if($where['h.provice']){
                $data['pro_num'] = M(self::TABLENAME_MONY)
                    ->alias('l')
                    ->join('left join ' . C('DB_PREFIX') . HotelModel::TABLENAME_HOTEL . ' as h on h.id = l.h_id')
                    ->join('left join ' . C('DB_PREFIX') . HotelGetModel::TABLENAME . ' as s on s.h_id = h.id')
                    ->join('left join ' . C('DB_PREFIX') . HotelModel::TABLENAME_TYPE . ' as j on j.id = h.ht_id')
                    ->join('left join ' . C('DB_PREFIX') . UserRoleModel::TABLENAME_USER . ' as x on x.id = s.sale_id')
                    ->join('left join ' . C('DB_PREFIX') . self::TANLENAME_EXT . ' as p on p.u_id = x.id')
                    ->field('SUM(l.price) as price,h.city')
                    ->where($where)
                    ->where(array('s.is_default' => 1, 'l.price' => array('neq', 0),'l.status'=>3,'s.sale_id'=>array('neq',0)))
                    ->group('city')->select();
                // 处理所需要的业务数据
                $data['pro_nums'] = array();
                foreach ($data['pro_num'] as $key => $val) {
                    $data['pro_nums'][$key]['value'] = $val['price'];
                    $data['pro_nums'][$key]['name'] = $val['city'];
                }
            }else{

                $data['pro_num'] = M(self::TABLENAME_MONY)
                    ->alias('l')
                    ->join('left join ' . C('DB_PREFIX') . HotelModel::TABLENAME_HOTEL . ' as h on h.id = l.h_id')
                    ->join('left join ' . C('DB_PREFIX') . HotelGetModel::TABLENAME . ' as s on s.h_id = h.id')
                    ->join('left join ' . C('DB_PREFIX') . HotelModel::TABLENAME_TYPE . ' as j on j.id = h.ht_id')
                    ->join('left join ' . C('DB_PREFIX') . UserRoleModel::TABLENAME_USER . ' as x on x.id = s.sale_id')
                    ->join('left join ' . C('DB_PREFIX') . self::TANLENAME_EXT . ' as p on p.u_id = x.id')
                    ->field('SUM(l.price) as price,h.provice')
                    ->where($where)
                    ->where(array('s.is_default' => 1, 'l.price' => array('neq', 0),'l.status'=>3,'s.sale_id'=>array('neq',0)))
                    ->group('provice')->select();
                $data['pro_nums'] = array();
                foreach ($data['pro_num'] as $key => $val) {
                    $data['pro_nums'][$key]['value'] = $val['price'];
                    $data['pro_nums'][$key]['name'] = $val['provice'];
                }
            }


            $data['pro_nums'] = json_encode($data['pro_nums']);
            // 统计酒店类型占比
            $data['type_num'] = M(self::TABLENAME_MONY)
                ->alias('l')
                ->join('left join ' . C('DB_PREFIX') . HotelModel::TABLENAME_HOTEL . ' as h on h.id = l.h_id')
                ->join('left join ' . C('DB_PREFIX') . HotelGetModel::TABLENAME . ' as s on s.h_id = h.id')
                ->join('left join ' . C('DB_PREFIX') . HotelModel::TABLENAME_TYPE . ' as j on j.id = h.ht_id')
                ->join('left join ' . C('DB_PREFIX') . UserRoleModel::TABLENAME_USER . ' as x on x.id = s.sale_id')
                ->join('left join ' . C('DB_PREFIX') . self::TANLENAME_EXT . ' as p on p.u_id = x.id')
                ->field('SUM(l.price) as price,j.name as type_name')
                ->where($where)
                ->where(array('s.is_default' => 1, 'l.price' => array('neq', 0),'l.status'=>3,'s.sale_id'=>array('neq',0)))
                ->group('type_name')->select();
            // 处理所需要的业务数据
            $data['type_nums'] = array();
            foreach ($data['type_num'] as $keys => $vals) {
                $data['type_nums'][$keys]['value'] = $vals['price'];
                $data['type_nums'][$keys]['name'] = $vals['type_name'];
            }
            $data['type_nums'] = json_encode($data['type_nums']);
            // 统计渠道占比
            $data['qd_num'] = M(self::TABLENAME_MONY)
                ->alias('l')
                ->join('left join ' . C('DB_PREFIX') . HotelModel::TABLENAME_HOTEL . ' as h on h.id = l.h_id')
                ->join('left join ' . C('DB_PREFIX') . HotelGetModel::TABLENAME . ' as s on s.h_id = h.id')
                ->join('left join ' . C('DB_PREFIX') . UserRoleModel::TABLENAME_USER . ' as j on j.id = s.sale_id')
                ->join('left join ' . C('DB_PREFIX') . self::TANLENAME_EXT . ' as p on p.u_id = j.id')
                ->field('SUM(l.price) as price,(CASE WHEN p.channel_type = 1 THEN "内聘" '
                    . ' WHEN p.channel_type = 2 THEN "个人" ELSE "团队" END) as user_type_name')
                ->where($where)
                ->where(array('s.is_default' => 1, 'l.price' => array('neq', 0),'l.status'=>3,'s.sale_id'=>array('neq',0)))
                ->group('user_type_name')->select();
            // 处理所需要的业务数据
            $data['qd_nums'] = array();
            foreach ($data['qd_num'] as $keyf => $valf) {
                $data['qd_nums'][$keyf]['value'] = $valf['price'];
                $data['qd_nums'][$keyf]['name'] = $valf['user_type_name'];
            }
            $data['qd_nums'] = json_encode($data['qd_nums']);
            if($where['h.provice']){
                $data['bj'] = M(self::TABLENAME_MONY)
                    ->alias('l')
                    ->join('left join ' . C('DB_PREFIX') . HotelModel::TABLENAME_HOTEL . ' as h on h.id = l.h_id')
                    ->join('left join ' . C('DB_PREFIX') . HotelGetModel::TABLENAME . ' as s on s.h_id = h.id')
                    ->join('left join ' . C('DB_PREFIX') . UserRoleModel::TABLENAME_USER . ' as j on j.id = s.sale_id')
                    ->join('left join ' . C('DB_PREFIX') . self::TANLENAME_EXT . ' as p on p.u_id = j.id')
                    ->where($where)
                    ->where(array('s.is_default' => 1, 'l.price' => array('neq', 0),'l.status'=>3,'s.sale_id'=>array('neq',0)))
                    ->field('SUM(l.price) as price,h.provice as all_price,h.provice')
                    ->group('provice')->select();
                $data['bj_nums'] = array();
                foreach ($data['bj'] as $keyfs => $valfs) {
                    $data['bj_nums'][$keyfs]['value'] = $valfs['price'];
                    $data['bj_nums'][$keyfs]['name'] = $valfs['provice'];
                }
                $data['bj_nums'] = json_encode($data['bj_nums']);
            }else {
                $data['bj'] = M(self::TABLENAME_MONY)
                    ->alias('l')
                    ->join('left join ' . C('DB_PREFIX') . HotelModel::TABLENAME_HOTEL . ' as h on h.id = l.h_id')
                    ->join('left join ' . C('DB_PREFIX') . HotelGetModel::TABLENAME . ' as s on s.h_id = h.id')
                    ->join('left join ' . C('DB_PREFIX') . UserRoleModel::TABLENAME_USER . ' as j on j.id = s.sale_id')
                    ->join('left join ' . C('DB_PREFIX') . self::TANLENAME_EXT . ' as p on p.u_id = j.id')
                    ->where(array('s.is_default' => 1, 'l.price' => array('neq', 0), 'l.status' => 3, 's.sale_id' => array('neq', 0)))
                    ->where($where)
                    ->field('SUM(l.price) as price,h.provice')
                    ->group('provice')->select();
                $data['bj_nums'] = array();
                foreach ($data['bj'] as $keyfs => $valfs) {
                    $data['bj_nums'][$keyfs]['value'] = $valfs['price'];
                    $data['bj_nums'][$keyfs]['name'] = $valfs["provice"];
                }
                $data['bj_nums'] = json_encode($data['bj_nums']);
            }

            // 统计前十的数据表格
            $rank = M(self::TABLENAME_MONY)
                ->alias('l')
                ->join('left join ' . C('DB_PREFIX') . HotelModel::TABLENAME_HOTEL . ' as h on h.id = l.h_id')
                ->join('left join ' . C('DB_PREFIX') . HotelGetModel::TABLENAME . ' as s on s.h_id = h.id')
                ->join('left join ' . C('DB_PREFIX') . UserRoleModel::TABLENAME_USER . ' as j on j.id = s.sale_id')
                ->join('left join ' . C('DB_PREFIX') . self::TANLENAME_EXT . ' as p on p.u_id = j.id')
                ->join('left join ' . C('DB_PREFIX') . self::TABLENAME_CHL . ' as e on e.id = p.cl_id')
                ->where(array('s.is_default' => 1, 'l.price' => array('neq', 0),'l.status'=>3,'s.sale_id'=>array('neq',0)))
                ->where($where)
                ->field('SUM(l.price) as price,(CASE WHEN p.channel_type = 1 THEN "内聘" '
                    . ' WHEN p.channel_type = 2 THEN "个人" ELSE "团队" END) as user_type_name,j.real_name as real_name,j.img,e.name as user_name')
                ->group('real_name')->select();
            // 全年趋势图(从本月往前推算12个月)
            $nowtime = strtotime(date("Y-m", strtotime("+1 months", strtotime(date("Y-m", time())))));
            if($where['l.ctime']){
                $data['all_num'] = M(self::TABLENAME_MONY)
                    ->alias('l')
                    ->join('left join ' . C('DB_PREFIX') . HotelModel::TABLENAME_HOTEL . ' as h on h.id = l.h_id')
                    ->join('left join ' . C('DB_PREFIX') . HotelGetModel::TABLENAME . ' as s on s.h_id = h.id')
                    ->join('left join ' . C('DB_PREFIX') . UserRoleModel::TABLENAME_USER . ' as j on j.id = s.sale_id')
                    ->join('left join ' . C('DB_PREFIX') . self::TANLENAME_EXT . ' as p on p.u_id = j.id')
                    ->join('left join ' . C('DB_PREFIX') . self::TABLENAME_CHL . ' as e on e.id = p.cl_id')
                    ->where(array('s.is_default' => 1, 'l.price' => array('neq', 0),'l.status'=>3,'s.sale_id'=>array('neq',0)))
                    ->where($where)
                    ->field("SUM(l.price) as price,FROM_UNIXTIME(l.ctime,'%Y-%m') as months")
                    ->group('months')->select();
            }else{
                $maps['l.ctime'] = array('lt', $nowtime);
                $data['all_num'] = M(self::TABLENAME_MONY)
                    ->alias('l')
                    ->join('left join ' . C('DB_PREFIX') . HotelModel::TABLENAME_HOTEL . ' as h on h.id = l.h_id')
                    ->join('left join ' . C('DB_PREFIX') . HotelGetModel::TABLENAME . ' as s on s.h_id = h.id')
                    ->join('left join ' . C('DB_PREFIX') . UserRoleModel::TABLENAME_USER . ' as j on j.id = s.sale_id')
                    ->join('left join ' . C('DB_PREFIX') . self::TANLENAME_EXT . ' as p on p.u_id = j.id')
                    ->join('left join ' . C('DB_PREFIX') . self::TABLENAME_CHL . ' as e on e.id = p.cl_id')
                    ->where(array('s.is_default' => 1, 'l.price' => array('neq', 0),'l.status'=>3,'s.sale_id'=>array('neq',0)))
                    ->where($maps)
                    ->where($where)
                    ->field("SUM(l.price) as price,FROM_UNIXTIME(l.ctime,'%Y-%m') as months")
                    ->group('months')->select();
            }
            $data['all_num'] = self::toArrayList($data['all_num'], self::getMonth(), 'price');
            $data['all_num'] = arraySequence($data['all_num'], 'months', 'SORT_ASC');
            // 二维数组排序,生成前十统计列表
            $data['rank'] = arraySequence($rank, 'price');
            $ranks = array_sum(i_array_column($data['rank'], 'price'));
            foreach ($data['rank'] as $keyk => &$valk) {
                $valk['bai'] = ($valk['price'] / $ranks) * 100;
            }
            return $data;
        }else{
            $data = array();
            // 统计各省数量占比量
            if($where['h.provice']){
                $data['pro_num'] = M(self::TABLENAME_MONY)
                    ->alias('l')
                    ->join('left join ' . C('DB_PREFIX') . HotelModel::TABLENAME_HOTEL . ' as h on h.id = l.h_id')
                    ->join('left join ' . C('DB_PREFIX') . HotelGetModel::TABLENAME . ' as s on s.h_id = h.id')
                    ->join('left join ' . C('DB_PREFIX') . HotelModel::TABLENAME_TYPE . ' as j on j.id = h.ht_id')
                    ->join('left join ' . C('DB_PREFIX') . UserRoleModel::TABLENAME_USER . ' as x on x.id = s.sale_id')
                    ->join('left join ' . C('DB_PREFIX') . self::TANLENAME_EXT . ' as p on p.u_id = x.id')
                    ->field('SUM(l.price) as price,h.city')
                    ->where($where)
                    ->where(array('s.is_default' => 1, 'l.price' => array('neq', 0),'l.status'=>3,'s.sale_id'=>array('neq',0),'x.parent_id'=>$id))
                    ->group('city')->select();
                // 处理所需要的业务数据
                $data['pro_nums'] = array();
                foreach ($data['pro_num'] as $key => $val) {
                    $data['pro_nums'][$key]['value'] = $val['price'];
                    $data['pro_nums'][$key]['name'] = $val['city'];
                }
            }else{

                $data['pro_num'] = M(self::TABLENAME_MONY)
                    ->alias('l')
                    ->join('left join ' . C('DB_PREFIX') . HotelModel::TABLENAME_HOTEL . ' as h on h.id = l.h_id')
                    ->join('left join ' . C('DB_PREFIX') . HotelGetModel::TABLENAME . ' as s on s.h_id = h.id')
                    ->join('left join ' . C('DB_PREFIX') . HotelModel::TABLENAME_TYPE . ' as j on j.id = h.ht_id')
                    ->join('left join ' . C('DB_PREFIX') . UserRoleModel::TABLENAME_USER . ' as x on x.id = s.sale_id')
                    ->join('left join ' . C('DB_PREFIX') . self::TANLENAME_EXT . ' as p on p.u_id = x.id')
                    ->field('SUM(l.price) as price,h.provice')
                    ->where($where)
                    ->where(array('s.is_default' => 1, 'l.price' => array('neq', 0),'l.status'=>3,'s.sale_id'=>array('neq',0),'x.parent_id'=>$id))
                    ->group('provice')->select();
                $data['pro_nums'] = array();
                foreach ($data['pro_num'] as $key => $val) {
                    $data['pro_nums'][$key]['value'] = $val['price'];
                    $data['pro_nums'][$key]['name'] = $val['provice'];
                }
            }


            $data['pro_nums'] = json_encode($data['pro_nums']);
            // 统计酒店类型占比
            $data['type_num'] = M(self::TABLENAME_MONY)
                ->alias('l')
                ->join('left join ' . C('DB_PREFIX') . HotelModel::TABLENAME_HOTEL . ' as h on h.id = l.h_id')
                ->join('left join ' . C('DB_PREFIX') . HotelGetModel::TABLENAME . ' as s on s.h_id = h.id')
                ->join('left join ' . C('DB_PREFIX') . HotelModel::TABLENAME_TYPE . ' as j on j.id = h.ht_id')
                ->join('left join ' . C('DB_PREFIX') . UserRoleModel::TABLENAME_USER . ' as x on x.id = s.sale_id')
                ->join('left join ' . C('DB_PREFIX') . self::TANLENAME_EXT . ' as p on p.u_id = x.id')
                ->field('SUM(l.price) as price,j.name as type_name')
                ->where($where)
                ->where(array('s.is_default' => 1, 'l.price' => array('neq', 0),'l.status'=>3,'s.sale_id'=>array('neq',0),'x.parent_id'=>$id))
                ->group('type_name')->select();
            // 处理所需要的业务数据
            $data['type_nums'] = array();
            foreach ($data['type_num'] as $keys => $vals) {
                $data['type_nums'][$keys]['value'] = $vals['price'];
                $data['type_nums'][$keys]['name'] = $vals['type_name'];
            }
            $data['type_nums'] = json_encode($data['type_nums']);
            // 统计渠道占比
            $data['qd_num'] = M(self::TABLENAME_MONY)
                ->alias('l')
                ->join('left join ' . C('DB_PREFIX') . HotelModel::TABLENAME_HOTEL . ' as h on h.id = l.h_id')
                ->join('left join ' . C('DB_PREFIX') . HotelGetModel::TABLENAME . ' as s on s.h_id = h.id')
                ->join('left join ' . C('DB_PREFIX') . UserRoleModel::TABLENAME_USER . ' as j on j.id = s.sale_id')
                ->join('left join ' . C('DB_PREFIX') . self::TANLENAME_EXT . ' as p on p.u_id = j.id')
                ->field('SUM(l.price) as price,(CASE WHEN p.channel_type = 1 THEN "内聘" '
                    . ' WHEN p.channel_type = 2 THEN "个人" ELSE "团队" END) as user_type_name')
                ->where($where)
                ->where(array('s.is_default' => 1, 'l.price' => array('neq', 0),'l.status'=>3,'s.sale_id'=>array('neq',0),'j.parent_id'=>$id))
                ->group('user_type_name')->select();
            // 处理所需要的业务数据
            $data['qd_nums'] = array();
            foreach ($data['qd_num'] as $keyf => $valf) {
                $data['qd_nums'][$keyf]['value'] = $valf['price'];
                $data['qd_nums'][$keyf]['name'] = $valf['user_type_name'];
            }
            $data['qd_nums'] = json_encode($data['qd_nums']);
            if($where['h.provice']){
                $data['bj'] = M(self::TABLENAME_MONY)
                    ->alias('l')
                    ->join('left join ' . C('DB_PREFIX') . HotelModel::TABLENAME_HOTEL . ' as h on h.id = l.h_id')
                    ->join('left join ' . C('DB_PREFIX') . HotelGetModel::TABLENAME . ' as s on s.h_id = h.id')
                    ->join('left join ' . C('DB_PREFIX') . UserRoleModel::TABLENAME_USER . ' as j on j.id = s.sale_id')
                    ->join('left join ' . C('DB_PREFIX') . self::TANLENAME_EXT . ' as p on p.u_id = j.id')
                    ->where($where)
                    ->where(array('s.is_default' => 1, 'l.price' => array('neq', 0),'l.status'=>3,'s.sale_id'=>array('neq',0),'j.parent_id'=>$id))
                    ->field('SUM(l.price) as price,h.provice as all_price,h.provice')
                    ->group('provice')->select();
                $data['bj_nums'] = array();
                foreach ($data['bj'] as $keyfs => $valfs) {
                    $data['bj_nums'][$keyfs]['value'] = $valfs['price'];
                    $data['bj_nums'][$keyfs]['name'] = $valfs['provice'];
                }
                $data['bj_nums'] = json_encode($data['bj_nums']);
            }else {
                $data['bj'] = M(self::TABLENAME_MONY)
                    ->alias('l')
                    ->join('left join ' . C('DB_PREFIX') . HotelModel::TABLENAME_HOTEL . ' as h on h.id = l.h_id')
                    ->join('left join ' . C('DB_PREFIX') . HotelGetModel::TABLENAME . ' as s on s.h_id = h.id')
                    ->join('left join ' . C('DB_PREFIX') . UserRoleModel::TABLENAME_USER . ' as j on j.id = s.sale_id')
                    ->join('left join ' . C('DB_PREFIX') . self::TANLENAME_EXT . ' as p on p.u_id = j.id')
                    ->where(array('s.is_default' => 1, 'l.price' => array('neq', 0), 'l.status' => 3, 's.sale_id' => array('neq', 0),'j.parent_id'=>$id))
                    ->where($where)
                    ->field('SUM(l.price) as price,h.provice')
                    ->group('provice')->select();
                $data['bj_nums'] = array();
                foreach ($data['bj'] as $keyfs => $valfs) {
                    $data['bj_nums'][$keyfs]['value'] = $valfs['price'];
                    $data['bj_nums'][$keyfs]['name'] = $valfs["provice"];
                }
                $data['bj_nums'] = json_encode($data['bj_nums']);
            }

            // 统计前十的数据表格
            $rank = M(self::TABLENAME_MONY)
                ->alias('l')
                ->join('left join ' . C('DB_PREFIX') . HotelModel::TABLENAME_HOTEL . ' as h on h.id = l.h_id')
                ->join('left join ' . C('DB_PREFIX') . HotelGetModel::TABLENAME . ' as s on s.h_id = h.id')
                ->join('left join ' . C('DB_PREFIX') . UserRoleModel::TABLENAME_USER . ' as j on j.id = s.sale_id')
                ->join('left join ' . C('DB_PREFIX') . self::TANLENAME_EXT . ' as p on p.u_id = j.id')
                ->join('left join ' . C('DB_PREFIX') . self::TABLENAME_CHL . ' as e on e.id = p.cl_id')
                ->where(array('s.is_default' => 1, 'l.price' => array('neq', 0),'l.status'=>3,'s.sale_id'=>array('neq',0),'j.parent_id'=>$id))
                ->where($where)
                ->field('SUM(l.price) as price,(CASE WHEN p.channel_type = 1 THEN "内聘" '
                    . ' WHEN p.channel_type = 2 THEN "个人" ELSE "团队" END) as user_type_name,j.real_name as real_name,j.img,e.name as user_name')
                ->group('real_name')->select();
            // 全年趋势图(从本月往前推算12个月)
            $nowtime = strtotime(date("Y-m", strtotime("+1 months", strtotime(date("Y-m", time())))));
            if($where['l.ctime']){
                $data['all_num'] = M(self::TABLENAME_MONY)
                    ->alias('l')
                    ->join('left join ' . C('DB_PREFIX') . HotelModel::TABLENAME_HOTEL . ' as h on h.id = l.h_id')
                    ->join('left join ' . C('DB_PREFIX') . HotelGetModel::TABLENAME . ' as s on s.h_id = h.id')
                    ->join('left join ' . C('DB_PREFIX') . UserRoleModel::TABLENAME_USER . ' as j on j.id = s.sale_id')
                    ->join('left join ' . C('DB_PREFIX') . self::TANLENAME_EXT . ' as p on p.u_id = j.id')
                    ->join('left join ' . C('DB_PREFIX') . self::TABLENAME_CHL . ' as e on e.id = p.cl_id')
                    ->where(array('s.is_default' => 1, 'l.price' => array('neq', 0),'l.status'=>3,'s.sale_id'=>array('neq',0),'j.parent_id'=>$id))
                    ->where($where)
                    ->field("SUM(l.price) as price,FROM_UNIXTIME(l.ctime,'%Y-%m') as months")
                    ->group('months')->select();
            }else{
                $maps['l.ctime'] = array('lt', $nowtime);
                $data['all_num'] = M(self::TABLENAME_MONY)
                    ->alias('l')
                    ->join('left join ' . C('DB_PREFIX') . HotelModel::TABLENAME_HOTEL . ' as h on h.id = l.h_id')
                    ->join('left join ' . C('DB_PREFIX') . HotelGetModel::TABLENAME . ' as s on s.h_id = h.id')
                    ->join('left join ' . C('DB_PREFIX') . UserRoleModel::TABLENAME_USER . ' as j on j.id = s.sale_id')
                    ->join('left join ' . C('DB_PREFIX') . self::TANLENAME_EXT . ' as p on p.u_id = j.id')
                    ->join('left join ' . C('DB_PREFIX') . self::TABLENAME_CHL . ' as e on e.id = p.cl_id')
                    ->where(array('s.is_default' => 1, 'l.price' => array('neq', 0),'l.status'=>3,'s.sale_id'=>array('neq',0),'j.parent_id'=>$id))
                    ->where($maps)
                    ->where($where)
                    ->field("SUM(l.price) as price,FROM_UNIXTIME(l.ctime,'%Y-%m') as months")
                    ->group('months')->select();
            }
            $data['all_num'] = self::toArrayList($data['all_num'], self::getMonth(), 'price');
            $data['all_num'] = arraySequence($data['all_num'], 'months', 'SORT_ASC');
            // 二维数组排序,生成前十统计列表
            $data['rank'] = arraySequence($rank, 'price');
            $ranks = array_sum(i_array_column($data['rank'], 'price'));
            foreach ($data['rank'] as $keyk => &$valk) {
                $valk['bai'] = ($valk['price'] / $ranks) * 100;
            }
            return $data;
        }

    }

    /**
     * 统计酒店
     */
    public static function countHotelNum($where, $maps, $docker)
    {
        $id=session('USERINFO.id');
        $role_id = session('USERINFO.role_id');
        if($role_id == 9){
            $docker['l.is_default'] = 1;
            $docker['l.sale_id'] = array('neq',0);
            $data = array();
            // 统计各省酒店占比
            $data['pro_num'] = M(HotelGetModel::TABLENAME)
                ->alias('l')
                ->join('left join ' . C('DB_PREFIX') . HotelModel::TABLENAME_HOTEL . ' as s on s.id = l.h_id')
                ->join('left join ' . C('DB_PREFIX') . UserRoleModel::TABLENAME_USER . ' as h on h.id = l.sale_id')
                 ->join('left join ' . C('DB_PREFIX') . self::TANLENAME_EXT . ' as p on p.u_id = h.id')
                ->where($docker)
//            ->where(array('l.is_default' => 1, 's.is_sign' => 1,'l.sale_id'=>array('neq',0)))
                ->field("COUNT(l.id) as num,provice")
                ->group('provice')->select();
            // 处理所需要的业务数据
            $data['pro_nums'] = array();
            foreach ($data['pro_num'] as $keys => $vals) {
                $data['pro_nums'][$keys]['value'] = $vals['num'];
                $data['pro_nums'][$keys]['name'] = $vals['provice'];
            }
            $data['pro_nums'] = json_encode($data['pro_nums']);
            // 统计酒店类型占比
            $data['type_num'] = M(HotelGetModel::TABLENAME)
                ->alias('l')
                ->join('left join ' . C('DB_PREFIX') . HotelModel::TABLENAME_HOTEL . ' as s on s.id = l.h_id')
                ->join('left join ' . C('DB_PREFIX') . HotelModel::TABLENAME_TYPE . ' as z on z.id = s.ht_id')
                ->join('left join ' . C('DB_PREFIX') . UserRoleModel::TABLENAME_USER . ' as h on h.id = l.sale_id')
                ->join('left join ' . C('DB_PREFIX') . self::TANLENAME_EXT . ' as p on p.u_id = h.id')
                ->where($docker)
//            ->where(array('l.is_default' => 1, 's.is_sign' => 1,'l.sale_id'=>array('neq',0)))
                ->field('COUNT(l.id) as num,z.name as type_name')
                ->group('type_name')->select();
            // 处理所需要的业务数据
            $data['type_nums'] = array();
            foreach ($data['type_num'] as $keys => $vals) {
                $data['type_nums'][$keys]['value'] = $vals['num'];
                $data['type_nums'][$keys]['name'] = $vals['type_name'];
            }
            $data['type_nums'] = json_encode($data['type_nums']);
            // 各渠道占比
            $data['qd_num'] = M(HotelGetModel::TABLENAME)
                ->alias('l')
                ->join('left join ' . C('DB_PREFIX') . HotelModel::TABLENAME_HOTEL . ' as s on s.id = l.h_id')
                ->join('left join ' . C('DB_PREFIX') . UserRoleModel::TABLENAME_USER . ' as h on h.id = l.sale_id')
                ->join('left join ' . C('DB_PREFIX') . self::TANLENAME_EXT . ' as p on p.u_id = h.id')
                ->where($docker)
//            ->where(array('l.is_default' => 1, 's.is_sign' => 1,'l.sale_id'=>array('neq',0)))
                ->field('COUNT(l.id) as count,(CASE WHEN p.channel_type = 1 THEN "内聘" '
                    . ' WHEN p.channel_type = 2 THEN "个人" ELSE "团队" END) as user_type_name')
                ->group('user_type_name')->select();
            // 处理所需要的业务数据
            $data['qd_nums'] = array();
            foreach ($data['qd_num'] as $keyf => $valf) {
                $data['qd_nums'][$keyf]['value'] = $valf['count'];
                $data['qd_nums'][$keyf]['name'] = $valf['user_type_name'];
            }
            $data['qd_nums'] = json_encode($data['qd_nums']);
            // 统计全年趋势图
            $nowtime = strtotime(date("Y-m", strtotime("+1 months", strtotime(date("Y-m", time())))));
            $map['ctime'] = array('lt', $nowtime);
            $data['all_num'] = M(HotelGetModel::TABLENAME)
                ->alias('l')
                ->join('left join ' . C('DB_PREFIX') . HotelModel::TABLENAME_HOTEL . ' as s on s.id = l.h_id')
                ->join('left join ' . C('DB_PREFIX') . UserRoleModel::TABLENAME_USER . ' as h on h.id = l.sale_id')
                ->join('left join ' . C('DB_PREFIX') . self::TANLENAME_EXT . ' as p on p.u_id = h.id')
                ->where($docker)
//            ->where(array('l.is_default' => 1, 's.is_sign' => 1,'l.sale_id'=>array('neq',0)))
                ->field("COUNT(s.id) as num,FROM_UNIXTIME(s.ctime,'%Y-%m') as months")
                ->group('months')->select();
            $data['all_num'] = self::toArrayList($data['all_num'], self::getMonth(), 'num');
            $data['all_num'] = arraySequence($data['all_num'], 'months', 'SORT_ASC');
            // 统计优秀销售员排行榜
            $rank = M(HotelGetModel::TABLENAME)
                ->alias('l')
                ->join('left join ' . C('DB_PREFIX') . HotelModel::TABLENAME_HOTEL . ' as s on s.id = l.h_id')
                ->join('left join ' . C('DB_PREFIX') . UserRoleModel::TABLENAME_USER . ' as h on h.id = l.sale_id')
                ->join('left join ' . C('DB_PREFIX') . self::TANLENAME_EXT . ' as p on p.u_id = h.id')
                ->where($docker)
//            ->where(array('l.is_default' => 1, 's.is_sign' => 1,'l.sale_id'=>array('neq',0)))
                ->field('COUNT(l.id) as num,(CASE WHEN p.channel_type = 1 THEN "内聘" '
                    . ' WHEN p.channel_type = 2 THEN "个人" ELSE "团队" END) as user_type_name,h.real_name as real_names')
                ->group('real_names')->select();
            // 二维数组排序,生成前十统计列表
            $data['rank'] = arraySequence($rank, 'num');
            return $data;
        }else{
            $docker['h.parent_id'] = $id;
            $docker['l.is_default'] = 1;
            $docker['l.sale_id'] = array('neq',0);
            $data = array();
            // 统计各省酒店占比
            $data['pro_num'] = M(HotelGetModel::TABLENAME)
                ->alias('l')
                ->join('left join ' . C('DB_PREFIX') . HotelModel::TABLENAME_HOTEL . ' as s on s.id = l.h_id')
                ->join('left join ' . C('DB_PREFIX') . UserRoleModel::TABLENAME_USER . ' as h on h.id = l.sale_id')
            ->join('left join ' . C('DB_PREFIX') . self::TANLENAME_EXT . ' as p on p.u_id = h.id')
                ->where($docker)
            ->where(array('l.is_default' => 1, 's.is_sign' => 1,'l.sale_id'=>array('neq',0)))
                ->field("COUNT(l.id) as num,provice")
                ->group('provice')->select();
            // 处理所需要的业务数据
            $data['pro_nums'] = array();
            foreach ($data['pro_num'] as $keys => $vals) {
                $data['pro_nums'][$keys]['value'] = $vals['num'];
                $data['pro_nums'][$keys]['name'] = $vals['provice'];
            }
            $data['pro_nums'] = json_encode($data['pro_nums']);
            // 统计酒店类型占比
            $data['type_num'] = M(HotelGetModel::TABLENAME)
                ->alias('l')
                ->join('left join ' . C('DB_PREFIX') . HotelModel::TABLENAME_HOTEL . ' as s on s.id = l.h_id')
                ->join('left join ' . C('DB_PREFIX') . HotelModel::TABLENAME_TYPE . ' as z on z.id = s.ht_id')
                ->join('left join ' . C('DB_PREFIX') . UserRoleModel::TABLENAME_USER . ' as h on h.id = l.sale_id')
                ->join('left join ' . C('DB_PREFIX') . self::TANLENAME_EXT . ' as p on p.u_id = h.id')
                ->where($docker)
            ->where(array('l.is_default' => 1, 's.is_sign' => 1,'l.sale_id'=>array('neq',0)))
                ->field('COUNT(l.id) as num,z.name as type_name')
                ->group('type_name')->select();
            // 处理所需要的业务数据
            $data['type_nums'] = array();
            foreach ($data['type_num'] as $keys => $vals) {
                $data['type_nums'][$keys]['value'] = $vals['num'];
                $data['type_nums'][$keys]['name'] = $vals['type_name'];
            }
            $data['type_nums'] = json_encode($data['type_nums']);
            // 各渠道占比
            $data['qd_num'] = M(HotelGetModel::TABLENAME)
                ->alias('l')
                ->join('left join ' . C('DB_PREFIX') . HotelModel::TABLENAME_HOTEL . ' as s on s.id = l.h_id')
                ->join('left join ' . C('DB_PREFIX') . UserRoleModel::TABLENAME_USER . ' as h on h.id = l.sale_id')
                ->join('left join ' . C('DB_PREFIX') . self::TANLENAME_EXT . ' as p on p.u_id = h.id')
                ->where($docker)
            ->where(array('l.is_default' => 1, 's.is_sign' => 1,'l.sale_id'=>array('neq',0)))
                ->field('COUNT(l.id) as count,(CASE WHEN p.channel_type = 1 THEN "内聘" '
                    . ' WHEN p.channel_type = 2 THEN "个人" ELSE "团队" END) as user_type_name')
                ->group('user_type_name')->select();
            // 处理所需要的业务数据
            $data['qd_nums'] = array();
            foreach ($data['qd_num'] as $keyf => $valf) {
                $data['qd_nums'][$keyf]['value'] = $valf['count'];
                $data['qd_nums'][$keyf]['name'] = $valf['user_type_name'];
            }
            $data['qd_nums'] = json_encode($data['qd_nums']);
            // 统计全年趋势图
            $nowtime = strtotime(date("Y-m", strtotime("+1 months", strtotime(date("Y-m", time())))));
            $map['ctime'] = array('lt', $nowtime);
            $data['all_num'] = M(HotelGetModel::TABLENAME)
                ->alias('l')
                ->join('left join ' . C('DB_PREFIX') . HotelModel::TABLENAME_HOTEL . ' as s on s.id = l.h_id')
                ->join('left join ' . C('DB_PREFIX') . UserRoleModel::TABLENAME_USER . ' as h on h.id = l.sale_id')
                ->join('left join ' . C('DB_PREFIX') . self::TANLENAME_EXT . ' as p on p.u_id = h.id')
                ->where($docker)
            ->where(array('l.is_default' => 1, 's.is_sign' => 1,'l.sale_id'=>array('neq',0)))
                ->field("COUNT(s.id) as num,FROM_UNIXTIME(s.ctime,'%Y-%m') as months")
                ->group('months')->select();
            $data['all_num'] = self::toArrayList($data['all_num'], self::getMonth(), 'num');
            $data['all_num'] = arraySequence($data['all_num'], 'months', 'SORT_ASC');
            // 统计优秀销售员排行榜
            $rank = M(HotelGetModel::TABLENAME)
                ->alias('l')
                ->join('left join ' . C('DB_PREFIX') . HotelModel::TABLENAME_HOTEL . ' as s on s.id = l.h_id')
                ->join('left join ' . C('DB_PREFIX') . UserRoleModel::TABLENAME_USER . ' as h on h.id = l.sale_id')
                ->join('left join ' . C('DB_PREFIX') . self::TANLENAME_EXT . ' as p on p.u_id = h.id')
                ->where($docker)
            ->where(array('l.is_default' => 1, 's.is_sign' => 1,'l.sale_id'=>array('neq',0)))
                ->field('COUNT(l.id) as num,(CASE WHEN p.channel_type = 1 THEN "内聘" '
                    . ' WHEN p.channel_type = 2 THEN "个人" ELSE "团队" END) as user_type_name,h.real_name as real_names')
                ->group('real_names')->select();
            // 二维数组排序,生成前十统计列表
            $data['rank'] = arraySequence($rank, 'num');
            return $data;
        }


    }

    /**
     * 统计酒店客房数
     */
    public static function countHotelRoom($where)
    {
        $id = session('USERINFO.id');
        $role_id = session('USERINFO.role_id');
        if($role_id == 9){

        }else{
            $where['h.parent_id'] = $id;
        }
        $data = array();
        // 统计房间类型占比量
        if ($where) {
            $data['type_room_num'] = M(HotelModel::TABLENAME_ROOMS)
                ->alias('l')
                ->join('left join ' . C('DB_PREFIX') . \Pc\Model\HotelModel::TABLENAME_ROOMSE . ' as s on s.rt_id = l.id')
                ->join('left join ' . C('DB_PREFIX') . HotelModel::TABLENAME_HOTEL . ' as j on j.id = s.h_id')
                ->join('left join ' . C('DB_PREFIX') . HotelModel::TABLENAME_GET . ' as e on e.h_id = j.id')
                ->join('left join ' . C('DB_PREFIX') . UserRoleModel::TABLENAME_USER . ' as h on h.id = e.sale_id')
                ->join('left join ' . C('DB_PREFIX') . self::TANLENAME_EXT . ' as p on p.u_id = h.id')
                ->field('SUM(s.r_num) as num,l.name as type_name')
                ->where($where)
                ->where(array('j.is_sign' => 1,'e.is_default'=>1))
                ->where($where)
                ->group('type_name')->select();
        } else {
            $data['type_room_num'] = M(HotelModel::TABLENAME_ROOMS)
                ->alias('l')
                ->join('left join ' . C('DB_PREFIX') . \Pc\Model\HotelModel::TABLENAME_ROOMSE . ' as s on s.rt_id = l.id')
                ->join('left join ' . C('DB_PREFIX') . HotelModel::TABLENAME_HOTEL . ' as j on j.id = s.h_id')
                ->join('left join ' . C('DB_PREFIX') . HotelModel::TABLENAME_GET . ' as e on e.h_id = j.id')
                ->join('left join ' . C('DB_PREFIX') . UserRoleModel::TABLENAME_USER . ' as h on h.id = e.sale_id')
                ->join('left join ' . C('DB_PREFIX') . self::TANLENAME_EXT . ' as p on p.u_id = h.id')
                ->field('SUM(s.r_num) as num,l.name as type_name')
                ->where(array('j.is_sign' => 1,'e.is_default'=>1))
                ->group('type_name')->select();
        }
        // 统计全年趋势图
        $nowtime = strtotime(date("Y-m", strtotime("+1 months", strtotime(date("Y-m", time())))));
        $map['j.ctime'] = array('lt', $nowtime);
        $data['hotel_room'] = M(HotelModel::TABLENAME_ROOMS)
            ->alias('l')
            ->join('left join ' . C('DB_PREFIX') . \Pc\Model\HotelModel::TABLENAME_ROOMSE . ' as s on s.rt_id = l.id')
            ->join('left join ' . C('DB_PREFIX') . HotelModel::TABLENAME_HOTEL . ' as j on j.id = s.h_id')
            ->join('left join ' . C('DB_PREFIX') . HotelModel::TABLENAME_GET . ' as e on e.h_id = j.id')
            ->join('left join ' . C('DB_PREFIX') . UserRoleModel::TABLENAME_USER . ' as h on h.id = e.sale_id')
            ->join('left join ' . C('DB_PREFIX') . self::TANLENAME_EXT . ' as p on p.u_id = h.id')
            ->field("SUM(s.r_num) as num,FROM_UNIXTIME(j.ctime,'%Y-%m') as months")
            ->where($where)
            ->where(array('j.is_sign' => 1,'e.is_default'=>1))
            ->group('months')->select();
        $data['hotel_room'] = self::toArrayList($data['hotel_room'], self::getMonth(), 'num');
        $data['hotel_room'] = arraySequence($data['hotel_room'], 'months', 'SORT_ASC');
        // 处理所需要的业务数据
        $data['room_nums'] = array();
        foreach ($data['type_room_num'] as $keys => $vals) {
            $data['room_nums'][$keys]['value'] = $vals['num'];
            $data['room_nums'][$keys]['name'] = $vals['type_name'];
        }
        $data['room_nums'] = json_encode($data['room_nums']);
        return $data;
    }

    /**
     * 统计产品数
     */
    public static function countGoodsNum($where)
    {
        $id = session('USERINFO.id');
        $role_id = session('USERINFO.role_id');
        if($role_id == 9){

        }else{
            $where['z.parent_id'] = $id;
        }

        // 已安装的酒店
        $data = array();
        // 净化器统计
        $e1_num = M(self::TABLENAME_CONT)
            ->alias('l')
            ->join('left join ' . C('DB_PREFIX') . self::TABLENAME_EMENT . ' as h on h.hc_id = l.id')
            ->join('left join ' . C('DB_PREFIX') . self::TABLENAME_MENT . ' as j on j.id = h.e1_id')
            ->join('left join ' . C('DB_PREFIX') . HotelModel::TABLENAME_HOTEL . ' as s on s.id = l.h_id')
            ->join('left join ' . C('DB_PREFIX') . HotelModel::TABLENAME_GET . ' as e on e.h_id = s.id')
            ->join('left join ' . C('DB_PREFIX') . UserRoleModel::TABLENAME_USER . ' as z on z.id = e.sale_id')
            ->join('left join ' . C('DB_PREFIX') . self::TANLENAME_EXT . ' as p on p.u_id = z.id')
            ->field('SUM(h.e1_num) as num,j.name as name')
            ->where($where)
            ->where(array('s.is_sign' => 1,'e.is_default'=>1))
            ->group('name')->select();
        foreach ($e1_num as $key => &$val) {
            $val['name'] = '净化器--' . $val['name'];
        }
        // 监控器统计
        $e2_num = M(self::TABLENAME_CONT)
            ->alias('l')
            ->join('left join ' . C('DB_PREFIX') . self::TABLENAME_EMENT . ' as h on h.hc_id = l.id')
            ->join('left join ' . C('DB_PREFIX') . self::TABLENAME_MENT . ' as j on j.id = h.e2_id')
            ->join('left join ' . C('DB_PREFIX') . HotelModel::TABLENAME_HOTEL . ' as s on s.id = l.h_id')
            ->join('left join ' . C('DB_PREFIX') . HotelModel::TABLENAME_GET . ' as e on e.h_id = s.id')
            ->join('left join ' . C('DB_PREFIX') . UserRoleModel::TABLENAME_USER . ' as z on z.id = e.sale_id')
            ->join('left join ' . C('DB_PREFIX') . self::TANLENAME_EXT . ' as p on p.u_id = z.id')
            ->field('SUM(h.e2_num) as num,j.name as name')
            ->where($where)
            ->where(array('s.is_sign' => 1,'e.is_default'=>1))
            ->group('name')->select();
        foreach ($e2_num as $k => &$v) {
            $v['name'] = '监控器--' . $v['name'];
        }
        $data['goods_num'] = array_merge_recursive((array)$e1_num, (array)$e2_num);
        // 统计全年趋势图
        $e3_num = M(self::TABLENAME_CONT)
            ->alias('l')
            ->join('left join ' . C('DB_PREFIX') . self::TABLENAME_EMENT . ' as h on h.hc_id = l.id')
            ->join('left join ' . C('DB_PREFIX') . self::TABLENAME_MENT . ' as j on j.id = h.e1_id')
            ->join('left join ' . C('DB_PREFIX') . HotelModel::TABLENAME_HOTEL . ' as s on s.id = l.h_id')
            ->join('left join ' . C('DB_PREFIX') . HotelModel::TABLENAME_GET . ' as e on e.h_id = s.id')
            ->join('left join ' . C('DB_PREFIX') . UserRoleModel::TABLENAME_USER . ' as z on z.id = e.sale_id')
            ->join('left join ' . C('DB_PREFIX') . self::TANLENAME_EXT . ' as p on p.u_id = z.id')
            ->field("SUM(h.e1_num+h.e2_num) as num,FROM_UNIXTIME(l.ctime,'%Y-%m') as months")
            ->where($where)
            ->where(array('s.is_sign' => 1,'e.is_default'=>1))
            ->group('months')->select();
        // 处理所需要的业务数据
        $data['all_num'] = self::toArrayList($e3_num, self::getMonth(), 'num');
        $data['all_num'] = arraySequence($data['all_num'], 'months', 'SORT_ASC');
        $data['goods_nums'] = array();
        foreach ($data['goods_num'] as $keys => $vals) {
            $data['goods_nums'][$keys]['value'] = $vals['num'];
            $data['goods_nums'][$keys]['name'] = $vals['name'];
        }
        $data['goods_nume'] = json_encode($data['goods_nums']);
        return $data;

    }

    /**
     * 统计合同数
     */
    public static function countContract($where)
    {
        $id=session('USERINFO.id');
        $role_id = session('USERINFO.role_id');
        if($role_id == 9){

        }else{
            $where['j.parent_id'] = array('eq',$id);
        }

        $where['s.is_default'] = 1;
        $where['s.sale_id'] = array('neq', 0);
        $data = array();
        // 渠道占比统计
        $data['canal'] = M(self::TABLENAME_CONT)
            ->alias('l')
            ->join('left join ' . C('DB_PREFIX') . HotelModel::TABLENAME_HOTEL . ' as h on h.id = l.h_id')
            ->join('left join ' . C('DB_PREFIX') . HotelGetModel::TABLENAME . ' as s on s.h_id = h.id')
            ->join('left join ' . C('DB_PREFIX') . UserRoleModel::TABLENAME_USER . ' as j on j.id = s.sale_id')
            ->join('left join ' . C('DB_PREFIX') . self::TANLENAME_EXT . ' as p on p.u_id = j.id')
            ->where($where)
            ->where(array('s.is_default' => 1, 's.sale_id' => array('neq', 0)))
            ->field('COUNT(l.id) as num,(CASE WHEN p.channel_type = 1 THEN "内聘" '
                . ' WHEN p.channel_type = 2 THEN "个人" ELSE "团队" END) as user_type_name')
            ->group('user_type_name')->select();
        // 全年趋势图
        $nowtime = strtotime(date("Y-m", strtotime("+1 months", strtotime(date("Y-m", time())))));
        $map['ctime'] = array('lt', $nowtime);
        $data['all_num'] = M(self::TABLENAME_CONT)
            ->alias('l')
            ->join('left join ' . C('DB_PREFIX') . HotelModel::TABLENAME_HOTEL . ' as h on h.id = l.h_id')
            ->join('left join ' . C('DB_PREFIX') . HotelGetModel::TABLENAME . ' as s on s.h_id = h.id')
            ->join('left join ' . C('DB_PREFIX') . UserRoleModel::TABLENAME_USER . ' as j on j.id = s.sale_id')
            ->join('left join ' . C('DB_PREFIX') . self::TANLENAME_EXT . ' as p on p.u_id = j.id')
            ->where($where)
            ->where(array('s.is_default' => 1, 's.sale_id' => array('neq', 0)))
            ->field("COUNT(l.id) as num,FROM_UNIXTIME(l.ctime,'%Y-%m') as months")
            ->group('months')->select();
        $data['all_num'] = self::toArrayList($data['all_num'], self::getMonth(), 'num');
        $data['all_num'] = arraySequence($data['all_num'], 'months', 'SORT_ASC');
        // 列表前十名数据展示
        $rank = M(self::TABLENAME_CONT)
            ->alias('l')
            ->join('left join ' . C('DB_PREFIX') . HotelModel::TABLENAME_HOTEL . ' as h on h.id = l.h_id')
            ->join('left join ' . C('DB_PREFIX') . HotelGetModel::TABLENAME . ' as s on s.h_id = h.id')
            ->join('left join ' . C('DB_PREFIX') . UserRoleModel::TABLENAME_USER . ' as j on j.id = s.sale_id')
            ->join('left join ' . C('DB_PREFIX') . self::TANLENAME_EXT . ' as p on p.u_id = j.id')
            ->where($where)
            ->where(array('s.is_default' => 1, 's.sale_id' => array('neq', 0)))
            ->field('COUNT(l.id) as num,(CASE WHEN p.channel_type  = 1 THEN "内聘" '
                . ' WHEN p.channel_type  = 2 THEN "个人" ELSE "团队" END) as user_type_name,j.real_name as real_name,j.img')
            ->group('real_name')->select();
        // 二维数组排序,生成前十统计列表
        $data['rank'] = arraySequence($rank, 'num');
        // 处理所需要的业务数据
        $data['canals'] = array();
        foreach ($data['canal'] as $keys => $vals) {
            $data['canals'][$keys]['value'] = $vals['num'];
            $data['canals'][$keys]['name'] = $vals['user_type_name'];
        }
        $data['canals'] = json_encode($data['canals']);
        return $data;
    }

    /**
     * 统计渠道数
     */
    public static function countChannel($where)
    {
        $id=session('USERINFO.id');
        $role_id = session('USERINFO.role_id');
        if($role_id == 9){

        }else{
            $where['l.parent_id'] = $id;
        }

        $data = array();
        $data['qd_num'] = M(UserRoleModel::TABLENAME_USER)
            ->alias('l')
            ->join('left join ' . C('DB_PREFIX') . self::TANLENAME_EXT . ' as p on p.u_id = l.id')
            ->where(array('p.u_id' => array('neq', 0)))
            ->field('COUNT(p.u_id) as num , (CASE WHEN p.channel_type = 1 THEN "内聘" '
                . ' WHEN p.channel_type = 2 THEN "个人" ELSE "团队" END) as user_type_name')
            ->where($where)
            ->group('user_type_name')->select();
        if ($where['p.channel_type']) {
            // 全年趋势图
            $nowtime = strtotime(date("Y-m", strtotime("+1 months", strtotime(date("Y-m", time())))));
            $map['ctime'] = array('lt', $nowtime);
            //渠道(1内聘,2个人,3团队)
            $data['all_num_one'] = M(UserRoleModel::TABLENAME_USER)
                ->alias('l')
                ->join('left join ' . C('DB_PREFIX') . self::TANLENAME_EXT . ' as p on p.u_id = l.id')
                ->field("COUNT(id) as num,FROM_UNIXTIME(ctime,'%Y-%m') as months")
                ->where($where)
                ->group('months')->select();
            $data['all_num_one'] = self::toArrayList($data['all_num_one'], self::getMonth(), 'num');
            $data['all_num_one'] = arraySequence($data['all_num_one'], 'months', 'SORT_ASC');
            // 处理所需要的业务数据
            $data['qd_nums'] = array();
            foreach ($data['qd_num'] as $keys => $vals) {
                $data['qd_nums'][$keys]['value'] = $vals['num'];
                $data['qd_nums'][$keys]['name'] = $vals['user_type_name'];
            }
            $data['qd_nums'] = json_encode($data['qd_nums']);
            return $data;
        } else {
            // 全年趋势图
            $nowtime = strtotime(date("Y-m", strtotime("+1 months", strtotime(date("Y-m", time())))));
            $map['ctime'] = array('lt', $nowtime);
            //渠道(1内聘,2个人,3团队)
            $data['all_num_one'] = M(UserRoleModel::TABLENAME_USER)
                ->alias('l')
                ->join('left join ' . C('DB_PREFIX') . self::TANLENAME_EXT . ' as p on p.u_id = l.id')
                ->field("COUNT(id) as num,FROM_UNIXTIME(ctime,'%Y-%m') as months")
                ->where($map)
                ->where($where)
                ->where(array('p.channel_type' => array('eq', 2)))
                ->group('months')->select();
            $data['all_num_one'] = self::toArrayList($data['all_num_one'], self::getMonth(), 'num');
            $data['all_num_one'] = arraySequence($data['all_num_one'], 'months', 'SORT_ASC');
            $data['all_num_two'] = M(UserRoleModel::TABLENAME_USER)
                ->alias('l')
                ->join('left join ' . C('DB_PREFIX') . self::TANLENAME_EXT . ' as p on p.u_id = l.id')
                ->field("COUNT(id) as num,FROM_UNIXTIME(ctime,'%Y-%m') as months")
                ->where($map)
                ->where($where)
                ->where(array('p.channel_type' => array('eq', 1)))
                ->group('months')->select();
            $data['all_num_two'] = self::toArrayList($data['all_num_two'], self::getMonth(), 'num');
            $data['all_num_two'] = arraySequence($data['all_num_two'], 'months', 'SORT_ASC');
            $data['all_num_the'] = M(UserRoleModel::TABLENAME_USER)
                ->alias('l')
                ->join('left join ' . C('DB_PREFIX') . self::TANLENAME_EXT . ' as p on p.u_id = l.id')
                ->field("COUNT(id) as num,FROM_UNIXTIME(ctime,'%Y-%m') as months")
                ->where($map)
                ->where($where)
                ->where(array('p.channel_type' => array('eq', 3)))
                ->group('months')->select();
            $data['all_num_the'] = self::toArrayList($data['all_num_the'], self::getMonth(), 'num');
            $data['all_num_the'] = arraySequence($data['all_num_the'], 'months', 'SORT_ASC');
            // 处理所需要的业务数据
            $data['qd_nums'] = array();
            foreach ($data['qd_num'] as $keys => $vals) {
                $data['qd_nums'][$keys]['value'] = $vals['num'];
                $data['qd_nums'][$keys]['name'] = $vals['user_type_name'];
            }
            $data['qd_nums'] = json_encode($data['qd_nums']);
            return $data;
        }
    }

    /**
     * php获取以前的十二个月(包含本月)
     */
    public static function getMonth($num = 12)
    {
        $temp = array();
        $nowtime = strtotime(date("Y-m", strtotime("+1 months", strtotime(date("Y-m", time())))));
        while ($num > 0) {
            $num--;
            $nowtime = strtotime('-1 month', $nowtime);
            $temp[] = date('Y-m', $nowtime);
        }
        return $temp;
    }

    /**
     * 整合数组
     */
    public static function toArrayList($data, $month, $field)
    {
        $item = array();
        foreach ($month as $key => $val) {
            $item[$key][$field] = '0';
            $item[$key]['months'] = $val;
        }
        // 合并数组
        $List = array_merge_recursive((array)$data, (array)$item);
        $newArray = self::listArrayKey($List, 'months');
        return $newArray;
    }

    /**
     * 比对数组,存在两个的,则保留有值得数据
     */
    static public function listArrayKey($arr, $key)
    {
        $list = array();
        foreach ($arr as $k => $v) {
            if (in_array($v[$key], $list)) {
                unset($arr[$k]);
            } else {
                $list[$k] = $v[$key];
            }
        }
        return $arr;
    }
}