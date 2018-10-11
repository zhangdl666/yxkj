<?php
/**
 * Author: ' Silent
 * Time: 2017/9/13 17:16
 */

namespace Wx\Model;

use Pc\Server\Http;

class RunInfoModel extends BaseModel
{
    /**
     * 表名
     */
    const TABLENAME_LOG = 'record_log';

    /**
     * 接口地址
     */
    const Token = '523c374d-fa46-d6a7-a068-d80d88612dfc';  // 开发者公司的apiToken

    // 2.0接口(启用的是2.0接口)
    const GetDeviceInfo = "http://api.bjhike.com/api/v2_deviceInfo"; // 获取设备信息(传入错误的mac地址,也会返回200,但是devices与message是为空的)
    const GetDeviceDay = "http:/api.bjhike.com/api/v2_days"; // 以天为单位的数据接口
    const getDeviceNewInfo = "http://api.bjhike.com/api/v2_deviceData"; // 设备最新一次状态
    const GetDeviceHoures = "http://api.bjhike.com/api/v2_hours"; // 以小时为单位的数据接口
    const GetDeviceAllday = "http://api.bjhike.com/api/v2_device_alldata"; // 获取设备某时间段的信息
    const GetCityData = "http://api.bjhike.com/api/v2_weather"; //获取城市区域的天气
    const GetDevicesTime = "http://cloud.bjhike.com/api/getDeviceLoginLog/"; // 获取时间锻

    // 2.0接口(成功返回的状态吗)
    const RETURN_CODE = 200;

    /**
     * 参数1
     * @return array
     */
    public static function getParamsone($mac)
    {
        $params = array(
            'companyToken' => self::Token,
            'mac' => $mac,
        );
        return $params;
    }

    /**
     * 参数2
     * @param $mac
     * @return array
     */
    public static function getParamstwo($mac, $days)
    {
        $params = array(
            'companyToken' => self::Token,
            'mac' => $mac,
            'days' => $days,
        );
        return $params;
    }

    /**
     * 参数3
     * @param $mac
     * @return array
     */
    public static function getParamsthe($mac, $hours)
    {
        $params = array(
            'companyToken' => self::Token,
            'mac' => $mac,
            'hours' => $hours,
        );
        return $params;
    }

    /**
     * 参数4
     * @param $mac
     * @return array
     */
    public static function getParamsfou($mac, $starttime, $endtime)
    {
        $params = array(
            'companyToken' => self::Token,
            'mac' => $mac,
            'starttime' => $starttime,
            'endtime' => $endtime,
        );
        return $params;
    }

    /**
     * 参数5
     * @param $mac
     * @return array
     */
    public static function getParamsfive($city, $area)
    {
        $params = array(
            'city' => $city,
            'area' => $area,
        );
        return $params;
    }

    /**
     * 参数6
     * @param $mac
     */
    public static function getParamsSix($mac)
    {
        $params = array(
            'mac' => $mac,
        );
        return $params;
    }

    /**
     * (1)
     * @methdod get
     * 获取设备信息
     * CompanyToken 开发者公司的apiToken
     * DeviceMacs 设备Mac地址
     * @param $mac
     */
    public static function getOneInfo($mac)
    {
        $params = self::getParamsone($mac);
        // 将返回的数据转换为数组格式
        $data = json_decode(Http::get(self::GetDeviceInfo, $params), true);
        // 请求的url
        $url = self::GetDeviceInfo;
        // 请求的参数
        $params = json_encode($params);
        // 处理返回的数据
        return self::process($data, $mac, $url, $params, $type = 'GET');
    }

    /**
     * (2)
     * @methdod get
     * 设备最新一次状态
     * CompanyToken 开发者公司的apiToken
     * DeviceMacs 设备Mac地址
     * @param $mac
     */
    public static function getNewDevicesInfo($mac)
    {
        $params = self::getParamsone($mac);
        // 将返回的数据转换为数组格式
        $data = json_decode(Http::get(self::getDeviceNewInfo, $params), true);
        // 请求的url
        $url = self::GetDeviceInfo;
        // 请求的参数
        $params = json_encode($params);
        // 处理返回的数据
        return self::process($data, $mac, $url, $params, $type = 'GET');
    }

    /**
     * (3)
     * @methdod get
     * 以天为单位的数据接口
     * CompanyToken 开发者公司的apiToken
     * DeviceMacs 设备Mac地址
     * @param $mac
     */
    public static function getDevicesDay($mac, $days = 30)
    {
        $params = self::getParamsTwo($mac, $days);
        // 将返回的数据转换为数组格式
        $data = json_decode(Http::get(self::GetDeviceDay, $params), true);
        // 请求的url
        $url = self::GetDeviceInfo;
        // 请求的参数
        $params = json_encode($params);
        // 处理返回的数据
        return self::processOne($data, $mac, $url, $params, $type = 'GET');
    }

    /**
     * (4)
     * @methdod get
     * 以小时为单位的数据接口
     * CompanyToken 开发者公司的apiToken
     * DeviceMacs 设备Mac地址
     * @param $mac
     */
    public static function getDevicesHoures($mac, $hours = 168)
    {
        $params = self::getParamsthe($mac, $hours);
        // 将返回的数据转换为数组格式
        $data = json_decode(Http::get(self::GetDeviceHoures, $params), true);
        // 请求的url
        $url = self::GetDeviceInfo;
        // 请求的参数
        $params = json_encode($params);
        // 处理返回的数据
        return self::processOne($data, $mac, $url, $params, $type = 'GET');
    }

    /**
     * (5)
     * @methdod get
     * 获取设备某时间段的信息
     * CompanyToken 开发者公司的apiToken
     * DeviceMacs 设备Mac地址
     * @param $mac
     */
    public static function getDevicesalldata($mac, $startime = '1505145600', $endtime = '1505231999')
    {
        $params = self::getParamsfou($mac, $startime, $endtime);
        // 将返回的数据转换为数组格式
        $data = json_decode(Http::get(self::GetDeviceAllday, $params), true);
        // 请求的url
        $url = self::GetDeviceInfo;
        // 请求的参数
        $params = json_encode($params);
        // 处理返回的数据
        return self::processOne($data, $mac, $url, $params, $type = 'GET');
    }

    /**
     * (6)
     * @methdod get
     * 获取城市天气信息
     * CompanyToken 开发者公司的apiToken
     * DeviceMacs 设备Mac地址
     * @param $mac
     */
    public static function getCityData($city_id, $area_id)
    {
        $params = self::getParamsfive($city_id, $area_id);
        // 将返回的数据转换为数组格式
        $data = json_decode(Http::get(self::GetCityData, $params), true);
        return $data;
    }

    /**
     * (6)
     * @methdod get
     * 今日累计开启时长
     * @param $mac
     */
    public static function getDevicesTime($mac, $time = '')
    {
        $listArray = array();
        $params = self::getParamsSix($mac);
        // 将返回的数据转换为数组格式
        $data = json_decode(Http::post(self::GetDevicesTime, $params));
        // 处理数据(type 1开机 2关机)
        foreach ($data as $key => $val) {
            $data[$key] = json_decode($val, true);
        }
        $listArray['hours'] = self::listToDay($data);
        $listArray['day'] = self::listRunDay($data);
        $listArray['allDay'] = self::listAllDay($data);
        $listArray['monthDay'] = self::monthRunDay($data, $time);
        return $listArray;
    }


    /**
     * 今日累计开启时长
     * @param $data
     */
    public static function listToDay($data)
    {
        $type = end($data);
        $type = $type['type'];
        $time = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
        $allTime = '';
        if ($type == 1) {
            //计算开启时间是否是当天,当前是开启状态
            $today = date('Y-m-d', $time);
            if ($today != date('Y-m-d', $today['time'])) {
                // 不是今天开启的(计算从今天开始时间到结束时间)
                $t = time();
                $start_time = mktime(0, 0, 0, date("m", $t), date("d", $t), date("Y", $t));  //当天开始时间
                $allTime = floor((time() - $start_time) % 86400 / 3600);
            }
        } else {
            $time = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
            $today = date('Y-m-d', $time);
            $item = array();
            foreach ($data as $key => $val) {
                if ($today == date('Y-m-d', $val['time'])) {
                    $item[] = $val;
                }
            }
            // 判断最后一个数组是否关闭了
            $type = end($data);
        $type = $type['type'];
            if ($type == 1) {
                // 最后一个加入实时时间
                $num = count($item);
                foreach ($item as $key => $val) {
                    $item[$num]['time'] = time();
                    $item[$num]['type'] = 0;
                }
            }
            // 计算时长
            foreach ($item as $key => $val) {
                if ($data[$key + 1] && $data[$key + 1]['type'] == 1) {
                    $allTime += floor(($val['time'] - $item[$key - 1]['time']) % 86400 / 3600);
                }
            }
        }
        return $allTime;
    }

    /**
     * 判断设备指定月的运行天数
     * @param $data
     * @return bool|float|int|string
     */
    public static function monthRunDay($data, $time)
    {
        // 过滤时间
        $startTime = date('Y-m-01', strtotime($time));
        $endTime = date('Y-m-d', strtotime("$startTime +1 month -1 day"));
        $listData = array();
        $startTimes = strtotime($startTime);
        $endTimes = strtotime($endTime);
        foreach ($data as $s => $v) {
            if ($startTimes <= $v['time'] && $endTimes > $v['time']) {
                $listData[] = $v;
            }
        }
        // 判断最后一个数组里面的数据仪器是否关闭了
        $type = end($data);
        $type = $type['type'];
        if ($type == 1) {
            // 最后一个加入实时时间
            $num = count($listData);
            foreach ($listData as $key => $val) {
                $listData[$num]['time'] = time();
                $listData[$num]['type'] = 0;
            }
        }
        // 计算时长
        $allTime = '';
        foreach ($listData as $key => $val) {
            if ($listData[$key + 1] && $listData[$key + 1]['type'] == 1) {
                $allTime += ($val['time'] - $listData[$key - 1]['time']) / 86400;
            }
        }
        $allTime = substr(sprintf("%.3f", $allTime), 0, -1);
        return $allTime;
    }

    /**
     * 运行天数
     * @param $data
     * @return float|string
     */
    public static function listRunDay($data)
    {
        // 判断最后一个数组里面的数据仪器是否关闭了
        $type = end($data);
        $type = $type['type'];
        if ($type == 1) {
            // 最后一个加入实时时间
            $num = count($data);
            foreach ($data as $key => $val) {
                $data[$num]['time'] = time();
                $data[$num]['type'] = 0;
            }
        }
        // 计算时长
        $allTime = '';
        foreach ($data as $key => $val) {
            if ($data[$key + 1] && $data[$key + 1]['type'] == 1) {
                $allTime += ($val['time'] - $data[$key - 1]['time']) / 86400;
            }
        }
        $allTime = substr(sprintf("%.3f", $allTime), 0, -1);
        return $allTime;
    }

    /**
     * 累计开启天数
     */
    public static function listAllDay($data)
    {
        // 判断最后一个数组里面的数据仪器是否关闭了
        $type = end($data);
        $type = $type['type'];
        if ($type == 1) {
            // 最后一个加入实时时间
            $num = count($data);
            foreach ($data as $key => $val) {
                $data[$num]['time'] = time();
                $data[$num]['type'] = 0;
            }
        }
        foreach ($data as $key => &$val) {
            $val['date'] = date('Y-m-d', $val['time']);
        }
        // 根据天数去重  直接判断最后剩下数组的个数;
        return count(SalesModel::listArrayKey($data, 'date'));

    }


    /**
     * 处理错误信息(1)(2)
     * @param $data
     */
    public static function process($data, $mac, $url, $params, $type)
    {
        $result = array('code' => 1, 'message' => '接口数据获取失败', 'data' => '');
        // 访问了接口
        if ($data['code'] == 200) {
            // 没有设备信息返回
            if (empty($data['devices'])) {
                self::recordLog($mac, $status = 1, $msg = $result['message'], $code = $data['code'], $url, $params, $type);
                return $result;
            } else {
                $result['code'] = 0;
                $result['message'] = '接口数据获取成功';
                $result['data'] = $data;
                return $result;
            }
        }
    }

    /**
     * 处理错误信息(3)
     * @param $data
     */
    public static function processOne($data, $mac, $url, $params, $type)
    {
        $result = array('code' => 1, 'message' => '接口数据获取失败', 'data' => '');
        if ($data['code'] == 200) {
            $result['code'] = 0;
            $result['message'] = '接口数据获取成功';
            $result['data'] = $data;
            return $result;
        } else {
            self::recordLog($mac, $status = 1, $msg = $data['message'], $code = $data['code'], $url, $params, $type);
            $result['message'] = $data['message'];
            return $result;
        }
    }


    /**
     * 记录日志类型
     * @param $data
     */
    public static function recordLog($mac, $status, $msg, $code, $url, $params, $type)
    {
        $data['mac'] = $mac;
        $data['status'] = $status;
        $data['msg'] = $msg;
        $data['code'] = $code;
        $data['url'] = $url;
        $data['params'] = $params;
        $data['type'] = $type;
        $data['addtime'] = time();
        return M(self::TABLENAME_LOG)->add($data);
    }
}