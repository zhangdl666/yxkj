<?php
/**
 * Author: ' Silent
 * Time: 2017/9/4 14:57
 */

namespace Pc\Model;


class HotelGetModel extends BaseModel
{
    // 定义表名
    const TABLENAME = 'hotel_get';

    /**
     * 添加酒店认领人员
     * @param $data
     */
    public static function addHotelUser($data)
    {
        // 用户也可以不选择
        if (!$data['sale_id']) {
            return true;
        }
        HotelUserModel::SaleExtInc($data['sale_id']);
        $data['ctime'] = time();
        return M(self::TABLENAME)->add($data);
    }

    /**
     * 修改酒店认领人员
     * @param $id
     * @param array $data
     */
    public static function editHotelUser($data, $HotelGetId)
    {
        $HotelGetModel = D(self::TABLENAME);
        $HotelGetModel->startTrans();
        // 先删除后添加数据
        if ($HotelGetId) {
            $SaleDatas = $HotelGetModel->where(array('id' => $HotelGetId))->find();
            if ($SaleDatas['sale_id'] != $data['sale_id']) {
                $m = $HotelGetModel->where(array('id' => $HotelGetId))->save(array('is_default' => 0));
            } else if ($SaleDatas['sale_id'] == $data['sale_id']) {
                return true;
            }
        } else {
            $m = true;
        }
        // 更新酒店的认领时间
        M(HotelModel::TABLENAME_HOTEL)->where(array('id' => $data['h_id']))->save(array('get_time' => time()));
        if ($m) {
            // 添加数据
            $data['ctime'] = time();
            $info = $HotelGetModel->add($data);
            if ($info) {
//                if ($data && $SaleDatas) {
//                    if ($SaleDatas['sale_id'] == $data['sale_id']) {
//                        // 不做判断
//                    } else {
//                        HotelUserModel::SaleExtInc($data['sale_id']);
//                        $userData = HotelUserModel::getSaleExt($SaleDatas['sale_id']);
//                        if ($userData['hotel_num'] > 0) {
//                            HotelUserModel::SaleExtDec($SaleDatas['sale_id']);
//                        }
//                    }
//                }
                $HotelGetModel->commit();
                return $info;
            }
        } else {
            $HotelGetModel->rollback();
            return false;
        }
    }

    /**
     * 认领酒店
     * @param $user_id
     * @param $hotel_id
     * @return mixed
     */
    public static function addHotelGet($user_id, $hotel_id)
    {
        $data = array();
        $data['h_id'] = $hotel_id;
        $data['sale_id'] = $user_id;
        $data['ctime'] = time();
        $data['is_default'] = 1;
        return M(self::TABLENAME)->add($data);
    }

    /**
     * 查询认领的唯一标识符
     * @param $user_id
     * @param $Hotel_id
     */
    public static function getId($user_id, $Hotel_id)
    {
        $where = array();
        $where['h_id'] = $Hotel_id;
        $where['sale_id'] = $user_id;
        $where['is_default'] = 1;
        return M(self::TABLENAME)->where($where)->getField('id');
    }

    /**
     * 取消认领  将认领状态改为0
     * @param $user_id
     * @param $Hotel_id
     */
    public static function cancelInfo($user_id, $Hotel_id)
    {
        $where = array();
        $where['h_id'] = $Hotel_id;
        $where['sale_id'] = $user_id;
        $where['is_default'] = 1;
        return M(self::TABLENAME)->where($where)->save(array('is_default' => 0));
    }

    /**
     * 认领详细信息
     * @param $Hotel_id
     * @return mixed
     */
    public static function getInfos($Hotel_id)
    {
        $where = array();
        $where['h_id'] = $Hotel_id;
        $where['is_default'] = 1;
        return M(self::TABLENAME)->where($where)->find(array('is_default' => 0));
    }
}