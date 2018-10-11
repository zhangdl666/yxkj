<?php
/**
 * Author: ' Silent
 * Time: 2018/1/11 14:54
 */

namespace Pc\Model;


use Think\Model;

class DeviceRunModel extends Model
{
    const STATUS_ONE = 'green';  // 运行正常
    const STATUS_TWO = '';  // 暂无开启
    const STATUS_THREE = 'red'; // 超标预警
    const STATUS_FOUR = 'orange'; // 离线预警

    const STATE_ONE = 'green'; // 优
    const STATE_TWO = 'orange'; // 良
    const STATE_THE = 'red'; //差
    const STATE_FOU = 'gray'; //离线
    const STATE_FIV = 'black'; //关闭
    //const STATE_FOU = ''; //设备未检测到

    /**
     * 设备最新信息
     * @param $data
     */
    public static function DevsRunInfos($data)
    {
        $str = self::DeviceString($data);
        $Infos = RunInfoModel::getMultipleInfo($str);
        $listArray = array();
        foreach ($Infos['设备数据'] as $key => $val) {
            $listArray[$key]['room_sno'] = $data[$key]['room_sno'];
            $listArray[$key]['equipment_sno'] = $val['设备地址'];
            $devtime = strtr($val['上传时间'], array(".0" => ''));
            $time = date('Y-m-d', strtotime($devtime));
            if ($time == date('Y-m-d', time())) {
                if ($val['online'] == 1 && $val['power'] == 1) {
                    // 根据设备最新更新的信息获取pm25,判断房间是否超标
                    $num = testingPm($val['检测PM2.5']);
                    if ($num == 3) {
                        $listArray[$key]['status'] = self::STATUS_THREE;
                    } else {
                        $listArray[$key]['status'] = self::STATUS_ONE;
                    }
                    // 在线就是在线了
                } else if ($val['online'] == 1 && $val['power'] == 0) {
                    $listArray[$key]['status'] = self::STATUS_ONE;
                } else if ($val['online'] == 0 && $val['power'] == 1) {
                    $listArray[$key]['status'] = self::STATUS_FOUR;
                } else if ($val['online'] == 0 && $val['power'] == 0) {
                    $listArray[$key]['status'] = self::STATUS_TWO;
                }
            } else {
                $listArray[$key]['status'] = self::STATUS_TWO;
            }
        }
        return $listArray;
    }

    /**
     * 设备最新信息
     * @param $data
     */
    public static function DevsRunInPm25($data)
    {
        $str = self::DeviceString($data);
        $Infos = RunInfoModel::getMultipleInfo($str);
        $num = 0;
        $pm25 = 0;
        foreach ($Infos['设备数据'] as $key => $val) {
            $listArray[$key]['equipment_sno'] = $val['设备地址'];
            $devtime = strtr($val['上传时间'], array(".0" => ''));
            $time = date('Y-m-d', strtotime($devtime));
            if ($time == date('Y-m-d', time())) {
                if ($val['online'] == 1 && $val['power'] == 1) {
                    $num++;
                    $pm25 += $val['检测PM2.5'];
                }
            }
        }
        return round($pm25 / $num);
    }

    /**
     * 室外pm2.5
     * @author ' Silent <1136359934@qq.com>
     * @param $data
     * @return float|int
     */
    public static function DevsRunOutPm25($data)
    {
        $str = self::DeviceString($data);
        $Infos = RunInfoModel::getMultipleInfo($str);
        $num = 0;
        $pm25 = 0;
        foreach ($Infos['设备数据'] as $key => $val) {
            $listArray[$key]['equipment_sno'] = $val['设备地址'];
            $devtime = strtr($val['上传时间'], array(".0" => ''));
            $time = date('Y-m-d', strtotime($devtime));
            if ($time == date('Y-m-d', time())) {
                if ($val['online'] == 1 && $val['power'] == 1) {
                    $num++;
                    $pm25 += $val['室外PM2.5'];
                }
            }
        }
        return round($pm25 / $num);
    }


    public static function DevsRunInop($data)
    {
        $str = self::DeviceString($data);
        $Infos = RunInfoModel::getMultipleInfo($str);
        $num = 0;
        $pm25 = 0;
        foreach ($Infos['设备数据'] as $key => $val) {
            $listArray[$key]['equipment_sno'] = $val['设备地址'];
            $devtime = strtr($val['上传时间'], array(".0" => ''));
            $time = date('Y-m-d', strtotime($devtime));
            if ($time == date('Y-m-d', time())) {
                if ($val['online'] == 1 && $val['power'] == 1) {
                    $num++;
                    $pm25 += $val['检测PM2.5'];
                }
            }
        }
        return $pm25 / $num;
    }

    public static function DeviceRemInfo($data)
    {
        $str = self::DeviceString($data);
        $Infos = RunInfoModel::getMultipleInfo($str);
        //dump($Infos);exit;
        $listArray = array();
        foreach ($Infos['设备数据'] as $key => $val) {
            $listArray[$key]['room_sno'] = $data[$key]['room_sno'];
            $listArray[$key]['equipment_sno'] = $val['设备地址'];
            $devtime = strtr($val['上传时间'], array(".0" => ''));
            $time = date('Y-m-d', strtotime($devtime));
//            if ($time == date('Y-m-d', time())) {
                // online 在线状态 1=在线 0=离线
                // power 开机状态 1=开机 0=关机
                // 只要是离线的，就显示离线，
                // 只有在线，关闭状态  就显示关闭的

                if ($val['online'] == 1 && $val['power'] == 1) {
                    // 成功获取到该设备的最后一次信息
                    $pm25 = $val['检测PM2.5'];
                    if ($pm25 == 0) {
                        $num = 1;
                    } else {
                        $num = testingPm($pm25);
                    }
                    if ($num == 1) {
                        $listArray[$key]['status'] = self::STATE_ONE;
                    } else if ($num == 2) {
                        $listArray[$key]['status'] = self::STATE_TWO;
                    } else if ($num == 3) {
                        $listArray[$key]['status'] = self::STATE_THE;
                    }
//                } else if ($val['online'] == 1 && $val['power'] == 0) {
//                    // 深灰色
//                    $listArray[$key]['status'] = self::STATE_FOU;
//                } else if ($val['online'] == 0 && $val['power'] == 1) {
//                    $listArray[$key]['status'] = self::STATE_FOU;
//                } else if ($val['online'] == 0 && $val['power'] == 0) {
//                    $listArray[$key]['status'] = self::STATE_FIV;
                }else if($val['online'] == 1 && $val['power'] == 0){
                    $listArray[$key]['status'] = self::STATE_FIV;
                }else if($val['online'] == 0){
                    $listArray[$key]['status'] = self::STATE_FOU;
                }
//            } else {
//                $listArray[$key]['status'] = self::STATE_FOU;
//            }
        }
        return $listArray;
    }

    /**
     * 获取当个设备的状态
     */
    public static function OneDevices($mac)
    {
        $data = array(array('equipment_sno' => $mac));
        $strs = self::DeviceString($data);
        $Infos = RunInfoModel::getMultipleInfo($strs);
        // 检测时间,控制到五十秒内
        $devtime = strtotime(strtr($Infos['设备数据'][0]['上传时间'], array(".0" => '')));
        if (time() - $devtime > 30) {
            return -1;
        } else {
            return $Infos['设备数据'][0]['检测PM2.5'];
        }

    }

    /**
     * 将设备字符串转成字符串
     * @param $data
     */
    public static function DeviceString($data)
    {
        $str = '';
        foreach ($data as $key => $val) {
            if ($key >= 1) {
                $str .= '|' . $val['equipment_sno'];
            } else {
                $str .= $val['equipment_sno'];
            }
        }
        return $str;
    }
}