<?php
/**
 * @author: ' Silent
 * @time: 2018/1/17 17:37
 */

namespace Pc\Controller;


use Pc\Model\DeviceRunModel;
use Pc\Model\HotelModel;
use Pc\Model\RunInfoDataModel;
use Pc\Model\SalesModel;
use Pc\Model\UserObjModel;
use Pc\Server\DateType;

class RemovalInfoController extends BaseController
{
    public $model = 'Hotel';
    protected $user_id;
    protected $role_id;

    /**
     * 初始化方法
     * @author ' Silent <1136359934@qq.com>
     */
    public function _initialize()
    {
        parent::_initialize();
        $this->role_id = $this->identity()->getRoleId();
        $this->user_id = $this->identity()->getUserId();
        $hotel = $this->getHotel();
        $this->assign('Hotel', $hotel);
        $this->assign('role_id', $this->role_id);
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
     * 获取用户登录对象
     * @author ' Silent <1136359934@qq.com>
     * @return UserObjModel
     */
    public function identity()
    {
        if (!$this->userObj) {
            $this->userObj = new UserObjModel();
        }
        return $this->userObj;
    }

    /**
     * 获取所有房间净化数据
     */
    public function getHotelRooms()
    {
        $result = array('code' => 1, 'message' => '获取数据失败', 'data' => '');
        $hotel_id = I('post.hotel_id', 0, 'intval');
        if ($hotel_id) {
            $roomData = RunInfoDataModel::getHotelRoom($this->user_id);
            $roomInfo = DeviceRunModel::DeviceRemInfo($roomData);
            $result['code'] = 0;
            $result['message'] = '获取数据成功';
            $result['data'] = $roomInfo;
        }
        $this->ajaxReturn($result);
    }


    /**
     * 首页酒店信息
     * @author ' Silent <1136359934@qq.com>
     */
    public function getListInfo()
    {
        // 归属所属酒店
        $roomdata = RunInfoDataModel::getHotelRoom($this->user_id);
        $roomInfo = DeviceRunModel::DeviceRemInfo($roomdata);
        // 净化效果数据
        $hotel_id = M('User')->where(array('id'=>$this->user_id))->getField('hotel_id');
        $listData = $this->getHotelSevenDays($hotel_id);
        $yznum = 0;
        foreach ($listData as $key => $val){
            if($val['indoor_pm'] > 0 && $val['indoor_pm'] <= 34){
                $yznum++;
            }
        }
        $this->assign('translate',$yznum);
        $this->assign('indoor_pms', DeviceRunModel::DevsRunInPm25($roomdata));
        $this->assign('indoor_pming', testingPm(DeviceRunModel::DevsRunInPm25($roomdata)));
        $this->assign('outdoor_pms', DeviceRunModel::DevsRunOutPm25($roomdata));
        $this->assign('outdoor_pming', testingPm(DeviceRunModel::DevsRunOutPm25($roomdata)));
        $this->assign('numone', json_encode(i_array_column($listData, 'outdoor_pm')));
        $this->assign('numtwo', json_encode(i_array_column($listData, 'indoor_pm')));
        $this->assign('date', json_encode(i_array_column($listData, 'time')));
        $this->assign('roomnums', count($roomInfo));
        $this->assign('roomInfo', $roomInfo);
        $this->display('index');
    }

    /**
     * 选中酒店信息
     * @author ' Silent <1136359934@qq.com>
     */
    public function getHotelInfo()
    {
        $hotel_id = I('post.hotel_id', 0, 'intval');
        $roomdata = RunInfoDataModel::getHotelRooms($hotel_id);
        $roomInfo = DeviceRunModel::DeviceRemInfo($roomdata);
        $hotelName = HotelModel::getHotelNames($this->user_id);
        // 净化效果数据
        $listData = $this->getHotelSevenDays($hotel_id);
        $yznum = 0;
        foreach ($listData as $key => $val){
            if($val['indoor_pm'] > 0 && $val['indoor_pm'] <= 34){
                $yznum++;
            }
        }
        $this->assign('translate',$yznum);
        $this->assign('hotelName', $hotelName);
        $this->assign('id', $hotel_id);
        $this->assign('indoor_pms', DeviceRunModel::DevsRunInPm25($roomdata));
        $this->assign('indoor_pming', testingPm(DeviceRunModel::DevsRunInPm25($roomdata)));
        $this->assign('outdoor_pms', DeviceRunModel::DevsRunOutPm25($roomdata));
        $this->assign('outdoor_pming', testingPm(DeviceRunModel::DevsRunOutPm25($roomdata)));
        $this->assign('numone', json_encode(i_array_column($listData, 'outdoor_pm')));
        $this->assign('numtwo', json_encode(i_array_column($listData, 'indoor_pm')));
        $this->assign('date', json_encode(i_array_column($listData, 'time')));
        $this->assign('roomnums', count($roomInfo));
        $this->assign('roomInfo', $roomInfo);
        $this->display('hotelinfo');
    }

    /**
     * 单个设备点击
     * @author ' Silent <1136359934@qq.com>
     */
    public function clickRoom($mac)
    {

        $hotel_id = M('Device')->where(array('mac' => $mac))->getField('h_id');
        $listData = $this->getDeviceSevenDays($hotel_id, $mac);
        $this->assign('numone', json_encode(i_array_column($listData, 'outdoor_pm')));
        $this->assign('numtwo', json_encode(i_array_column($listData, 'indoor_pm')));
        $this->assign('date', json_encode(i_array_column($listData, 'time')));
        return $this->fetch('OneDevice');
    }

    public function clickRooms()
    {
        $mac = I('post.mac', '', 'trim');
        $result = array('code' => 0, 'message' => '获取房间净化效果失败');
        $res = $this->clickRoom($mac);
        if ($res) {
            $result['code'] = 1;
            $result['message'] = $res;
        }
        $this->ajaxReturn($result);
    }


    /**
     * 获取过去七天的数据(酒店)
     * @author ' Silent <1136359934@qq.com>
     */
    public function getHotelSevenDays($hotel_id = '')
    {
        $listData = DateType::getUpwardDay();
        $redis = new \Redis();
        $redis->connect('127.0.0.1');
        $listAllRun = array();
        foreach ($listData as $key => $value) {
            // 组装0-24时数组
            $dateArray = array(array($value . '/00'), array($value . '/01'), array($value . '/02'), array($value . '/03'), array($value . '/04'), array($value . '/05'), array($value . '/06'), array($value . '/07'), array($value . '/08'), array($value . '/09'), array($value . '/10'), array($value . '/11'), array($value . '/12'), array($value . '/13'), array($value . '/14'), array($value . '/15'), array($value . '/16'), array($value . '/17'), array($value . '/18'), array($value . '/19'), array($value . '/20'), array($value . '/21'), array($value . '/22'), array($value . '/23'));
            $listRun = array();
            foreach ($dateArray as $keys => $vals) {
                $str = $vals[0] . '_' . $hotel_id;
                if ($redis->exists($str)) {
                    $listRun[] = $redis->hGetAll($str);
                }
            }
            $listAllRun[] = $listRun;
        }
        $listAllDatas = array();
        foreach ($listAllRun as $keyone => $valtwo) {
            if (!empty($valtwo)) {
                $alloutDoor = array_sum(i_array_column($valtwo, 'outdoor_pm'));
                $allinDoor = array_sum(i_array_column($valtwo, 'indoor_pm'));
                $allNum = array_sum(i_array_column($valtwo, 'num'));
                $time = explode('/', $valtwo[0]['time']);
                $listAllDatas[$keyone]['time'] = $time[0];
                $listAllDatas[$keyone]['outdoor_pm'] = ceil($alloutDoor / $allNum);
                $listAllDatas[$keyone]['indoor_pm'] = ceil($allinDoor / $allNum);
            }
        }
        $newDatas = DateType::getUpwardDays();
        return arraySequence(SalesModel::listArrayKey(array_merge_recursive((array)$listAllDatas, (array)$newDatas), 'time'), 'time', 'SORT_ASC');
    }

    /**
     * 获取过去七天的数据(设备)
     * @author ' Silent <1136359934@qq.com>
     */
    public
    function getDeviceSevenDays($hotel_id = '', $device = '')
    {
        $listData = DateType::getUpwardDay();
        $redis = new \Redis();
        $redis->connect('127.0.0.1');
        $listAllRun = array();
        foreach ($listData as $key => $value) {
            // 组装0-24时数组
            $dateArray = array(array($value . '/00'), array($value . '/01'), array($value . '/02'), array($value . '/03'), array($value . '/04'), array($value . '/05'), array($value . '/06'), array($value . '/07'), array($value . '/08'), array($value . '/09'), array($value . '/10'), array($value . '/11'), array($value . '/12'), array($value . '/13'), array($value . '/14'), array($value . '/15'), array($value . '/16'), array($value . '/17'), array($value . '/18'), array($value . '/19'), array($value . '/20'), array($value . '/21'), array($value . '/22'), array($value . '/23'));
            $listRun = array();
            foreach ($dateArray as $keys => $vals) {
                $str = $vals[0] . '_' . $hotel_id . '_' . $device;
	if ($redis->exists($str)) {
                    $listRun[] = $redis->hGetAll($str);
                }
            }
            $listAllRun[] = $listRun;
        }
        $listAllDatas = array();
        foreach ($listAllRun as $keyone => $valtwo) {
            if (!empty($valtwo)) {
                $alloutDoor = array_sum(i_array_column($valtwo, 'outdoor_pm'));
                $allinDoor = array_sum(i_array_column($valtwo, 'indoor_pm'));
                $allNum = array_sum(i_array_column($valtwo, 'num'));
                $time = explode('/', $valtwo[0]['time']);
                $listAllDatas[$keyone]['time'] = $time[0];
                $listAllDatas[$keyone]['outdoor_pm'] = ceil($alloutDoor / $allNum);
                $listAllDatas[$keyone]['indoor_pm'] = ceil($allinDoor / $allNum);
            }
        }
        $newDatas = DateType::getUpwardDays();
        return arraySequence(SalesModel::listArrayKey(array_merge_recursive((array)$listAllDatas, (array)$newDatas), 'time'), 'time', 'SORT_ASC');
    }
}
