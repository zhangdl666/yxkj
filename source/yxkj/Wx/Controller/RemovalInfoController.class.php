<?php
/**
 * RemovalInfoController.class.php
 * 净化效果
 * @author: wy901216
 * @date: 2017/9/28  13:40
 */

namespace Wx\Controller;


use Pc\Model\DeviceRunModel;
use Pc\Model\HotelModel;
use Pc\Model\RunInfoModel;
use Pc\Model\SalesModel;
use Pc\Model\UserRoleModel;
use Pc\Server\DateType;
use Wx\Model\RunInfoDataModel;
use Pc\Model\RunInfoDataModel as RunInfoDataModels;
use Think\Controller;
use Wx\Model\UserObjModel;

class RemovalInfoController extends BaseController
{
    /**
     * 初始化
     */
    public function _initialize()
    {
        $hotelData = HotelModel::getHotelName();
        $this->assign('role', $this->identity()->getRoleId());
        $this->assign('hotelData', $hotelData);

        //$this->assign('hotel_id', $hotelData[0]['id']);

        $this->assign('group_name', '/Wx/');
        /*
                //登录微信
                $access_info = session('ACCESS_INFO');
                //微信时间是否过期
                if(empty($access_info['ACCESS_TIME']) || time()-$access_info['ACCESS_TIME'] >= $access_info['EXPIRES_IN']){
                    //获取access_token
                    $access_data = $this->access_token();
                    if($access_data != true){
                        echo $access_data;exit;
                    }
                }

                //用户信息
                $userinfo = session('USERINFO');
                //用户未登录
                if(empty($userinfo)){
                    //去登录
                    header('Location: '.U('Login/index'));
                    exit;
                }
                $this->assign('username',$userinfo['name']);
                $this->assign('userimg',$userinfo['img']);
        */
        $module = M("Role")->field('name,oper_module')->where(array('id' => session('USERINFO.role_id')))->find();
        $module_ids = explode(',', $module['oper_module']);
        $model_left = array();
        if (!empty($module_ids)) {
            $model_left = M("Module")->field('name,module,method')->where(array('parent_id' => 0, 'status' => 1, 'id' => array('in', $module_ids)))->select();
        }

        $this->assign('module', $model_left);
        $this->assign('now_module', CONTROLLER_NAME);

        //当前模块下可操作方法
        if (!empty($module_ids)) {
            $method = M("Module")->field('method')->where(array('module' => CONTROLLER_NAME, 'status' => 1, 'id' => array('in', $module_ids)))->select();
            $method_arr = i_array_column($method, 'method');
            $this->assign('method_arr', $method_arr);
        }
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


    /*
     * 净化效果
     * 酒店总经理、酒店信息维护人员(看到的都是各自酒店的情况)
     * 获取酒店名称、酒店对应的安装设备的房间数、房间号和房间对应的设备编号、室内累计优质天数
     * PM2.5对应的当日室内空气质量均值、当日室外空气质量均值
     * 当前日期之前七日的空气质量走势统计
     ***/
    public function getListInfo()
    {
        $this->common();
        $appid = C('WEIXIN.AppID');
        $appSecret = C('WEIXIN.AppSecret');
        $jssdk = new JSSDK($appid, $appSecret);
        $sign_package = $jssdk->getSignPackage();
        $this->assign($sign_package);
        $hotel_id = session('USERINFO.hotel_id') ? session('USERINFO.hotel_id') : I('get.id');
        if($hotel_id){
           $this->assign('hotelid', $hotel_id); 
        }
        
//        $this->assign('role_ids',$_SESSION['USERINFO']['hotel_id']);
//        $this->assign('role_ids',11);
        $this->display('index');
    }


    /*
     * 净化效果 分享
     ***/
    public function share()
    {
        $this->common();
        $hid = I('id');
        $promotion = M('Promotion')->where(array('h_id' => $hid, 'status' => 1))->field('id,img')
            ->order('ctime desc')->find();
        $this->assign('promotion', $promotion);
        $this->assign('tel', C('TEL'));
        $this->display('share');
    }


    public function common($id)
    {
        //获取酒店对应的信息
        $userinfo = session('USERINFO');
//        $userinfo = M(UserRoleModel::TABLENAME_USER)->where(array('id' => 32))->find();
        if($id){
            $datad = RunInfoDataModel::getHotelInfo('',$id);
        }else{
            $datad = RunInfoDataModel::getHotelInfo($userinfo);
        }

        $this->assign($datad);

        $shareInfo['title'] = '净化效果';
//        $shareInfo['summary'] = $datad['summary'] . '净化效果';
        $shareInfo['web_url'] = WEB_URL;
        $this->assign($shareInfo);
        $listRunDatas = $this->getHotelSevenDays($userinfo['hotel_id']);
        $yznum = 0;
        foreach ($listRunDatas as $key => $val){
            if($val['indoor_pm'] > 0 && $val['indoor_pm'] <= 34){
                $yznum++;
            }
        }
        $this->assign('yznum',$yznum);
        $this->assign('indoor_pms', DeviceRunModel::DevsRunInPm25($datad['rooms']));
        $this->assign('indoor_pming', testingPm(DeviceRunModel::DevsRunInPm25($datad['rooms'])));
        $this->assign('outdoor_pms', DeviceRunModel::DevsRunOutPm25($datad['rooms']));
        $this->assign('outdoor_pming', testingPm(DeviceRunModel::DevsRunOutPm25($datad['rooms'])));
        $this->assign('date', json_encode(i_array_column($listRunDatas, 'time')));
        $this->assign('spm25', json_encode(i_array_column($listRunDatas, 'indoor_pm')));
        $this->assign('sapm25', json_encode(i_array_column($listRunDatas, 'outdoor_pm')));

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


    public function gethotelroom($id)
    {
        $roomData = RunInfoDataModels::getHotelRooms($id);
        $data = DeviceRunModel::DeviceRemInfo($roomData);
        $list['code'] = 1;
        $list['data'] = $data;
        $this->ajaxReturn($list);
    }

    /**
     * 查看某家设备信息
     */
    public function gethotelinfo($id)
    {
        $data = RunInfoDataModel::getHotelInfo($uid = 0, $id);
        $this->assign($data);
        $userinfo = session('USERINFO');
//        $userinfo = M(UserRoleModel::TABLENAME_USER)->where(array('id' => 32))->find();
        if($id){
            $datad = RunInfoDataModel::getHotelInfo('',$id);
        }else{
            $datad = RunInfoDataModel::getHotelInfo($userinfo);
        }
        $shareInfo['title'] = '净化效果';
        $shareInfo['summary'] = $data['summary'] . '净化效果';
        $shareInfo['web_url'] = WEB_URL;
        $this->assign($shareInfo);
        $listRunDatas = $this->getHotelSevenDays($id);
        $yznum = 0;
        foreach ($listRunDatas as $key => $val){
            if($val['indoor_pm'] > 0 && $val['indoor_pm'] <= 34){
                $yznum++;
            }
        }
        $this->assign('yznum',$yznum);
        $this->assign('indoor_pms', DeviceRunModel::DevsRunInPm25($datad['rooms']));
        $this->assign('indoor_pming', testingPm(DeviceRunModel::DevsRunInPm25($datad['rooms'])));
        $this->assign('outdoor_pms', DeviceRunModel::DevsRunOutPm25($datad['rooms']));
        $this->assign('outdoor_pming', testingPm(DeviceRunModel::DevsRunOutPm25($datad['rooms'])));
        $this->assign('date', json_encode(i_array_column($listRunDatas, 'time')));
        $this->assign('spm25', json_encode(i_array_column($listRunDatas, 'indoor_pm')));
        $this->assign('sapm25', json_encode(i_array_column($listRunDatas, 'outdoor_pm')));
        $this->display('hotel');
    }

    /**
     * 单独设备信息
     */
    public function onedevice($device)
    {
        $hotel_id = M('Device')->where(array('mac' => $device))->getField('h_id');
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
        $datas = arraySequence(SalesModel::listArrayKey(array_merge_recursive((array)$listAllDatas, (array)$newDatas), 'time'), 'time', 'SORT_ASC');
        $this->assign('date', json_encode(i_array_column($datas, 'time')));
        $this->assign('spm25', json_encode(i_array_column($datas, 'indoor_pm')));
        $this->assign('sapm25', json_encode(i_array_column($datas, 'outdoor_pm')));
        $res = $this->fetch('OneDevice');
        return $res;
    }

    public function onedevices(){
        $mac = I('post.mac', '', 'trim');
        $result = array('code'=>0,'message'=>'获取房间净化效果失败');

        $res = $this->onedevice($mac);
        if($res){
            $result['code'] = 1;
            $result['message'] = $res;
        }
        $this->ajaxReturn($result);
    }
}