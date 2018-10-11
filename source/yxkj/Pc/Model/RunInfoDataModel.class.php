<?php
/**
 * Author: ' Silent
 * Time: 2017/9/14 10:07
 */

namespace Pc\Model;


use Pc\Server\Http;

class RunInfoDataModel extends BaseModel
{
    const STATUS_ONE = 'green';  // 运行正常
    const STATUS_TWO = '';  // 暂无开启
    const STATUS_THREE = 'red'; // 超标预警
    const STATUS_FOUR = 'orange'; // 离线预警

    const STATE_ONE = 'green'; // 优
    const STATE_TWO = 'orange'; // 良
    const STATE_THE = 'red'; //查
    const STATE_FOU = ''; //设备未检测到

    /**
     * 查出该用户的归属酒店
     */
    public static function getHotelId($user_id)
    {
        $where = array();
        $where['id'] = array('eq', $user_id);
        return M(UserRoleModel::TABLENAME_USER)->where($where)->getField('hotel_id');
    }

    /**
     * 查出该酒店的签约合同和房间数据
     */
    public static function getHotelRoom($user_id)
    {
        $where = array();
        //$where['p.status'] = array('in', '3,4');
        $where['p.h_id'] = self::getHotelId($user_id);
        $where['l.status'] = array('eq', 1);
        $list = M(RmainModel::TABLENAME_IFNO)
            ->alias('l')
            ->join('left join ' . C('DB_PREFIX') . RmainModel::TABLENAME_ORDER . ' as p on p.id = l.oo_id')
            ->where($where)
            ->field('l.room_sno,l.equipment_sno,l.ctime')
            ->select();
        foreach ($list as $key => &$val) {
            $val['sort'] = preg_replace('/([\x80-\xff]*)/i', '', $val['room_sno']);
        }
        return arraySequence($list, 'sort', 'SORT_ASC');
    }

    /**
     * 查出该酒店的签约合同和房间数据
     */
    public static function getHotelRooms($id)
    {
        $where = array();
//        $where['p.status'] = array('eq', 4);
        //$where['p.status'] = array('in','3,4');
        $where['p.h_id'] = $id;
        $where['l.status'] = array('eq', 1);
        $list = M(RmainModel::TABLENAME_IFNO)
            ->alias('l')
            ->join('left join ' . C('DB_PREFIX') . RmainModel::TABLENAME_ORDER . ' as p on p.id = l.oo_id')
            ->where($where)
            ->field('l.room_sno,l.equipment_sno')
            ->select();
        foreach ($list as $key => &$val) {
            $val['sort'] = preg_replace('/([\x80-\xff]*)/i', '', $val['room_sno']);
        }
        return arraySequence($list, 'sort', 'SORT_ASC');
    }

    /**
     * 获取室内空气质量统计
     * @param $user_id
     */
    public static function getRunInfoData($user_id)
    {
        $data = self::getHotelRoom($user_id);
        $items = array();
        foreach ($data as $key => $val) {
            // 设备方会返回所有数据
            $datas = RunInfoModel::getDevicesHoures($val['equipment_sno'], 7 * 24);
            if ($datas['data']) {
                $items[] = $datas['data']['hours'];
            }

        }
        // 产品经理当日下午两点到次日两点
        $beginTodays = mktime(14, 0, 0, date('m'), date('d'), date('Y'));
        if (time() < $beginTodays) {
            // 获取昨天的数据
            $beginToday = mktime(14, 0, 0, date('m'), date('d'), date('Y'));
            $beginToday = strtotime(date('Y-m-d H:i:s', strtotime("-1 day", $beginToday)));
            $endToday = time();
        } else {
            $beginToday = mktime(14, 0, 0, date('m'), date('d'), date('Y'));
            $endToday = strtotime(date('Y-m-d H:i:s', strtotime("+1 day", $beginToday)));
        }
        $items = array_values($items);
        $infodata = array();
        foreach ($items as $f) {
            foreach ($f as $k => $z) {
                if ($z['date'] >= $beginToday && $z['date'] <= $endToday) {
                    $infodata[] = $z;
                }
            }
        }
        // 统计一周的数据 相同的则相加
        $item = array();
        foreach ($infodata as $keys => $vals) {
            // 以天为统计的,pm数量相加
            if (!isset($item[$vals['fulldate']])) {
                $item[$vals['fulldate']] = $vals;
            } else {
                $item[$vals['fulldate']]['pm25'] = $vals['pm25'];
                $item[$vals['fulldate']]['apm25'] = $vals['apm25'];
            }
        }
        return $item;
    }

    /**
     * 获取室内空气质量统计
     * @param $user_id
     */
    public static function getRunInfoDatas($id)
    {
        $data = self::getHotelRooms($id);
        $items = array();
        foreach ($data as $key => $val) {
            // 设备方会返回所有数据
            $datas = RunInfoModel::getDevicesHoures($val['equipment_sno'], 7 * 24);
            if ($datas['data']) {
                $items[] = $datas['data']['hours'];
            }

        }
        $beginTodays = mktime(14, 0, 0, date('m'), date('d'), date('Y'));
        if (time() < $beginTodays) {
            // 获取昨天的数据
            $beginToday = mktime(14, 0, 0, date('m'), date('d'), date('Y'));
            $beginToday = strtotime(date('Y-m-d H:i:s', strtotime("-1 day", $beginToday)));
            $endToday = time();
        } else {
            $beginToday = mktime(14, 0, 0, date('m'), date('d'), date('Y'));
            $endToday = strtotime(date('Y-m-d H:i:s', strtotime("+1 day", $beginToday)));
        }
        $items = array_values($items);
        $infodata = array();
        foreach ($items as $f) {
            foreach ($f as $k => $z) {
                if ($z['date'] >= $beginToday && $z['date'] <= $endToday) {
                    $infodata[] = $z;
                }
            }
        }
        // 统计一周的数据 相同的则相加
        $item = array();
        foreach ($infodata as $keys => $vals) {
            // 以天为统计的,pm数量相加
            if (!isset($item[$vals['fulldate']])) {
                $item[$vals['fulldate']] = $vals;
            } else {
                $item[$vals['fulldate']]['pm25'] = $vals['pm25'];
                $item[$vals['fulldate']]['apm25'] = $vals['apm25'];
            }
        }
        return $item;
    }

    /**
     * 获取指定空气质量统计(统计图所需要的数据)
     */
    public static function getEchartsDate($user_id, $time, $hotel_id, $room)
    {
        if ($room) {
            $data = array(array('equipment_sno' => $room));
        } else {
            if ($hotel_id) {
                $data = self::getHotelRooms($hotel_id);
            } else {
                $data = self::getHotelRoom($user_id);
            }
        }
        $listArray = array();
        foreach ($data as $key => $val) {
            $num = ceil(($key + 2) / 100);
            // 产品经理当日下午两点到次日两点
            if ($time) {
                $dates = $time . ' 14:00:00';
                $beginToday = strtotime($dates);
                $endToday = strtotime(date('Y-m-d H:i:s', strtotime("+1 day", $beginToday)));
            } else {
                // 产品经理当日下午两点到次日两点
                $beginTodays = mktime(14, 0, 0, date('m'), date('d'), date('Y'));
                if (time() < $beginTodays) {
                    // 获取昨天的数据
                    $beginToday = mktime(14, 0, 0, date('m'), date('d'), date('Y'));
                    $beginToday = strtotime(date('Y-m-d H:i:s', strtotime("-1 day", $beginToday)));
                    $endToday = time();
                } else {
                    $beginToday = mktime(14, 0, 0, date('m'), date('d'), date('Y'));
                    $endToday = strtotime(date('Y-m-d H:i:s', strtotime("+1 day", $beginToday)));
                }
            }
            $where['upload_time'] = array(array('gt', $beginToday), array('lt', $endToday), 'AND');
            $where['device_code'] = $val['equipment_sno'];
            $datas = M('UploadDeviceInfo_' . $num)->where($where)->field("upload_times,indoor_pm")->select();
            foreach ($datas as $keys => &$vals) {
                $vals['sort_times'] = strtotime($vals['upload_times']);
                $vals['group_times'] = date('Y-m-d/H', strtotime($vals['upload_times']));
            }
            $listArray[] = $datas;
        }
        $result = [];
        array_map(function ($value) use (&$result) {
            $result = array_merge($result, array_values($value));
        }, $listArray);
        $listdata = arraySequence($result, 'sort_times', 'SORT_ASC');
        $results = array();
        foreach ($listdata as $k => $v) {
            $results[$v['group_times']][] = $v;
        }
        $listAllDatas = array();
        foreach ($results as $f => $h) {
            $num = count($h);
            $listAllDatas[$f]['num'] = ceil(array_sum(i_array_column($h, 'indoor_pm')) / $num);
            $listAllDatas[$f]['date'] = $h[0]['group_times'];
        }
        return $listAllDatas;
    }

    /**
     * 获取当天空气质量统计(统计图所需要的数据)
     */
    public static function getEcharts($user_id)
    {
        $data = self::getHotelRoom($user_id);
        $items = array();
        foreach ($data as $key => $val) {
            // 设备方会返回所有数据
            $datas = RunInfoModel::getDevicesHoures($val['equipment_sno'], 7 * 24);
            $items[] = $datas['data']['hours'];
        }
        $infodata = array();
//        $startTime = strtotime(date("Y-m-d H:i:s", mktime(0, 0, 0, date("m"), date("d") - date("w") + 1, date("Y"))));
//        $endTime = strtotime(date("Y-m-d H:i:s", mktime(23, 59, 59, date("m"), date("d") - date("w") + 7, date("Y"))));
        $beginToday = mktime(14, 0, 0, date('m'), date('d'), date('Y'));
        $endToday = strtotime(date('Y-m-d H:i:s', strtotime("+1 day", $beginToday)));
        foreach ($items as $f => $j) {
            foreach ($j as $k => &$h) {
                $h['time'] = date('Y-m-d', $h['date']);
                if ($h['date'] > $startTime && $h['date'] < $endTime) {
                    $infodata[] = $h;
                }
            }
        }
        // 统计一周的数据 相同的则相加
        $items = array();
        foreach ($infodata as $keys => $vals) {
            // 以天为统计的,pm数量相加
            if (!isset($items[$vals['time']])) {
                $items[$vals['time']] = $vals;
            } else {
                $items[$vals['time']]['pm25'] += $vals['pm25'];
                $items[$vals['time']]['apm25'] += $vals['apm25'];
            }
        }
        return $items;
    }


    /**
     * 检测当前设备运行情况
     */
    public static function getDevicesInfo($user_id, $hotel_id, $room)
    {
        $listData = array();
        if ($room) {
            $data = array(array('equipment_sno' => $room));
        } else {
            if ($hotel_id) {
                $data = self::getHotelRooms($hotel_id);
            } else {
                $data = self::getHotelRoom($user_id);
            }
        }
        $allhours = '';
        $day = '';
        $allday = '';
        foreach ($data as $key => $val) {
            $list = RunInfoModel::getDevicesTime($val['equipment_sno']);
            $allhours += $list['hours'];
            $day += $list['day'];
            $allday += $list['allDay'];
        }
        $listData['pm'] = DeviceRunModel::DevsRunInPm25($data);
        $listData['data'] = $data;
        $listData['allHours'] = $allhours;
        $listData['day'] = $day;
        $listData['allDay'] = $allday;
        return $listData;
    }

    /**
     * 存入redis,缓存时间
     * @author ' Silent <1136359934@qq.com>
     * @param $hotel_id
     * @return mixed
     */
    public static function getDevicesInfoTime($hotel_id)
    {

        $data = self::getHotelRooms($hotel_id);
        $allhours = '';
        $day = '';
        $allday = '';
        foreach ($data as $key => $val) {
            $list = RunInfoModel::getDevicesTime($val['equipment_sno']);
            $allhours += $list['hours'];
            $day += $list['day'];
            $allday += $list['allDay'];
        }
        $listData['pm'] = DeviceRunModel::DevsRunInPm25($data);
        $listData['allHours'] = $allhours;
        $listData['day'] = $day;
        $listData['allDay'] = $allday;
        return $listData;
    }

    /**
     * 检测当前设备运行情况
     */
    public static function getDevicesInfos($id, $user_id = '')
    {
        $listData = array();
        if ($id) {
            $data = self::getHotelRooms($id);
        } else {
            $data = self::getHotelRoom($user_id);
        }
        // 总天数
        $allhours = '';
        $day = '';
        $allday = '';
        // 获取到设备最新状态
        foreach ($data as $key => $val) {
            $list = RunInfoModel::getDevicesTime($val['equipment_sno']);
            $allhours += $list['hours'];
            $day += $list['day'];
            $allday += $list['allDay'];
        }
        $listData['pm'] = DeviceRunModel::DevsRunInPm25($data);
        $listData['data'] = $data;
        $listData['allHours'] = $allhours;
        $listData['day'] = $day;
        $listData['allDay'] = $allday;
        return $listData;
    }

    /**
     * 检测当前设备的空气质量值
     */
    public static function getDevicesPm($user_id)
    {
        $listArray = array();
        $data = self::getHotelRoom($user_id);
        $pm25 = '';
        $apm25 = '';
        foreach ($data as $key => $val) {
//            $newdata = RunInfoModel::getNewDevicesInfo($val['equipment_sno']);
            $infodata = RunInfoModel::getOneInfo($val['equipment_sno']);
            $city_id = $infodata['data']['devices'][0]['city'];
            $area_id = $infodata['data']['devices'][0]['area'];
            $weatch = RunInfoModel::getCityData($city_id, $area_id);
            $apm25 += $weatch['info']['PM25'];
//            $pm25 += $newdata['data']['devices'][0]['data']['pm25'];
//            $data[$key]['status'] = self::handleDataOne($newdata);
        }
        $pm25 = DeviceRunModel::DevsRunInPm25($data);
        $listArray['apm25'] = intval($apm25 / count($data));
        $listArray['apm25s'] = testingPm($listArray['apm25']);
        $listArray['pm25'] = DeviceRunModel::DevsRunInPm25($data);;
        $listArray['pm25s'] = testingPm($listArray['pm25']);
        $listArray['data'] = $data;
        $listArray['room_num'] = count($data);
        return $listArray;
    }

    /**
     * 检测当前设备的空气质量值
     */
    public static function getDevicesPms($hotel_id)
    {
        $listArray = array();
        $data = self::getHotelRooms($hotel_id);
        $pm25 = '';
        $apm25 = '';
        foreach ($data as $key => $val) {
//            $newdata = RunInfoModel::getNewDevicesInfo($val['equipment_sno']);
            $infodata = RunInfoModel::getOneInfo($val['equipment_sno']);
            $city_id = $infodata['data']['devices'][0]['city'];
            $area_id = $infodata['data']['devices'][0]['area'];
            $weatch = RunInfoModel::getCityData($city_id, $area_id);
            $apm25 += $weatch['info']['PM25'];
//            $pm25 += $newdata['data']['devices'][0]['data']['pm25'];
//            $data[$key]['status'] = self::handleDataOne($newdata);
        }
        $listArray['apm25'] = testingPm($listArray['apm25']);
        $listArray['apm25s'] = testingPm($listArray['apm25']);
        $listArray['pm25'] = DeviceRunModel::DevsRunInPm25($data);
        $listArray['pm25s'] = testingPm(testingPm($listArray['apm25']));
        $listArray['data'] = $data;
        $listArray['room_num'] = count($data);
        return $listArray;
    }

    /**
     * 处理设备返回的信息
     */
    public static function handleDataOne($data)
    {
        if ($data['code'] == 0) {
            // 成功获取到该设备的最后一次信息
            $pm25 = $data['data']['devices'][0]['data']['pm25'];
            $num = testingPm($pm25);
            if ($num == 1) {
                return self::STATE_ONE;
            } else if ($num == 2) {
                return self::STATE_TWO;
            } else if ($num == 3) {
                return self::STATE_THE;
            }
        }
        return self::STATE_FOU;
    }

    /**
     * 处理设备返回信息
     * online 在线状态 1=在线 0=离线
     * power 开机状态 1=开机 0=关机
     * 检测到室内数据超标，该房间就显示为超标。后台无法连接到某间房间的设备，表示设备离线，当前的房间就是离线
     * 硬件厂商会提供每个房间，每个指标的数据。我们拿到后和参考标准进行比对，得出是否超标的结论。
     */
    public static function handleData($data, $newdata)
    {
        $online = $data['data']['devices'][0]['online'];
        $power = $data['data']['devices'][0]['power'];
        $pm25 = $newdata['data']['devices'][0]['data']['pm25'];
        // 检测是否超标预警(设备正常运行)
        if ($online == 1 && $power == 1) {
            // 根据设备最新更新的信息获取pm25,判断房间是否超标
            $num = testingPm($pm25);
            if ($num == 3) {
                return self::STATUS_THREE;
            }
        }
        // 离线预警
        if ($online == 0 && $power == 1) {
            return self::STATUS_FOUR;
        }
        // 暂未开启
        if ($power == 0 && $online == 0) {
            return self::STATUS_TWO;
        }
        // 暂未开启
        if ($power == '' && $online == '') {
            return self::STATUS_TWO;
        }
        // 运行正常
        if ($online == 1 && $power == 1) {
            return self::STATUS_ONE;
        }
    }
}