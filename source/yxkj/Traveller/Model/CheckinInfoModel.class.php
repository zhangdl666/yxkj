<?php
/**
 * Created by ${CONTROLLER_NAME}.
 * @auther: 刘小伟
 * Date: 2017/9/29
 * Time: 17:04
 */
namespace Traveller\Model;
use Traveller\Model\BaseModel;


class CheckinInfoModel extends BaseModel {
    /**
     * 接口地址
     */
    const Token = '523c374d-fa46-d6a7-a068-d80d88612dfc';  // 开发者公司的apiToken
    const getDeviceNewInfo = "http://api.bjhike.com/api/v2_deviceData"; // 设备最新一次状态
    // 2.0接口(启用的是2.0接口)
    const gethoure ="http://api.bjhike.com/api/v2_hours";//返回以小时为单位的设备状态
    const getDays = 'http://api.bjhike.com/api/v2_days';//返回以天为单位的数据
    //房间详情
    public static function getmore($where){
     $roth =   M('CheckinInfo')
            ->alias('o')
            ->join('yx_hotel as h on h.id =o.h_id')
            ->join('yx_user as u on u.id = o.u_id')
            ->join('yx_room_type as r on r.id = o.rt_id')
            ->join('yx_oper_info as i on i.room_sno = o.room_sno')
            ->join('yx_hotel_type as t on t.id = h.ht_id')
            ->where($where)
            ->field('o.*,h.provice,h.city,h.county,h.area,h.img,h.name as hotel_name,t.name as type_name,r.name as room_name,u.name as user_name,i.status')
            ->order('o.ctime desc')
            ->find();
        $week = date("w",$roth['ctime']);
        //自定义星期数组
        $weekArr=array("星期日","星期一","星期二","星期三","星期四","星期五","星期六");

        //获取数字对应的星期
        $roth['wtime'] =  $weekArr[$week];
        return CheckinInfoModel::inafter($roth);
    }
    //酒店信息
    public static function hotelInformation($id){
        return M('Hotel')
                ->alias('o')
                ->join('yx_hotel_type as h on h.id = o.ht_id')
                ->field('o.name,o.img,o.provice,o.city,o.county,o.area,o.service_ids,h.name as type_name,o.tell')
                ->where(array('o.id'=>$id))
                ->find();
    }
    //空气质量
    public  static function inafter($data){
        if(0<=  $data['in_pm']&& $data['in_pm'] <=  35){
            $data['in_air'] = '优' ;
        }elseif (35<  $data['in_pm']&& $data['in_pm'] <=75){
            $data['in_air'] = '良';
        }elseif(75<  $data['in_pm']&& $data['in_pm'] <=115){
            $data['in_air'] = '轻度';
        }elseif(115<  $data['in_pm']&& $data['in_pm'] <=150){
            $data['in_air'] = '中度';
        }elseif(35<=  $data['in_pm']&& $data['in_pm'] <=75){
            $data['in_air'] = '重度';
        }else{
            $data['in_air'] = '严重';
        }
        if(0<= $data['out_pm']&& $data['out_pm'] <= 35){
            $data['out_air'] = '优';
        }elseif (35<= $data['out_pm']&& $data['out_pm'] <= 75){
            $data['out_air'] = '良';
        }elseif(75<  $data['out_pm']&& $data['out_pm'] <=115){
            $data['out_air'] = '轻度';
        }elseif(115<  $data['out_pm']&& $data['out_pm'] <=150){
            $data['out_air'] = '中度';
        }elseif(150<  $data['out_pm']&& $data['out_pm'] <=250){
            $data['out_air'] = '重度';
        }else{
            $data['out_air'] = '严重';
        }
        if($data['in_temperature']< 15){
            $data['temp_in'] = '偏冷';
        }else if(30<$data['in_temperature']){
            $data['temp_in'] = '偏热';
        }else{
            $data['temp_in'] = '舒适';
        }
        if($data['in_temperature']< 15){
            $data['temp_out'] = '偏冷';
        }else if(30<$data['out_temperature']){
            $data['temp_out'] = '偏热';
        }else{
            $data['temp_out'] = '舒适';
        }
        if(60 < $data['in_humidity'] ){
            $data['humidity'] = '舒适';
        }else{
            $data['humidity'] = '干燥';
        }
        if($data['air_speed'] == 1){
            $data['speeder'] = 'Ⅰ';
        }else if($data['air_speed'] == 2){
            $data['air_speeder'] = 'Ⅱ';
        }else if($data['air_speed'] == 3){
            $data['air_speeder'] = 'Ⅲ';
        }else{
            $data['air_speeder'] = '关闭';
        }
        return $data;
    }
    /**
     * 获取设备最新信息
     * $Mac 设备MAC地址
     * Return array
     */
    public static function send_post( $Mac) {
        $companyToken = self::Token;
        $result = file_get_contents(self::getDeviceNewInfo.'?companyToken='.$companyToken.'&mac='.$Mac);
        $mation_array =json_decode($result,true);
        return $mation_array['devices'][0];
    }
    /*
 * 获取数据库数据
 * */
    public static function findMac($mac){
        //根据时间查找最新数据属于哪个数据表
        $num = M('Device') ->where(array('mac'=>$mac))->field('id')->find();
        $num = ceil(($num['id']) / 100);
        $where['device_code'] = $mac;
        return  M('UploadDeviceInfo_'.$num)->where($where)->find();
    }
    /*
    /*
     *获取最近一个小时的状态
     * */
    public static function inhoure($deviced){
        $companyToken = self::Token;
        $result = file_get_contents(self::gethoure.'?companyToken='.$companyToken.'&deviceid='.$deviced.'&hours=1');
        $mation_array =json_decode($result,true);
        return $mation_array['hours'][0];
    }
    /*天数据*/
    public static function houres($deviced,$time){
        $companyToken = self::Token;
        $result = file_get_contents(self::gethoure.'?companyToken='.$companyToken.'&deviceid='.$deviced.'&hours='.$time);
        $mation_array =json_decode($result,true);
        return $mation_array['hours'];
    }
    /*周数据*/
    public static function indays($day){
        $companyToken = self::Token;
        $result = file_get_contents(self::getDays.'?companyToken='.$companyToken.'&mac='.$_SESSION['mac'].'&days='.$day);
        $mation_array =json_decode($result,true);
        return $mation_array['days'];
    }
    /*月数据*/
    public static function inyue($type ='温度'){
        $companyToken = self::Token;
        $url =self::getDays.'?companyToken='.$companyToken.'&DeviceMac='.$_SESSION['mac'].'&DataType='.urlencode($type);
        $result = file_get_contents($url);
        $mation_array =json_decode($result,true);
        return $mation_array['设备数据'];
    }
    //查询数据

    //出现错误或者未获取到数据时存入文档
    //
    public static function user_order($str_error){
        $username = M('User') -> field('id') ->where(array('id'=>$_SESSION['TRAVEID']))->find();
        $userinfo = './Traveller/User/' . date('Y-m-d') ;
        //接收错误提示信息保存到指定位置
        if (!is_dir($userinfo)) {
            mkdir($userinfo, 0777, true);
        }
        $name = '用户id为'.$username['id'].'的用户';
        $content = $name.'在'.date('Y-m-d H:i:s',time()).'操作出现错误,错误类型为'.$str_error;
        file_put_contents($userinfo.'/hostory.txt', $content."\r\n",FILE_APPEND);
        return ;
    }
    //用户信息
    public static function userInfor(){
        $userinfo['userinfo'] = $_SESSION['userinfo'];
        $userinfo['Mac'] = $_SESSION['mac'];
        return $userinfo;
    }

    public static function getUpwardDay($day)
    {
        $allDate = [];
        for ($i = 0; $i < $day; $i++) {
            $allDate[] = date('Y-m-d/00', strtotime('-' . $i . ' day'));
        }
        return $allDate;
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
}