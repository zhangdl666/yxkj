<?php
/**
 * Created by ${CONTROLLER_NAME}.
 * @auther: 刘小伟
 * Date: 2017/9/29
 * Time: 17:04
 */
namespace Traveller\Controller;
use Traveller\Model\CheckinInfoModel;
use Traveller\Model\BlogModel;
use Pc\Server\DateType;

class CheckinInfoController extends BaseController{
    /**
     * 接口地址
     */
    const Token = '523c374d-fa46-d6a7-a068-d80d88612dfc';  // 开发者公司的apiToken

    // 2.0接口(启用的是2.0接口)
    const GetDeviceInfo = "http://api.bjhike.com/api/v2_deviceInfo"; // 获取设备信息(传入错误的mac地址,也会返回200,但是devices与message是为空的)
    const getDeviceNewInfo = "http://api.bjhike.com/api/v2_deviceData"; // 设备最新一次状态
    const gethoure ="http://api.bjhike.com/api/v2_hours";//返回以小时为单位的设备状态
    //const Relieve ="http://api.bjhike.com/api/v2_unbind";//删除用户与设备的绑定关系
    const getuserMation ="http://api.bjhike.com/DeviceUsers";//返回用户与设备的关系
    const inTemp ="http://api.bjhike.com/api/v2_ac_control";//温度控制接口
    const postSpeed = "http://api.bjhike.com/api/v2_speed"; // 修改风速数据接口
    const Postoper = "http://api.bjhike.com/api/v2_power"; // 修改开关接口
    const PostTime = "http://api.bjhike.com/api/v2_time"; // 设备定时接口
    const  DeviceData_Batch = "http://api.bjhike.com/DeviceData_Batch"; // 设备定时接口
    //首页数据分配
    public function index()
    {
        $id = $_SESSION['TRAVEID'];
        if(empty($id)){
            $id = I('get.id');
            session('TRAVEID',$id);
        }
        $openid=session('TRAVELLEROPENID');
        /*
         * $openid ="o6jtgw9cbxTDc6Ew71oluwudgi7E";
        session('TRAVELLEROPENID',"o6jtgw9cbxTDc6Ew71oluwudgi7E");*/
        if(empty($openid)){
            header('Location:'.U('CheckinInfo/outer',array('id'=>$id)));
        }
        $Macinfo = $this ->getubind($openid);

        // $id = 21;
        if(count($Macinfo) == 1){
            $rother = array(
                'CompanyToken'=>self::Token,
                'DeviceMacs'=>$Macinfo[0]['mac'],
                'DataType'=>'ALL',
                'Info'=>1,
            );
            $encond = json_encode((object)$rother);
            $divicMac = $this ->callCurl(self::DeviceData_Batch, $encond,'json');
            foreach($divicMac['设备数据'] as $k => $v){
                sleep(1);
                $user = json_decode(file_get_contents(self::getuserMation.'?companyToken='. self::Token.'&DeviceMac='.$v['mac']),true);
                $userinfo =array_slice($user['用户设备关系'],-1);
                $Macinfo[$k]['userinfo'] =$userinfo[0]['设备名称'];
                $Macinfo[$k]['online'] =$v['online'];

            }
            session('Mac_arr',$Macinfo);
            header('Location:'.U('CheckinInfo/clickMac',array('id'=>0,'uid'=>$id)));
        }else if(count($Macinfo) > 1){
            $Mac = '';
            foreach($Macinfo as $k => $v){
                $Mac =$Mac == ''?$v['mac'] :$Mac.'|'.$v['mac'];
            }
            $rother = array(
                'CompanyToken'=>self::Token,
                'DeviceMacs'=>$Mac,
                'DataType'=>'ALL',
                'Info'=>1,
            );
            $encond = json_encode((object)$rother);
            $divicMac = $this ->callCurl(self::DeviceData_Batch, $encond,'json');
            foreach ($divicMac['设备数据'] as $k=>$v ){
                sleep(1);
                $user = json_decode(file_get_contents(self::getuserMation.'?companyToken='. self::Token.'&DeviceMac='.$v['设备地址']),true);
                $userinfo =array_slice($user['用户设备关系'],-1);
                $Macinfo[$k]['userinfo'] =$userinfo[0]['设备名称'];
                $Macinfo[$k]['online'] =$v['online'];
            }
            $Macinfo =array_reverse($Macinfo);
            session('Mac_arr',$Macinfo);
            $this ->assign('uid',$id);
            $this ->assign('Macinfo',$Macinfo);
            $this ->display('lister');
            return false;
        }else{
            $this ->assign('data','请重新扫描设备!');
            $this ->display('tishi');
            return false;
        }
        $this->display();
    }
    //查询设备信息

    public function clickMac(){
        $uid = $_SESSION['TRAVEID'];
        if(empty($uid)){
            $uid = I('uid');
        }
        $openid=session('TRAVELLEROPENID');
        if(empty($openid) || empty($_SESSION['Mac_arr'])){
            header('Location:'.U('CheckinInfo/outer',array('id'=>$uid)));
        }

        $Mac_num = I('id');
        session('Macnum',$Mac_num);
        $Macinfo = $_SESSION['Mac_arr'];
        $one_Mac = $Macinfo[$Mac_num ];
        $this ->chaxun($one_Mac, $_SESSION['TRAVEID']);
        $this ->display('index');
    }

    private function chaxun($Macinfo,$id){
        session('mac',$Macinfo['mac']);
        session('devices',$Macinfo['deviceid']);
        session('userinfo',$Macinfo['userinfo']);
        if(is_array($Macinfo)){
            $infomation = CheckinInfoModel::send_post($Macinfo['mac']);
            //$infomation = CheckinInfoModel::findMac($Macinfo['mac']);
            //根据设备MAC查询酒店和客房基本信息
            $wheres['o.equipment_sno']=$_SESSION['mac'];
            $hotel_room = M('OperInfo')
                ->alias('o')
                ->join('yx_oper_order as r on r.id = o.oo_id')
                ->where($wheres)
                ->field('o.rt_id,o.room_sno,r.h_id')
                ->find();
            if(empty($hotel_room['room_sno'])){
                $this ->assign('data','此设备尚未安装，请扫描其它设备');
                $this ->display('tishi');
                return false;
            }
        }
        $_POST['u_id'] =$id;
        $_POST['rt_id'] =$hotel_room['rt_id'];
        $_POST['room_sno']=$hotel_room['room_sno'];
        $_POST['h_id']=$hotel_room['h_id'];
//        sleep(1);
//        //最近一个小时的状态,室外温湿度无法获取
//        $infomation_houre = CheckinInfoModel::inhoure($_SESSION['devices']);
//        if(empty($infomation_houre)){
//            CheckinInfoModel::user_order('未获取到数据');
//        }
        //本地获取
        /*本地获取开始*/
        /*
         *   $_POST['in_pm']=$infomation['indoor_pm'];
          $_POST['out_pm']=$infomation['apm25'];
          $_POST['in_temperature']=$infomation['temperature'];
          $_POST['in_humidity']=$infomation['humidity'];
          $_POST['air_speed']=$infomation['speed'];
        */
        /*本地获取结束*/
        // 接口获取
        /*接口获取开始*/
        $_POST['in_pm']= $infomation['data']['pm25'];
        $_POST['out_pm'] = $infomation['data']['apm25'];
        $_POST['in_temperature']=$infomation['data']['temp'];
        $_POST['in_humidity']=$infomation['data']['humi'];
        $_POST['air_speed']=$infomation['info']['speed'];
        /*接口获取结束*/
        $_POST['eoper_num']= 1;
        //同一间房间不同日期进行添加
        $time = date('Y-m-d',time());
        $timer = M('CheckinInfo')->where(array('u_id'=>$id))->field('id,ctime,eoper_num')->order('ctime desc')->limit(1)-> find();
        $cha =  date('Y-m-d',$timer['ctime']) ;
        if($cha != $time){
            if(!empty($_POST['room_sno'])){
                $_POST['ctime'] =time();
                $number= M('CheckinInfo')->add($_POST);
                M('Device') -> where(array('mac' =>$Macinfo['Mac']))->setField('uid',$id);
            }
        }else{
            $timer['eoper_num']++;
            $_POST['eoper_num'] = $timer['eoper_num'];
            M('CheckinInfo')->where(array('id'=>$timer['id']))->setField($_POST);
            $number =$timer['id'];
        }
        $where['o.id'] = $number;
        $shebei['Mac'] = $Macinfo['Mac'];
        $data = CheckinInfoModel::getmore($where);
        $promotion =M('Promotion') ->where(array('h_id'=>$data['h_id'],'status'=>1))->field('id,img')
            ->find();
        //本地获取
        /*本地获取开始*/
        /*$data['oper'] = $infomation['power'];
        $data['online'] =  $infomation['online'];
        $data['timeopen'] =$infomation['timeopen'];
        $data['timeoff'] = $infomation['timeoff'];
        $data['ac_type'] = $_SESSION['ac_type'];*/
        /*本地获取结束*/
        /*接口获取开始*/
        //接口获取
        $data['oper'] = $infomation['info']['power'];
        $data['online'] =  $infomation['info']['online'];
        $data['timeopen'] =$infomation['info']['timeopen'];
        $data['timeoff'] = $infomation['info']['timeoff'];
        $data['ac_type'] = $_SESSION['ac_type'];
        /*接口获取结束*/
        $data['time'] = time();
        $data['online'] =$Macinfo['owner'] ==1 ?$infomation['info']['online'] : 3;
        $this ->assign('infomation',$infomation);
        $this ->assign('promotion',$promotion);
        $this ->assign('shebei',$shebei);
        $this ->assign('data',$data);
    }

    //酒店信息
    public function hotel(){
        $id =I('id');
        $data =CheckinInfoModel::hotelInformation($id);
        if(!empty($data['service_ids'])){
            $data['in_servies'] = explode(',',$data['service_ids']);
            foreach ($data['in_servies'] as $key =>$val){
                $sever[$key] = M('HotelSerivce') ->where(array('id'=>$val)) ->find();
            }
        }
        foreach ($sever as $key =>$val ){
            $sever[$key]['name'] =mb_substr($val['name'],0,5,'utf-8');
        }
        $data['img'] = explode(',',$data['img']);
        $data['img'] =$data['img']['0'];
        $this ->assign('data',$data);
        $this ->assign('server',$sever);
        $this ->display();
    }
    //我的足迹
    public function footprint(){
        $user =$_SESSION['TRAVEID'];
        $where['checkinInfo.u_id'] =$user;
        $model ='CheckincHoteltHotelcView';
        $data_list =D('CheckinInfo') ->getList($model,$where,$order_str='checkinInfo.ctime desc');
        //数据处理
        foreach ($data_list['datas'] as $key =>$val){
            $img = explode(',',$val['img']);
            $data_list['datas'][$key]['img'] = $img[0];
            CheckinInfoModel::inafter($val);
        }
        $this->assign('items',$data_list['datas']);
        $this->assign('num',$_SESSION['Macnum']);
        $this ->display();
    }
    //足迹详情
    public function details(){
        $uid = $_SESSION['TRAVEID'];
        if(empty($uid)){
            $uid = I('uid');
        }
        $openid=session('TRAVELLEROPENID');
        if(empty($openid) || empty($_SESSION['Mac_arr'])){
            header('Location:'.U('CheckinInfo/outer',array('id'=>$uid)));
        }
        $id = I('get.id');
        $where['o.id'] =$id;
        $data =CheckinInfoModel::getmore($where);
        $promotion =M('Promotion') ->where(array('h_id'=>$data['h_id'],'status'=>1))->field('id,img')
            ->find();
        $this ->assign('promotion',$promotion);
        $this ->assign('data',$data);
        $this ->display();
    }


    //日数据
    public function thisday(){
        $allDate = [];
        for ($i = 0; $i < 24; $i ++) {
            $time =  strtotime('-'.$i.' hour');
            $allDate[] = date("Y-m-d/H",$time);
        }
        $InallDate = array_reverse($allDate);

        //获取数据
        $deviced = $_SESSION['devices'];
        $items = CheckinInfoModel::houres($deviced,24);
        $data = array();
        foreach ($InallDate as $keys => &$vals) {
            if ($items[$keys]['fulldate'] ==$vals) {
                $data[$keys] = $items[$keys];
            }else{
                $data[$keys]['pm25'] = $items[$keys]['pm25'];
                $data[$keys]['fulldate'] = $vals ;
            }
            $data[$keys]['fulldate'] =  substr($data[$keys]['fulldate'],8,5);
            $data[$keys]['fulldate'] =str_replace(array('/'), array('日'), $data[$keys]['fulldate'].'点');
        }
        $type = 1;
        $user = CheckinInfoModel::userInfor();
        $this->assign('user', $user);
        $this->assign('date', json_encode(i_array_column($data, 'fulldate')));
        $this->assign('type', $type);
        $this->assign('spm25', json_encode(i_array_column($data, 'pm25')));
        $this ->display('hostory');

    }
    //周数据
    public function common(){
        $rows = CheckinInfoModel::indays(7);

        $listDatas = CheckinInfoModel::getUpwardDay(7);
        $InallDate = array_reverse($listDatas);
        $datas = array();
        foreach ($InallDate as $keys => &$vals) {
            if ($rows[$keys]['fulldate'] ==$vals) {
                $datas[$keys] = $rows[$keys];
            } else {
                // 没有数据时取下一个
                $datas[$keys]['pm25'] =  $rows[$keys]['pm25'];
                $datas[$keys]['fulldate'] = $vals;
            }
            $datas[$keys]['fulldate'] =  substr($datas[$keys]['fulldate'],5,5);
            $datas[$keys]['fulldate'] =str_replace(array('-'), array('月'), $datas[$keys]['fulldate'].'日');
        }
        $type = 2;
        $this->assign('date', json_encode(i_array_column($datas, 'fulldate')));
        $user = CheckinInfoModel::userInfor();
        $this->assign('user', $user);
        $this->assign('type', $type);
        $this->assign('spm25', json_encode(i_array_column($datas, 'pm25')));
        $this ->display('hostory');
    }
    //月数据
    public function thisyue()
    {
        $InallDate = [];
        for ($i = 0; $i < 30; $i ++) {
            $time =  strtotime('-'.$i.' day');
            $InallDate[] = date("m-d",$time);
        }
        $allDate = array_reverse($InallDate);
        $items = CheckinInfoModel::indays(30);
        $datas = array();
        foreach ($items as $key =>&$val){
            if(strlen($val['fulldate']) == 1){
                $items[$key]['fulldate'] = $val;
            }else{
                $items[$key]['fulldate'] = $allDate[$key];
            }
            $items[$key]['pm2.5'] = $val['temp'];
            if($items[$key]['fulldate'] == $allDate[$key]){
                $datas[$key] = $items[$key];
            }else{
                //没有数据时默认取择一条有数据的内容
                $datas[$key]['pm2.5'] = $items[$key];
                $datas[$key]['fulldate'] = $allDate[$key];
            }
            $datas[$key]['fulldate'] =str_replace(array('-'), array('月'), $datas[$key]['fulldate'].'日');
        }
        $user = CheckinInfoModel::userInfor();
        $type = 3;
        $this->assign('date', json_encode(i_array_column($datas, 'fulldate')));
        $this->assign('type', $type);
        $this->assign('user', $user);
        $this->assign('spm25', json_encode(i_array_column($datas, 'pm25')));
        $this ->display('hostory');
    }

    //分享出去的页面
    public function outer(){
        $id = I('id');
        $where['u.id'] =$id;
        $data = CheckinInfoModel::getmore($where);
        $data['time'] = time();
        $promotion =M('Promotion') ->where(array('h_id'=>$data['h_id'],'status'=>1))->field('id,img')
            ->find();
        $this ->assign('data',$data);
        $this ->assign('promotion',$promotion);
        $this ->assign('tel',C('TEL'));
        $this ->display();
    }
    //促销信息查看
    public function promotion(){
        $data = M('Promotion') ->where(array('id'=>I('id'))) ->find();
        $data['content'] = html_entity_decode($data['content']);
        $this ->assign('data',$data);
        $this ->display();
    }


    /*
     * type 1,风速（value代表风速）；2,开关（value代表开关状态0：关闭；1：开启）；3,设备定时；4,温度控制
     * 修改设备状态
     * */
    public function callback(){
        if($_POST['type'] == 4){
            session('ac_type',$_POST['ac_type']);
        }
        $Mac = $_SESSION['mac'];
        $rother = array(
            'companyToken' =>self::Token,
            'commands' =>array(0=>array(
                'deviceid'=>$_SESSION['devices'],
                'mac'=>$Mac,
                'value'=>$_POST['value'],
            ))
        );
        $str = json_encode((object)$rother);
        if($_POST['type'] ==1){
            $data = $this ->callCurl(self::postSpeed,$str,'json');
        }elseif($_POST['type'] ==2){
            $data = $this ->callCurl(self::Postoper, $str,'json');
        }elseif($_POST['type'] == 3){
            $equipmenttime = array(
                'companyToken' =>self::Token,
                'commands' =>array(0=>array(
                    'deviceid'=>$_SESSION['devices'],
                    'mac'=>$Mac,
                    'timed'=>1,
                    'timeopen'=>$_POST['timeopen'],
                    'timeoff'=>$_POST['timeoff'],
                ))
            );
            $arr = json_encode((object)$equipmenttime);
            $data = $this ->callCurl(self::PostTime, $arr,'json');
        }else{
            $equipmenttime = array(
                'companyToken' =>self::Token,
                'commands' =>array(0=>array(
                    'deviceid'=>$_SESSION['devices'],
                    'mac'=>$Mac,
                    "ac_type"=>$_POST['ac_type'],
                    'ac_temp'=>$_POST['ac_temp'],
                ))
            );
            $arr = json_encode((object)$equipmenttime);
            $data = $this ->callCurl(self::inTemp, $arr,'json');
        }
        //出现错误时写入文档
        if(!empty($data['message'])){
            CheckinInfoModel::user_order($data['message']);
        }
        echo (json_encode($data));exit;
    }

    public function callCurl($url, $arrParams, $format, $timeout = 15){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $arrParams);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        if('json' === $format){// header
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json;charset=utf-8'));
        }
        $ret = curl_exec($ch);
        if(curl_errno($ch)){//出错则显示错误信息
            print curl_error($ch);
        }
        curl_close($ch); //关闭curl链接
        $result = json_decode($ret, true);
        return $result;
    }
//实时数据
    public function calltime(){
        $Mac = $_SESSION['mac'];
        if(!empty($Mac)){
            $intime = CheckinInfoModel::send_post($Mac);
            sleep(1);
            $infomation_houre = CheckinInfoModel::inhoure($_SESSION['devices']);
            if($intime){
                $data['in_pm'] =$intime['data']['pm25'];
                $data['speed'] =$intime['info']['speed'];
                $data['oper'] =$intime['info']['power'];
                $data['in_temperature'] =$intime['data']['temp'];
                $data['in_humidity'] =$intime['data']['humi'];
                $data['out_pm'] =$infomation_houre['apm25'];
            }
            $content = CheckinInfoModel::inafter($data);
            echo (json_encode($content));exit;
        }
    }
}