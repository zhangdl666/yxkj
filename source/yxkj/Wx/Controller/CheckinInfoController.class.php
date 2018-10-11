<?php
/**
 * Created by ${CONTROLLER_NAME}.
 * @auther: 刘小伟
 * Date: 2017/9/29
 * Time: 17:04
 */
namespace Traveller\Controller;
use Traveller\Model\CheckinInfoModel;
class CheckinInfoController extends BaseController{
    /**
     * 接口地址
     */
    const Token = '523c374d-fa46-d6a7-a068-d80d88612dfc';  // 开发者公司的apiToken

    // 2.0接口(启用的是2.0接口)
    const GetDeviceInfo = "http://api.bjhike.com/api/v2_deviceInfo"; // 获取设备信息(传入错误的mac地址,也会返回200,但是devices与message是为空的)
    const getDeviceNewInfo = "http://api.bjhike.com/api/v2_deviceData"; // 设备最新一次状态
    const gethoure ="http://api.bjhike.com/api/v2_hours";//返回以小时为单位的设备状态
    const Relieve ="http://api.bjhike.com/api/v2_unbind";//删除用户与设备的绑定关系
    const getuserMation ="http://api.bjhike.com/api/v2_userBind";//返回用户与设备的关系
    const postSpeed = "http://api.bjhike.com/api/v2_speed"; // 修改风速数据接口
    const Postoper = "http://api.bjhike.com/api/v2_power"; // 修改开关接口
    const PostTime = "http://api.bjhike.com/api/v2_time"; // 设备定时接口
    //首页数据分配
    public function index()
    {
        $id = $_SESSION['TRAVEID'];
        if(empty($id)){
            $id = I('get.id');
        }
        $openid=session('TRAVELLEROPENID');
     //$openid ="o6jtgw9cbxTDc6Ew71oluwudgi7E";
            if(empty($openid)){
              header('Location:'.U('CheckinInfo/outer',array('id'=>$id)));
            }
          $Macinfo = $this ->getubind($openid);
     // $id= 643;
//    $Macinfo['Mac'] = 'D0BAE41B4C81';
//     $Macinfo['devices'] = 'gh_73d4995abdb8_e22a727c0261ef66 ';
        session('mac',$Macinfo['Mac']);
        session('devices',$Macinfo['devices']);
        if(is_array($Macinfo)){
            $infomation = $this ->send_post($Macinfo['Mac']);
            //根据设备MAC查询酒店和客房基本信息
            $hotel_room = M('OperInfo')
                ->alias('o')
                ->join('yx_oper_order as r on r.id = o.oo_id')
                ->where(array('o.equipment_sno'=>$infomation['info']['mac']))
                ->field('o.rt_id,o.room_sno,r.h_id')
                ->find();
        }else{
               $this ->assign('data',$Macinfo);
               $this ->display('tishi');
               return false;
        }
        $_POST['u_id'] =$id;
        $_POST['rt_id'] =$hotel_room['rt_id'];
        $_POST['room_sno']=$hotel_room['room_sno'];
        $_POST['h_id']=$hotel_room['h_id'];
        //最近一个小时的状态,室外温湿度无法获取
        $infomation_houre = $this ->inhoure($_SESSION['devices']);
        $_POST['in_pm']=$infomation_houre['pm25'];
        $_POST['out_pm']=$infomation_houre['apm25'];
        $_POST['in_temperature']=$infomation_houre['temp'];
//        $_POST['out_temperature']=$num;
        $_POST['in_humidity']=$infomation_houre['humi'];
//        $_POST['out_humidity']=$num;
        $_POST['air_speed']=$infomation['info']['speed'];
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
             }else{
                 $this ->assign('data','该设备尚未安装，请扫描其它设备');
                 $this ->display('tishi');
                 return false;
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
        $promotion =M('Promotion') ->where(array('h_id'=>$data['h_id'],'status'=>1))->field('id,img') ->select();
        $data['oper'] = $infomation['info']['power'];
        $data['online'] =  $infomation['info']['online'];
        $data['timeopen'] =$infomation['info']['timeopen'];
        $data['timeoff'] =$infomation['info']['timeoff'];
        $this ->assign('infomation',$infomation);
        $this ->assign('promotion',$promotion);
        $this ->assign('shebei',$shebei);
        $this ->assign('data',$data);
        $this->display();
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
           if(0< $val['in_pm']&& $val['in_pm'] < 35){
               $data_list['datas'][$key]['in_air'] = '优';
           }elseif (35< $val['in_pm']&& $val['in_pm'] < 70){
               $data_list['datas'][$key]['in_air'] = '良';
           }else{
               $data_list['datas'][$key]['in_air'] = '';
           }
       }
        $this->assign('items',$data_list['datas']);
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        $this ->display();
    }
    //足迹详情
    public function details(){
        $id = I('get.id');
        $where['o.id'] =$id;
       $data =CheckinInfoModel::getmore($where);
        $data['img'] =implode(',',$data['img']);
        $this ->assign('data',$data);
        $this ->display();
    }
    //分享出去的页面
    public function outer(){
        $id = I('id');
        $where['u.id'] =$id;
        $data = CheckinInfoModel::getmore($where);
        $this ->assign('data',$data);
        $this ->display();
    }
    //促销信息查看
    public function promotion(){
        $data = M('Promotion') ->where(array('id'=>I('id'))) ->find();
        $data['content'] = html_entity_decode($data['content']);
        $this ->assign('data',$data);
        $this ->display();
    }

/**
* 获取设备最新信息
 * $Mac 设备MAC地址
 * Return array
*/
   public function send_post( $Mac) {
       $companyToken = self::Token;
       $result = file_get_contents(self::getDeviceNewInfo.'?companyToken='.$companyToken.'&mac='.$Mac);
       $mation_array =json_decode($result,true);
       return $mation_array['devices'][0];
    }
    /*
     * 解除绑定
     * */
    public  function Relieve(){
        $openid=session('TRAVELLEROPENID');
        $openid ="o6jtgw9cbxTDc6Ew71oluwudgi7E";
        $equipmenttime = array(
            'companyToken' =>self::Token,
            'devices' =>$_SESSION['devices'],
            'mac' =>$_SESSION['mac'],
            'openid' =>$openid
        );
        $arr = json_encode((object)$equipmenttime);
        $data = $this ->callCurl(self::Relieve, $arr,'json');
        var_dump($data);exit;
    }
    /*
     *获取最近一个小时的状态
     * */
    public function inhoure($deviced){
        $companyToken = self::Token;
        $result = file_get_contents(self::gethoure.'?companyToken='.$companyToken.'&deviceid='.$deviced.'&hours=1');
        $mation_array =json_decode($result,true);
        return $mation_array['hours'][0];
    }
    /*
     * type 1,风速（value代表风速）；2,开关（value代表开关状态0：关闭；1：开启）；3,设备定时
     * 修改设备状态
     * */
    public function callback(){
        $Mac = $_SESSION['mac'];
        $mation =$this ->send_post($Mac);
        $rother = array(
            'companyToken' =>self::Token,
            'commands' =>array(0=>array(
                'deviceid'=>$mation['info']['deviceid'],
                'mac'=>$Mac,
                'value'=>$_POST['value'],
            ))
        );
        $str = json_encode((object)$rother);
        if($_POST['type'] ==1){
            $data = $this ->callCurl(self::postSpeed,$str,'json');
        }elseif($_POST['type'] ==2){
            $data = $this ->callCurl(self::Postoper, $str,'json');
        }else{
            $equipmenttime = array(
                'companyToken' =>self::Token,
                'commands' =>array(0=>array(
                    'deviceid'=>$mation['info']['deviceid'],
                    'mac'=>$Mac,
                    'timed'=>1,
                    'timeopen'=>$_POST['timeopen'],
                    'timeoff'=>$_POST['timeoff'],
                ))
            );
            $arr = json_encode((object)$equipmenttime);
            $data = $this ->callCurl(self::PostTime, $arr,'json');
        }
        var_dump($data);exit;
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
          $intime =$this ->send_post($Mac);
          $infomation_houre = $this ->inhoure($intime['info']['deviceid']);
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