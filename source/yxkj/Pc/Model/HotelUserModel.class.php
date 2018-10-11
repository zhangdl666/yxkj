<?php
/**
 * Author: ' Silent
 * Time: 2017/9/5 15:35
 */

namespace Pc\Model;

use Think\Model;

class HotelUserModel extends BaseModel
{
    // 定义状态枚举类
    const STATUS_ONE = 1;  // 我已认领
    const STATUS_TWO = 2;  // 已认领
    const STATUS_THREE = 3;  //未认领

    /**
     * 查看当前销售员与当前酒店关系
     * @param $user_id
     * @param $hotel_id
     * @return int
     */
    public static function checkUserStatus($user_id, $hotel_id)
    {
        $where = array();
        $where['sale_id'] = array('eq', $user_id);
        $where['h_id'] = array('eq', $hotel_id);
        $where['is_default'] = array('eq', 1);
        $info = M(HotelGetModel::TABLENAME)->where($where)->find();
        if ($info) {
            return self::STATUS_ONE;
        }
        // 查看当前酒店是否被其他销售员认领
//        $map['h_id'] = array('eq', $hotel_id);
//        $map['is_default'] = array('eq', 1);
//        $infos = M(HotelGetModel::TABLENAME)->where($map)->find();
//        if ($infos) {
//            return self::STATUS_TWO;
//        }
//        return self::STATUS_THREE;
        $infos = M(HotelModel::TABLENAME_HOTEL)->where(array('id'=>$hotel_id,'is_get'=>1))->find();
        if($infos){
            return self::STATUS_TWO;
        }else{
           return self::STATUS_THREE;
        }
    }

    /**
     * 我的认领
     * @param $user_id
     * @return mixed
     */
    public static function getMyListHotel($page = 1, $pageSize = 8, $where, $user_id)
    {
        $where['l.sale_id'] = array('eq', $user_id);
        $where['l.is_default'] = array('eq', 1);
        $where['p.status'] = array('eq', 1);
        return M(HotelGetModel::TABLENAME)
                            ->alias('l')
                            ->join('left join ' . C('DB_PREFIX') . HotelModel::TABLENAME_HOTEL . ' as p on p.id = l.h_id')
                            ->join('left join ' . C('DB_PREFIX') . HotelModel::TABLENAME_TYPE . ' as s on s.id = p.ht_id')
                            ->field('p.id,p.name,l.is_default,s.name as type_name,p.tell,p.img')
                            ->page($page . ',' . $pageSize)
                            ->where($where)
                            ->order('l.ctime desc')
                            ->select();
    }


    /**
     * 我的认领
     * @param $user_id
     * @return mixed
     */
    public static function getMyListHotelCount($where, $user_id)
    {
        $where['l.sale_id'] = array('eq', $user_id);
        $where['l.is_default'] = array('eq', 1);
        $where['p.status'] = array('eq', 1);
        return M(HotelGetModel::TABLENAME)
                            ->alias('l')
                            ->join('left join ' . C('DB_PREFIX') . HotelModel::TABLENAME_HOTEL . ' as p on p.id = l.h_id')
                            ->join('left join ' . C('DB_PREFIX') . HotelModel::TABLENAME_TYPE . ' as s on s.id = p.ht_id')
                            ->field('p.id,p.name,l.is_default,s.name as type_name,p.tell,p.img')
                            ->where($where)
                            ->order('l.ctime desc')
                            ->count();
    }

    /**
     * 修改酒店认领状态和时间
     * @param $id
     */
    public static function saveHotelCiam($id)
    {
        $data = array();
        $data['is_get'] = 1;
        $data['get_time'] = time();
        return M(HotelModel::TABLENAME_HOTEL)->where(array('id' => $id))->save($data);
    }

    /**
     * 认领酒店
     * @param $hotel_id
     * @param $user_id
     */
    public static function getUserClaim($hotel_id, $user_id)
    {
        $result = array('code' => 1, 'message' => '认领失败');
        // 检查当前酒店是否被自己认领
        $status = self::checkUserStatus($user_id, $hotel_id);
        if ($status == self::STATUS_ONE) {
            $result['message'] = '当前酒店,你已认领';
            return $result;
        }
        // 检查当前酒店是否被其他人认领
        if ($status == self::STATUS_TWO) {
            $result['message'] = '当前酒店,已被其他人认领';
            return $result;
        }
        $HotelUserModel = D(HotelModel::TABLENAME_HOTEL);
        // 开启事务
        $HotelUserModel->startTrans();
        $where = array();
        $where['id'] = $hotel_id;
        $where['is_get'] = array('eq', HotelModel::STATUS_NCLAIM);
        $HotelInfo = $HotelUserModel->where($where)->lock(true)->find();
        if ($HotelInfo) {
            $Hotelstatus = self::saveHotelCiam($hotel_id);
            $HotelGetStatus = HotelGetModel::addHotelGet($user_id, $hotel_id);
            // 增加信息
            if ($HotelGetStatus && $Hotelstatus) {
                self::SaleExtInc($user_id);
                $HotelUserModel->commit();
                $result['code'] = 0;
                $result['message'] = '认领成功';
                return $result;
            }
        } else {
            $HotelUserModel->rollback();
        }
        return $result;
    }

    /**
     * 添加酒店额外信息表(增加)
     */
    public static function SaleExtInc($user_id){
//        $where = array();
//        $where['u_id'] = array('eq', $user_id);
//        M('SaleExt')->where($where)->setInc('hotel_num');
        return true;
    }

    /**
     * 查询额外信息表
     */
    public static function getSaleExt($user_id){
//        $where = array();
//        $where['u_id'] = array('eq', $user_id);
//        return M('SaleExt')->where($where)->find();
        return true;
    }

    /**
     * 添加酒店额外信息表(减少)
     */
    public static function SaleExtDec($user_id){
//        $where = array();
//        $where['u_id'] = array('eq', $user_id);
//        M('SaleExt')->where($where)->setDec('hotel_num');
        return true;
    }

    /**
     * 获取所有销售人员
     */
    public static function getUserList()
    {
        return M(HotelModel::TABLENAME_USER)
            ->where(array('status' => 1,'role_id'=> 2))
            ->order('ctime desc')
            ->field('real_name as name,id')->select();
    }

    /**
     * 取消认领
     */
    public static function getNoUserClaim($hotel_id, $user_id)
    {
        $result = array('code' => 1, 'message' => '取消失败');
        $HotelUserModel = D(HotelModel::TABLENAME_HOTEL);
        $HotelInfo = $HotelUserModel->where(array('id' => $hotel_id))->lock(true)->find();
        // 开启事务
        $HotelUserModel->startTrans();
        if ($HotelInfo) {
            // 清除酒店认领信息
            $HotelInfo = HotelModel::cancelClaim($hotel_id);
            // 将认领中间表设置为0
            $HotelGetInfo = HotelGetModel::cancelInfo($user_id, $hotel_id);
            if ($HotelInfo && $HotelGetInfo) {
                $user_data = self::getSaleExt($user_id);
                if($user_data['hotel_num'] > 0){
                    self::SaleExtDec($user_id);
                }
                $HotelUserModel->commit();
                $result['code'] = 0;
                $result['message'] = '取消认领成功';
                return $result;
            }
        } else {
            $HotelUserModel->rollback();
        }
        return $result;
    }
}