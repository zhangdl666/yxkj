<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/9/28
 * Time: 15:43
 */

namespace Wx\Model;


use Pc\Model\DeviceRunModel;
use Pc\Model\RunInfoModel;

class RunInfoDataModel extends BaseModel{
    const STATUS_ONE = 'green';  // 运行正常
    const STATUS_TWO = '';  // 暂无开启
    const STATUS_THREE = 'red'; // 超标预警
    const STATUS_FOUR = 'orange'; // 离线预警

    const STATE_ONE = 'green'; // 优
    const STATE_TWO = 'orange'; // 良
    const STATE_THE = 'red'; //查
    const STATE_FOU = ''; //设备未检测到

    //获取酒店对应的信息
    public static function getHotelInfo($userinfo,$hids){
        $data = array();
        if($hids){
            $hotel = M("Hotel")->where(array('id'=>$hids))->find();
        }else{
            $hotel = M("Hotel")->where(array('id'=>$userinfo['hotel_id']))->find();
        }

        $data['hotel_name'] = $hotel['name'];      //酒店名称
        $data['hotel_city'] = $hotel['city'];      //酒店所在城市
        $data['hotel_county'] = $hotel['county'];      //酒店所在区县
        $time = time();
        $promotions = M("Promotion")->field("id,img,unix_timestamp(stime) as stime,unix_timestamp(etime) as etime,ctime")->where(array('h_id'=>$userinfo['hotel_id'],'status'=>1))->select();
        $newpromotions = array();
        foreach($promotions as $promotion){
            if(($promotion['stime'] < $time) && ($promotion['etime'] > $time)){     //正在进行的促销活动
                $newpromotions[] = $promotion['img'];
            }
        }
        $newpromotions = shuffle($newpromotions);
        $data['promotion_img'] = $newpromotions[0];

        //酒店安装设备房间数、房间编号
        if($hids) {
//            $where = array('obj.status' => 4, 'obj.h_id' => $hids, 'b.status' => 1);
            $where = array('obj.h_id' => $hids, 'b.status' => 1);
            $roomData = M("OperOrder")->alias('obj')
                ->field('obj.id,b.equipment_sno,b.room_sno')
                ->join("yx_oper_info as b on b.oo_id=obj.id")
                ->where($where)
                ->select();
            foreach ($roomData as $key => &$val) {
                $val['sort'] = preg_replace('/([\x80-\xff]*)/i', '', $val['room_sno']);
            }
            $roomData =  arraySequence($roomData, 'sort', 'SORT_ASC');
        }else{
//            $where = array('obj.status' => 4, 'obj.h_id' => $userinfo['hotel_id'], 'b.status' => 1);
            $where = array('obj.h_id' => $userinfo['hotel_id'], 'b.status' => 1);
            $roomData = M("OperOrder")->alias('obj')
                ->field('obj.id,b.equipment_sno,b.room_sno')
                ->join("yx_oper_info as b on b.oo_id=obj.id")
                ->where($where)
                ->select();
            foreach ($roomData as $key => &$val) {
                $val['sort'] = preg_replace('/([\x80-\xff]*)/i', '', $val['room_sno']);
            }
            $roomData =  arraySequence($roomData, 'sort', 'SORT_ASC');
        }

        //室内空气累计优质天数  (暂时没有提供计算方式?)


        //PM2.5对应的当日室内空气质量均值、当日室外空气质量均值
//        $pm25 = 0;
//        $apm25 = 0;
//        foreach ($roomData as $key => $val) {
////            $newdata = RunInfoModel::getNewDevicesInfo($val['equipment_sno']);
//            $infodata = RunInfoModel::getOneInfo($val['equipment_sno']);
//            $city_id = $infodata['data']['devices'][0]['city'];
//            $area_id = $infodata['data']['devices'][0]['area'];
//            $weatch = RunInfoModel::getCityData($city_id, $area_id);
//            $apm25 += $weatch['info']['PM25'];
////            $pm25 += $newdata['data']['devices'][0]['data']['pm25'];
////            $roomData[$key]['status'] = self::handleDataOne($newdata);
//        }
        $data['rooms'] =DeviceRunModel::DeviceRemInfo($roomData);     //酒店安装设备房间信息
        $data['room_num'] = count($roomData);     //酒店安装设备房间数量

//        $data['pm25']= DeviceRunModel::DevsRunInPm25($roomData);    //当日室内空气质量均值
//        $data['pm25s'] = testingPm($data['pm25']);            //当日室内空气质量均值状态 优 良 差
//        $data['apm25'] = intval($apm25 / $data['room_num']);  //当日室外空气质量均值
//        $data['apm25s'] = testingPm($data['apm25']);          //当日室外空气质量均值状态 优 良 差

        return $data;
    }


    /**
     * 处理设备返回的信息
     */
    public static function handleDataOne($data)
    {
        if ($data['code'] == 0) {
            // 成功获取到该设备的最后一次信息
            $pm25 = $data['data']['devices'][0]['data']['pm25'];
            $num = testingPm($pm25);
            if ($num == 1) {
                return self::STATE_ONE;
            } else if ($num == 2) {
                return self::STATE_TWO;
            } else if ($num == 3) {
                return self::STATE_THE;
            }
        }
        return self::STATE_FOU;
    }


}