<?php
/**
 * Author: ' Silent
 * Time: 2017/9/13 17:10
 */

namespace Pc\Server;

/**
 * 计算日期API
 * Class DateTypeModel
 * @package Core\Model\User
 */
class DateType
{

    /**
     * 获取时间(1.工作日,2.非工作日,3.全部)
     * @param $startTime
     * @param $endTime
     * @param $type
     */
    public static function listDate($startTime, $endTime, $type)
    {
        $data = '';
        /** 判断类型 */
        switch ($type) {
            case '1' :
                $data = self::workingDay($startTime, $endTime);
                break;
            case '2' :
                $data = self::restDay($startTime, $endTime);
                break;
            case '3' :
                $data = self::allDay($startTime, $endTime);
                break;
        }
        return $data;
    }

    /**
     * 工作日
     * @param $startTime
     * @param $endTime
     */
    public static function workingDay($startTime, $endTime)
    {
        /** 转换时间戳(计算一段时间) */
        $startDate = strtotime($startTime);
        $endDate = strtotime($endTime);
        $allDate = [];
        for ($i = $startDate; $i <= $endDate; $i += 86400) {
            if ((date('w', $i) == 6) || (date('w', $i) == 0)) {
                /** 不做处理 */
            } else {
                $allDate[] = date("Y-m-d", $i);
            }
        }
        return $allDate;
    }

    /**
     * 非工作日
     * @param $startTime
     * @param $endTime
     */
    public static function restDay($startTime, $endTime)
    {
        /** 转换时间戳(计算一段时间) */
        $startDate = strtotime($startTime);
        $endDate = strtotime($endTime);
        $allDate = [];
        for ($i = $startDate; $i <= $endDate; $i += 86400) {
            /** 返回当天的星期;数字0表示是星期天,数字123456表示星期1到6 */
            if ((date('w', $i) == 6) || (date('w', $i) == 0)) {
                $allDate[] = date("Y-m-d", $i);
            } else {
                /** 不做处理 */
            }
        }
        return $allDate;

    }

    /**
     * 全部
     * @param $startTime
     * @param $endTime
     */
    public static function allDay($startTime, $endTime)
    {
        /** 转换时间戳(计算一段时间) */
        $startDate = strtotime($startTime);
        $endDate = strtotime($endTime);
        $allDate = [];
        for ($i = $startDate; $i <= $endDate; $i += 86400) {
            $allDate[] = date("Y-m-d", $i);
        }
        return $allDate;
    }

    /**
     * 获取自今天往前推算7个工作日。
     */
    public static function getUpwardDay()
    {
        $allDate = [];
        for ($i = 0; $i < 7; $i++) {
            $allDate[] = date('Y-m-d', strtotime('-' . $i . ' day'));
        }
        return $allDate;
    }

    /**
     * 获取自今天往前推算7个工作日。
     */
    public static function getUpwardDays()
    {
        $allDate = [];
        for ($i = 0; $i < 7; $i++) {
            $allDate[$i]['time'] = date('Y-m-d', strtotime('-' . $i . ' day'));
            $allDate[$i]['outdoor_pm'] = 0;
            $allDate[$i]['indoor_pm'] = 0;
        }
        return $allDate;
    }

    /**
     * 获取今天时间段
     * @return array
     */
    public static function getUpwardDate()
    {
        $allDate = [];
        $beginToday = mktime(23, 0, 0, date('m'), date('d'), date('Y'));
        for ($i = 0; $i < 24; $i++) {
            $allDate[]['time'] = sprintf("%s:00", date('H', strtotime('-' . $i . 'hours', $beginToday)));
        }
        return arraySequence($allDate, 'time', 'SORT_ASC');
    }


    public static function getPm25()
    {
        $allDate = [];
        for ($i = 0; $i < 100; $i++) {
            if (is_int($i / 30)) {
                $allDate[]['pm25'] = $i;
            }
        }
        return $allDate;
    }
}