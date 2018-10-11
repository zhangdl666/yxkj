<?php
/**
 * @author: ' Silent
 * @time: 2018/1/17 8:56
 */

namespace Pc\Controller;


use Pc\Model\DeviceRunModel;
use Pc\Model\HotelModel;
use Pc\Model\RunInfoDataModel;
use Pc\Model\RunInfoModel;
use Pc\Model\SalesModel;
use Pc\Model\UserModel;
use Pc\Model\UserObjModel;
use Pc\Server\DateType;
use Pc\Server\Http;

class RunInfoController extends BaseController
{
    private $user_id;
    private $role_id;
    public $model = 'Hotel';

    /**
     * 初始化信息
     * @author ' Silent <1136359934@qq.com>
     */
    public function _initialize()
    {
        $this->user_id = $this->identity()->getUserId();
        $this->role_id = $this->identity()->getRoleId();
        parent::_initialize();
    }

    /**
     * 获取用户登录信息
     * @author ' Silent <1136359934@qq.com>
     */
    public function identity()
    {
        if (!$this->userObj) {
            $this->userObj = new UserObjModel();
        }
        return $this->userObj;
    }

    /**
     * 获取已签约的酒店
     * @author ' Silent <1136359934@qq.com>
     */
    public function getHotel()
    {
        $hotelData = HotelModel::getHotelName();
        return $hotelData;
    }

    /**
     * 运行信息列表
     * role_id(平台总经理,超级管理员可以筛选酒店和日期,酒店工程经理和总经理只能查看当前酒店)
     * @author ' Silent <1136359934@qq.com>
     */
    public function getListInfo()
    {
        switch ($this->role_id) {
            case 1:
                /*$hoteldata = $this->getHotel();
                $this->assign('hoteldata', $hoteldata);
                $this->assign('nowtime', date("Y-m-d"));
                $this->assign('role_ids', $this->role_id);
                break;*/
            case 5:
            case 9:
                $hoteldata = $this->getHotel();
                $this->assign('hoteldata', $hoteldata);
                $this->assign('nowtime', date("Y-m-d"));
                $this->assign('role_ids', $this->role_id);
                break;
            case 11:
                $roomData = RunInfoDataModel::getHotelRoom($this->user_id);     // 获取所有房间信息
                $roomInfo = DeviceRunModel::DevsRunInfos($roomData);            // 获取所有房间实时显示信息
                $hotel_id = M('User')->where(array('id'=>$this->user_id))->getField('hotel_id');
                $allDatas = $this->getHotelAllTimes($hotel_id);  // 获取开启运行时间统计
                $this->assign('datas', $roomInfo);
                $this->assign('hours',$allDatas['allHours']);
                $this->assign('pm',$allDatas['pm']);
                $this->assign('day',ceil($allDatas['day']));
                $this->assign('allday',$allDatas['allDay']);
                $this->assign('hours',$allDatas['allHours']);
                $this->assign('role_ids', $this->role_id);
                break;
            case 12:
                $roomData = RunInfoDataModel::getHotelRoom($this->user_id);     // 获取所有房间信息
                $roomInfo = DeviceRunModel::DevsRunInfos($roomData);            // 获取所有房间实时显示信息
                $hotel_id = M('User')->where(array('id'=>$this->user_id))->getField('hotel_id');
                $allDatas = $this->getHotelAllTimes($hotel_id);  // 获取开启运行时间统计
                $this->assign('datas', $roomInfo);
                $this->assign('hours',$allDatas['allHours']);
                $this->assign('pm',$allDatas['pm']);
                $this->assign('day',ceil($allDatas['day']));
                $this->assign('allday',$allDatas['allDay']);
                $this->assign('hours',$allDatas['allHours']);
                $this->assign('role_ids', $this->role_id);
                break;
        }
        $this->display('index');
    }

    /**
     * 加载酒店房间
     * @author ' Silent <1136359934@qq.com>
     */
    public function getHotelRooms()
    {
        $result = array('code' => 1, 'message' => '获取数据失败', 'data' => '');
        $hotel_id = I('post.hotel_id', 0, 'intval');
        if ($hotel_id) {
            $roomData = RunInfoDataModel::getHotelRooms($hotel_id);
            $roomInfo = DeviceRunModel::DevsRunInfos($roomData);
            $result['code'] = 0;
            $result['message'] = '获取数据成功';
            $result['data'] = $roomInfo;
        }
        $this->ajaxReturn($result);
    }

    /**
     * 筛选酒店
     * @author ' Silent <1136359934@qq.com>
     */
    public function screeningHotel()
    {
        $hotel_id = I('post.hotel_id', 0, 'intval');
        $time = I('post.time');
        $roomdata = RunInfoDataModel::getHotelRooms($hotel_id);
        $listArray = array();
        foreach ($roomdata as $key => $val) {
            $num = ceil(($key + 2) / 100);
            // 产品经理当日下午两点到次日两点
            $dates = $time . ' 14:00:00';
            $beginToday = strtotime($dates);
            $endToday = strtotime(date('Y-m-d H:i:s', strtotime("+1 day", $beginToday)));
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
        if (empty($listAllDatas)) {
            //   // 加载默认图
            $date = json_encode(i_array_column(DateType::getUpwardDate(), 'time'));
            $data = json_encode(i_array_column(DateType::getUpwardDate(), 'pm25s'));
            $rpm25list = json_encode(i_array_column(DateType::getPm25(), 'pm25'));
            $this->assign('rpm25', $rpm25list);
            $this->assign('date', $date);
            $this->assign('num', $data);
        } else {
            $this->assign('date', json_encode(i_array_column($listAllDatas, 'date')));
            $this->assign('num', json_encode(i_array_column($listAllDatas, 'num')));
        }
        $this->display('getHotelechartsData');
    }

    /**
     * 获取开启信息
     * @author ' Silent <1136359934@qq.com>
     */
    public function getopenInfotion($hotel_id, $time = '')
    {
        $roomdata = RunInfoDataModel::getHotelRooms($hotel_id);
        $allhours = '';
        $day = '';
        $allday = '';
        foreach ($roomdata as $key => $val) {
            // 获取显示信息
            $list = RunInfoModel::getDevicesTime($val['equipment_sno']);
            $allhours += $list['hours'];
            $day += $list['day'];
            $allday += $list['allDay'];
        }
        $listData['pm'] = DeviceRunModel::DevsRunInPm25($roomdata);
        $listData['data'] = $roomdata;
        $listData['allHours'] = $allhours;
        $listData['day'] = $day;
        $listData['allDay'] = $allday;
        return $listData;
    }

    /**
     * 获取运行表信息
     * @author ' Silent <1136359934@qq.com>
     * @param $hotel_id
     */
    public function getHotelechartsData($hotel_id, $time = '')
    {
        $listAllDatas = $this->getHotelRunInfo($hotel_id);
        if (empty($listAllDatas)) {
            //   // 加载默认图
            $date = json_encode(i_array_column(DateType::getUpwardDate(), 'time'));
            $data = json_encode(i_array_column(DateType::getUpwardDate(), 'pm25s'));
            $rpm25list = json_encode(i_array_column(DateType::getPm25(), 'pm25'));
            $this->assign('rpm25', $rpm25list);
            $this->assign('date', $date);
            $this->assign('num', $data);
        } else {
            $this->assign('date', json_encode(i_array_column($listAllDatas, 'time')));
            $this->assign('num', json_encode(i_array_column($listAllDatas, 'indoor_pm')));
        }
        $this->display('getHotelechartsData');
    }

    /**
     * 单独设备点击
     * @author ' Silent <1136359934@qq.com>
     * @param $hotel_id
     * @param string $time
     */
    public function getDevicerhartsData($hotel_id = '', $rooms = '', $time = '', $type = '')
    {
        if ($type == 1) {
            if ($hotel_id) {
                // 查询数据库所在条数
                $roomdata = RunInfoDataModel::getHotelRooms($hotel_id);
                $listArray = array();
                foreach ($roomdata as $key => $val) {
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
                if (empty($listAllDatas)) {
                    //   // 加载默认图
                    $date = json_encode(i_array_column(DateType::getUpwardDate(), 'time'));
                    $data = json_encode(i_array_column(DateType::getUpwardDate(), 'pm25s'));
                    $rpm25list = json_encode(i_array_column(DateType::getPm25(), 'pm25'));
                    $this->assign('rpm25', $rpm25list);
                    $this->assign('date', $date);
                    $this->assign('num', $data);
                } else {
                    $this->assign('date', json_encode(i_array_column($listAllDatas, 'date')));
                    $this->assign('num', json_encode(i_array_column($listAllDatas, 'num')));
                }
                $this->display('getHotelechartsData');
            } else {
                if ($rooms) {
                    // 查询数据库所在条数
                    $data = M('OperInfo')->where(array('status' => 1))->field('equipment_sno')->select();
                    foreach ($data as $key => $val) {
                        if ($rooms == $val['equipment_sno']) {
                            $keys = $key + 1;
                            break;
                        }
                    }
                    $num = ceil(($keys + 2) / 100);
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
                    $listdata = arraySequence($datas, 'sort_times', 'SORT_ASC');
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
                    if (empty($listAllDatas)) {
                        //   // 加载默认图
                        $date = json_encode(i_array_column(DateType::getUpwardDate(), 'time'));
                        $data = json_encode(i_array_column(DateType::getUpwardDate(), 'pm25s'));
                        $rpm25list = json_encode(i_array_column(DateType::getPm25(), 'pm25'));
                        $this->assign('rpm25', $rpm25list);
                        $this->assign('date', $date);
                        $this->assign('num', $data);
                    } else {
                        $this->assign('date', json_encode(i_array_column($listAllDatas, 'date')));
                        $this->assign('num', json_encode(i_array_column($listAllDatas, 'num')));
                    }
                    $this->display('getHotelechartsData');
                } else {
                    if ($this->role_id == 11 || $this->role_id == 12) {
                        $hotel_id = M('User')->where(array('id' => $this->user_id))->getField('hotel_id');
                    }
                    $listAllDatas = $this->getHotelTimeRunInfo($time, $hotel_id);
                    if (empty($listAllDatas)) {
                        //   // 加载默认图
                        $date = json_encode(i_array_column(DateType::getUpwardDate(), 'time'));
                        $data = json_encode(i_array_column(DateType::getUpwardDate(), 'pm25s'));
                        $rpm25list = json_encode(i_array_column(DateType::getPm25(), 'pm25'));
                        $this->assign('rpm25', $rpm25list);
                        $this->assign('date', $date);
                        $this->assign('num', $data);
                    } else {
                        $this->assign('date', json_encode(i_array_column($listAllDatas, 'time')));
                        $this->assign('num', json_encode(i_array_column($listAllDatas, 'indoor_pm')));
                    }
                    $this->display('getHotelechartsData');
                }

            }
        } else if ($type == 2) {
            echo 11;
            // 查询数据库所在条数
            $roomdata = RunInfoDataModel::getHotelRooms($hotel_id);
            $listArray = array();
            foreach ($roomdata as $key => $val) {
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
            if (empty($listAllDatas)) {
                //   // 加载默认图
                $date = json_encode(i_array_column(DateType::getUpwardDate(), 'time'));
                $data = json_encode(i_array_column(DateType::getUpwardDate(), 'pm25s'));
                $rpm25list = json_encode(i_array_column(DateType::getPm25(), 'pm25'));
                $this->assign('rpm25', $rpm25list);
                $this->assign('date', $date);
                $this->assign('num', $data);
            } else {
                $this->assign('date', json_encode(i_array_column($listAllDatas, 'date')));
                $this->assign('num', json_encode(i_array_column($listAllDatas, 'num')));
            }
            $this->display('getHotelechartsData');
        } else {
            // 查询数据库所在条数
            $hotel_id = M('Device')->where(array('mac' => $rooms))->getField('h_id');
            if ($time) {
                $listAllDatas = $this->getRoomsTimeRunInfo($time, $hotel_id, $rooms);
            } else {
                $listAllDatas = $this->getDeviceRunInfo($rooms, $hotel_id);
            }
            if (empty($listAllDatas)) {
                //   // 加载默认图
                $date = json_encode(i_array_column(DateType::getUpwardDate(), 'time'));
                $data = json_encode(i_array_column(DateType::getUpwardDate(), 'pm25s'));
                $rpm25list = json_encode(i_array_column(DateType::getPm25(), 'pm25'));
                $this->assign('rpm25', $rpm25list);
                $this->assign('date', $date);
                $this->assign('num', $data);
            } else {
                $this->assign('date', json_encode(i_array_column($listAllDatas, 'time')));
                $this->assign('num', json_encode(i_array_column($listAllDatas, 'indoor_pm')));
            }
            $this->display('getHotelechartsData');
        }
    }

    /**
     * 加载默认图表
     * @author ' Silent <1136359934@qq.com>
     */
    public function defaultChart($time = '')
    {
        if ($this->role_id == 11 || $this->role_id == 12) {
            $hotel_id = M('User')->where(array('id' => $this->user_id))->getField('hotel_id');
            $listAllDatas = $this->getHotelRunInfo($hotel_id);
            if (empty($listAllDatas)) {
                //   // 加载默认图
                $date = json_encode(i_array_column(DateType::getUpwardDate(), 'time'));
                $data = json_encode(i_array_column(DateType::getUpwardDate(), 'pm25s'));
                $rpm25list = json_encode(i_array_column(DateType::getPm25(), 'pm25'));
                $this->assign('rpm25', $rpm25list);
                $this->assign('date', $date);
                $this->assign('num', $data);
            } else {
                $this->assign('date', json_encode(i_array_column($listAllDatas, 'time')));
                $this->assign('num', json_encode(i_array_column($listAllDatas, 'indoor_pm')));
            }
            $this->display('getHotelechartsData');
        } else {
            $date = json_encode(i_array_column(DateType::getUpwardDate(), 'time'));
            $data = json_encode(i_array_column(DateType::getUpwardDate(), 'pm25s'));
            $rpm25list = json_encode(i_array_column(DateType::getPm25(), 'pm25'));
            $this->assign('rpm25', $rpm25list);
            $this->assign('date', $date);
            $this->assign('num', $data);
            $this->display('getHotelechartsData');
        }
    }

    /**
     * 获取运行时间
     * @author ' Silent <1136359934@qq.com>
     * @param $type
     * @param string $mac
     */
    public function getDeviceTimes($type, $rooms = '', $hotel_id = '')
    {
        if ($type == 1) {
            $roomdata = RunInfoDataModel::getHotelRooms($hotel_id);
            $data = $this->getHotelAllTimes($hotel_id);  // 获取开启运行时间统计
            $data['hours'] = $data['allHours'];
            $data['hours'] = $data['allHours'];
            $data['allDay'] = $data['allDay'];
            $data['day'] = ceil($data['day']);
            $pm = DeviceRunModel::DevsRunInPm25($roomdata);
        } else {
            $data = RunInfoModel::getDevicesTime($rooms);
            $pm = DeviceRunModel::OneDevices($rooms);
            if ($pm == -1) {
                $pm = 0;
            }
        }
        $this->assign('pm', $pm);
        $this->assign('data', $data);
        $this->display('deviceTime');
    }

    /**
     * curl模块访问的总运行时间
     * @author ' Silent <1136359934@qq.com>
     */
    public function getCurlHotelId()
    {
        // 查询出所有酒店和名称,统计运行总时间
        $data = M('Device')->where(array('h_id' => array('neq', '')))->field('mac,h_id')->select();
        $num = SalesModel::listArrayKey($data, 'h_id');
        foreach ($num as $key => $val) {
            $data = RunInfoDataModel::getDevicesInfoTime($val['h_id']);
            $redis = new \Redis();
            $redis->connect('127.0.0.1');
            //拼装主键
            $str = 'tiems_' . $val['h_id'];
            $times = array('pm' => $data['pm'], 'allHours' => $data['allHours'], 'day' => $data['day'], 'allDay' => $data['allDay'], 'hotel_id' => $val['h_id']);
            if ($redis->exists($str)) {
                $redis->delete($str);
                // 重新缓存
                $redis->hMset($str, $times);
            } else {
                $redis->hMset($str, $times);
            }
        }
    }

    /**
     * 获取酒店总运行时间
     * @author ' Silent <1136359934@qq.com>
     */
    public function getHotelAllTimes($hotel_id)
    {
        $redis = new \Redis();
        $redis->connect('127.0.0.1');
        return $redis->hGetAll('tiems_' . $hotel_id);
    }

    /**
     * 获取某家酒店总运行信息
     * @author ' Silent <1136359934@qq.com>
     */
    public function getHotelRunInfo($hotel_id)
    {
        $redis = new \Redis();
        $redis->connect('127.0.0.1');
        // 循环生成键值,查询
        $beginTodays = mktime(14, 0, 0, date('m'), date('d'), date('Y'));
        if (time() < $beginTodays) {
            // 获取昨天14点时间和现在时间
            $beginToday = mktime(14, 0, 0, date('m'), date('d'), date('Y'));
            $beginToday = strtotime(date('Y-m-d H:i:s', strtotime("-1 day", $beginToday)));
            $timeone = date('Y-m-d', $beginToday);
            $timetwo = date('Y-m-d', time());
            $listArray = array(
                array($timeone . '/14'), array($timeone . '/15'), array($timeone . '/16'), array($timeone . '/17'), array($timeone . '/18'), array($timeone . '/19'), array($timeone . '/20'), array($timeone . '/21'), array($timeone . '/22'), array($timeone . '/23'), array($timetwo . '/00'), array($timetwo . '/01'), array($timetwo . '/02'), array($timetwo . '/03'), array($timetwo . '/04'), array($timetwo . '/05'), array($timetwo . '/06'), array($timetwo . '/07'), array($timetwo . '/08'), array($timetwo . '/09'), array($timetwo . '/10'), array($timetwo . '/11'), array($timetwo . '/12'), array($timetwo . '/13'),
            );
            // 获取酒店的数据
            $listRun = array();
            foreach ($listArray as $key => $val) {
                $str = $val[0] . '_' .$hotel_id;
                if ($redis->exists($str)) {
                    $data = $redis->hGetAll($str);
                    $listRun[$key]['indoor_pm'] = ceil($data['indoor_pm'] / $data['num']);
                    $listRun[$key]['outdoor_pm'] = ceil($data['outdoor_pm'] / $data['num']);
                    $listRun[$key]['time'] = $val[0];
                } else {
                    $listRun[$key]['indoor_pm'] = 0;
                    $listRun[$key]['outdoor_pm'] = 0;
                    $listRun[$key]['time'] = $val[0];
                }
            }
            return $listRun;
        } else {
            // 获取今天14点时间到明天时间
            $beginToday = mktime(14, 0, 0, date('m'), date('d'), date('Y'));
            $timeone = date('Y-m-d', $beginToday);
            $endToday = strtotime(date('Y-m-d H:i:s', strtotime("+1 day", $beginToday)));
            $timetwo = date('Y-m-d', $endToday);
            $listArray = array(
                array($timeone . '/14'), array($timeone . '/15'), array($timeone . '/16'), array($timeone . '/17'), array($timeone . '/18'), array($timeone . '/19'), array($timeone . '/20'), array($timeone . '/21'), array($timeone . '/22'), array($timeone . '/23'), array($timetwo . '/00'), array($timetwo . '/01'), array($timetwo . '/02'), array($timetwo . '/03'), array($timetwo . '/04'), array($timetwo . '/05'), array($timetwo . '/06'), array($timetwo . '/07'), array($timetwo . '/08'), array($timetwo . '/09'), array($timetwo . '/10'), array($timetwo . '/11'), array($timetwo . '/12'), array($timetwo . '/13'),
            );
            // 获取酒店的数据
            $listRun = array();
            foreach ($listArray as $key => $val) {
                $str = $val[0] . '_' . $hotel_id;
                if ($redis->exists($str)) {
                    $data = $redis->hGetAll($str);
                    $listRun[$key]['indoor_pm'] = ceil($data['indoor_pm'] / $data['num']);
                    $listRun[$key]['outdoor_pm'] = ceil($data['outdoor_pm'] / $data['num']);
                    $listRun[$key]['time'] = $val[0];
                } else {
                    $listRun[$key]['indoor_pm'] = 0;
                    $listRun[$key]['outdoor_pm'] = 0;
                    $listRun[$key]['time'] = $val[0];
                }
            }
            return $listRun;
        }
    }

    /**
     * 获取某家酒店指定时间运行信息
     * @author ' Silent <1136359934@qq.com>
     */
    public function getHotelTimeRunInfo($time, $hotel)
    {
        $redis = new \Redis();
        $redis->connect('127.0.0.1');
        $dates = $time . ' 14:00:00';
        $beginToday = strtotime($dates);
        if(time() < $beginToday){
            $beginToday = strtotime(date('Y-m-d H:i:s', strtotime("-1 day", $beginToday)));
            $timeone = date('Y-m-d', $beginToday);
            $timetwo = date('Y-m-d', time());
        }else{
            $timeone = date('Y-m-d', $beginToday);
            $endToday = strtotime(date('Y-m-d H:i:s', strtotime("+1 day", $beginToday)));
            $timetwo = date('Y-m-d', $endToday);
        }
        $listArray = array(
            array($timeone . '/14'), array($timeone . '/15'), array($timeone . '/16'), array($timeone . '/17'), array($timeone . '/18'), array($timeone . '/19'), array($timeone . '/20'), array($timeone . '/21'), array($timeone . '/22'), array($timeone . '/23'), array($timetwo . '/00'), array($timetwo . '/01'), array($timetwo . '/02'), array($timetwo . '/03'), array($timetwo . '/04'), array($timetwo . '/05'), array($timetwo . '/06'), array($timetwo . '/07'), array($timetwo . '/08'), array($timetwo . '/09'), array($timetwo . '/10'), array($timetwo . '/11'), array($timetwo . '/12'), array($timetwo . '/13'),
        );
        // 获取酒店的数据
        $listRun = array();
        foreach ($listArray as $key => $val) {
            $str = $val[0] . '_' . $hotel;
            if ($redis->exists($str)) {
                $data = $redis->hGetAll($str);
                $listRun[$key]['indoor_pm'] = ceil($data['indoor_pm'] / $data['num']);
                $listRun[$key]['outdoor_pm'] = ceil($data['outdoor_pm'] / $data['num']);
                $listRun[$key]['time'] = $val[0];
            } else {
                $listRun[$key]['indoor_pm'] = 0;
                $listRun[$key]['outdoor_pm'] = 0;
                $listRun[$key]['time'] = $val[0];
            }
        }
        return $listRun;
    }

    /**
     * 获取某个设备指定时间运行信息
     * @author ' Silent <1136359934@qq.com>
     */
    public function getRoomsTimeRunInfo($time, $hotel, $rooms)
    {
        $redis = new \Redis();
        $redis->connect('127.0.0.1');
        $dates = $time . ' 14:00:00';
        $beginToday = strtotime($dates);
        if(time() < $beginToday){
            $beginToday = strtotime(date('Y-m-d H:i:s', strtotime("-1 day", $beginToday)));
            $timeone = date('Y-m-d', $beginToday);
            $timetwo = date('Y-m-d', time());
        }else{
            $timeone = date('Y-m-d', $beginToday);
            $endToday = strtotime(date('Y-m-d H:i:s', strtotime("+1 day", $beginToday)));
            $timetwo = date('Y-m-d', $endToday);
        }
        $listArray = array(
            array($timeone . '/14'), array($timeone . '/15'), array($timeone . '/16'), array($timeone . '/17'), array($timeone . '/18'), array($timeone . '/19'), array($timeone . '/20'), array($timeone . '/21'), array($timeone . '/22'), array($timeone . '/23'), array($timetwo . '/00'), array($timetwo . '/01'), array($timetwo . '/02'), array($timetwo . '/03'), array($timetwo . '/04'), array($timetwo . '/05'), array($timetwo . '/06'), array($timetwo . '/07'), array($timetwo . '/08'), array($timetwo . '/09'), array($timetwo . '/10'), array($timetwo . '/11'), array($timetwo . '/12'), array($timetwo . '/13'),
        );
        // 获取酒店的数据
        $listRun = array();
        foreach ($listArray as $key => $val) {
            $str = $val[0] . '_' . $hotel . '_' . $rooms;
            if ($redis->exists($str)) {
                $data = $redis->hGetAll($str);
                $listRun[$key]['indoor_pm'] = ceil($data['indoor_pm'] / $data['num']);
                $listRun[$key]['outdoor_pm'] = ceil($data['outdoor_pm'] / $data['num']);
                $listRun[$key]['time'] = $val[0];
            } else {
                $listRun[$key]['indoor_pm'] = 0;
                $listRun[$key]['outdoor_pm'] = 0;
                $listRun[$key]['time'] = $val[0];
            }
        }
        return $listRun;

    }


    /**
     * 获取某家酒店设备运行信息
     * @author ' Silent <1136359934@qq.com>
     */
    public function getDeviceRunInfo($rooms, $hotel_id)
    {
        $redis = new \Redis();
        $redis->connect('127.0.0.1');
        // 循环生成键值,查询
        $beginTodays = mktime(14, 0, 0, date('m'), date('d'), date('Y'));
        if (time() < $beginTodays) {
            // 获取昨天14点时间和现在时间
            $beginToday = mktime(14, 0, 0, date('m'), date('d'), date('Y'));
            $beginToday = strtotime(date('Y-m-d H:i:s', strtotime("-1 day", $beginToday)));
            $timeone = date('Y-m-d', $beginToday);
            $timetwo = date('Y-m-d', time());
            $listArray = array(
                array($timeone . '/14'), array($timeone . '/15'), array($timeone . '/16'), array($timeone . '/17'), array($timeone . '/18'), array($timeone . '/19'), array($timeone . '/20'), array($timeone . '/21'), array($timeone . '/22'), array($timeone . '/23'), array($timetwo . '/00'), array($timetwo . '/01'), array($timetwo . '/02'), array($timetwo . '/03'), array($timetwo . '/04'), array($timetwo . '/05'), array($timetwo . '/06'), array($timetwo . '/07'), array($timetwo . '/08'), array($timetwo . '/09'), array($timetwo . '/10'), array($timetwo . '/11'), array($timetwo . '/12'), array($timetwo . '/13'),
            );
            // 获取酒店的数据
            $listRun = array();
            $mac = $rooms;
            foreach ($listArray as $key => $val) {
                $str = $val[0] . '_'.$hotel_id.'_' . $mac;
                if ($redis->exists($str)) {
                    $data = $redis->hGetAll($str);
                    $listRun[$key]['indoor_pm'] = ceil($data['indoor_pm'] / $data['num']);
                    $listRun[$key]['outdoor_pm'] = ceil($data['outdoor_pm'] / $data['num']);
                    $listRun[$key]['time'] = $val[0];
                } else {
                    $listRun[$key]['indoor_pm'] = 0;
                    $listRun[$key]['outdoor_pm'] = 0;
                    $listRun[$key]['time'] = $val[0];
                }
            }
            return $listRun;
        } else {
            // 获取今天14点时间到明天时间
            $beginToday = mktime(14, 0, 0, date('m'), date('d'), date('Y'));
            $timeone = date('Y-m-d', $beginToday);
            $endToday = strtotime(date('Y-m-d H:i:s', strtotime("+1 day", $beginToday)));
            $timetwo = date('Y-m-d', $endToday);
            $listArray = array(
                array($timeone . '/14'), array($timeone . '/15'), array($timeone . '/16'), array($timeone . '/17'), array($timeone . '/18'), array($timeone . '/19'), array($timeone . '/20'), array($timeone . '/21'), array($timeone . '/22'), array($timeone . '/23'), array($timetwo . '/00'), array($timetwo . '/01'), array($timetwo . '/02'), array($timetwo . '/03'), array($timetwo . '/04'), array($timetwo . '/05'), array($timetwo . '/06'), array($timetwo . '/07'), array($timetwo . '/08'), array($timetwo . '/09'), array($timetwo . '/10'), array($timetwo . '/11'), array($timetwo . '/12'), array($timetwo . '/13'),
            );
            // 获取酒店的数据
            $listRun = array();
            $mac = $rooms;
            foreach ($listArray as $key => $val) {
                $str = $val[0] . '_' . $hotel_id . '_' . $mac;
                if ($redis->exists($str)) {
                    $data = $redis->hGetAll($str);
                    $listRun[$key]['indoor_pm'] = ceil($data['indoor_pm'] / $data['num']);
                    $listRun[$key]['outdoor_pm'] = ceil($data['outdoor_pm'] / $data['num']);
                    $listRun[$key]['time'] = $val[0];
                } else {
                    $listRun[$key]['indoor_pm'] = 0;
                    $listRun[$key]['outdoor_pm'] = 0;
                    $listRun[$key]['time'] = $val[0];
                }
            }
            return $listRun;
        }
    }

}
