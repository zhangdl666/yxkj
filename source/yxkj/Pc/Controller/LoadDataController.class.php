<?php
/**
 * Author: ' Silent
 * Time: 2017/11/2 17:45
 */

namespace Pc\Controller;

use Pc\Model\RunInfoModel;
use Pc\Server\Http;
use Think\Controller;

/**
 * 拉取数据保存至数据库
 * Class LoadDataController
 * @package Pc\Controller
 */
class LoadDataController extends Controller
{
    /**
     * 获取设备列表
     * @return mixed
     */
    public function getData()
    {
        return M('Device')->field('mac')->select();
    }

    /**
     * 运行时间
     */
    public function loadrunTiem()
    {
        $macdata = $this->getData();
        $data = array();
        foreach ($macdata as $key => $val) {
            $params = RunInfoModel::getParamsSix($val['mac']);
            $data[$key]['mac'] = $val['mac'];
            $data[$key]['runtime'] = Http::post(RunInfoModel::GetDevicesTime, $params);
        }
        // 处理数据
        foreach ($data as $key => $val) {
            // 查询数据库是否存在
            $result = M('DeviceInfo')->where(array('mac' => $val['mac']))->find();
            if ($result) {
                // 修改操作
                M('DeviceInfo')->where(array('mac' => $val['mac']))->setField(array('runtime' => $val['runtime']));
            } else {
                // 增加操作
                M('DeviceInfo')->add($val);
            }
        }
    }

    /**
     * 运行信息
     */
    public function loadrunData()
    {
        $macdata = $this->getData();
        $data = array();
        foreach ($macdata as $key => $val) {
            $params = RunInfoModel::getParamsthe($val['mac'], 0);
            $data[$key]['mac'] = $val['mac'];
            $data[$key]['rundata'] = Http::get(RunInfoModel::GetDeviceHoures, $params);
        }
        // 处理数据
        foreach ($data as $key => $val) {
            // 查询数据库是否存在
            $result = M('DeviceInfo')->where(array('mac' => $val['mac']))->find();
            if ($result) {
                // 修改操作
                M('DeviceInfo')->where(array('mac' => $val['mac']))->setField(array('rundata' => $val['rundata']));
            } else {
                // 增加操作
                M('DeviceInfo')->add($val);
            }
        }
    }

    /**
     * 以天为单位的运行信息
     */
    public function loadrunDayData()
    {
        $macdata = $this->getData();
        $data = array();
        foreach ($macdata as $key => $val) {
            $params = RunInfoModel::getParamsTwo($val['mac'], 0);
            $data[$key]['mac'] = $val['mac'];
            $data[$key]['rundaydata'] = Http::get(RunInfoModel::GetDeviceDay, $params);
        }
        // 处理数据
        foreach ($data as $key => $val) {
            // 查询数据库是否存在
            $result = M('DeviceInfo')->where(array('mac' => $val['mac']))->find();
            if ($result) {
                // 修改操作
                M('DeviceInfo')->where(array('mac' => $val['mac']))->setField(array('rundaydata' => $val['rundaydata']));
            } else {
                // 增加操作
                M('DeviceInfo')->add($val);
            }
        }
    }
}