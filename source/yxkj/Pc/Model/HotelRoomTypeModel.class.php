<?php
/**
 * Author: ' Silent
 * Time: 2017/9/4 15:26
 */

namespace Pc\Model;


class HotelRoomTypeModel extends BaseModel
{
    // 定义表名
    const TABLENAME = 'hotel_room_type';


    /**
     * 添加客房类型
     * @param $data
     */
    public static function addRoomNum($id, $data)
    {
        $dataList = array();
        foreach ($data as $key => &$val) {
            $val['h_id'] = $id;
            $dataList[] = $val;
        }
        return M(self::TABLENAME)->addAll($dataList, array(), true);
    }

    /**
     * 编辑客房类型
     * @param $data
     */
    public static function editRoomNum($data)
    {

    }
}