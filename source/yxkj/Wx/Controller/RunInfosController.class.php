<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/9/28
 * Time: 17:07
 */

namespace Wx\Controller;

class RunInfosController extends BaseController
{
    private $user_id;

    /**
     * 运行信息
     */
    public function _initialize()
    {
        $this->user_id = $this->identity()->getUserId();
    }

    /**
     * 获取登录用户对象
     * @return type
     */
    public function identity()
    {
        if (!$this->userObj) {
            $this->userObj = new UserObjModel();
        }
        return $this->userObj;
    }

    /**
     * 运行信息列表
     */
    public function getListInfo()
    {
        $data = RunInfoDataModel::getDevicesInfo($this->user_id);
        $listdatas = RunInfoDataModel::getRunInfoData($this->user_id);
        $this->assign('date', json_encode(i_array_column($listdatas, 'fulldate')));
        $this->assign('rundata', json_encode(i_array_column($listdatas, 'pm25')));
        $this->assign('pm', $data['pm']);
        $this->assign('data', $data['data']);
        $this->assign('hours', $data['allHours']);
        $this->assign('day', $data['day']);
        $this->assign('allday', $data['allDay']);
        $this->display('index');
    }

    /**
     * 单个设备运行情况
     */
    public function getOneDevice()
    {
        $mac = I('get.mac', '', 'trim');
        $data = RunInfoModel::getDevicesTime($mac);
        $newdata = RunInfoModel::getNewDevicesInfo($mac);
        $pm25 = $newdata['data']['devices'][0]['data']['pm25'];
        $datas = RunInfoModel::getDevicesHoures($mac, 24);
        $beginToday = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
        $endToday = mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')) - 1;
        $tongData = $datas['data']['hours'];
        $item = array();
        foreach ($tongData as $key => $val) {
            if ($val['date'] > $beginToday && $val['date'] < $endToday) {
                $item[] = $val;
            }
        }
        $this->assign('pm', $pm25);
        $this->assign('data', $data);
        $this->assign('date', json_encode(i_array_column($item, 'fulldate')));
        $this->assign('pm25', json_encode(i_array_column($item, 'pm25')));
        $this->display('OneDevice');
    }

    /**
     * 某日的运行情况
     */
    public function getOneData()
    {
        $date = I('get.date', '', 'trim');

    }

}