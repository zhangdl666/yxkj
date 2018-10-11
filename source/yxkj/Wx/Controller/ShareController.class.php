<?php
/**
 * Author: ' Silent
 * Time: 2017/12/25 18:38
 */

namespace Wx\Controller;


use Pc\Model\DeviceRunModel;
use Pc\Server\DateType;
use Think\Controller;
use Wx\Model\RunInfoDataModel;
use Wx\Model\RunInfoModel;
use Wx\Model\SalesModel;

class ShareController extends Controller
{
    /*
    * 净化效果 分享
    ***/
    public function share(){
        $hid = I('get.id');
        $this->common($hid);
        $promotion =M('Promotion') ->where(array('h_id'=>$hid,'status'=>1))->field('id,img')
            ->order('ctime desc')->find();
        $this ->assign('promotion',$promotion);
        $this ->assign('tel',C('TEL'));
        $this->display('share');
    }

    public function common($hid){
        //获取酒店对应的信息
        //$userinfo = session('USERINFO');
        $data = RunInfoDataModel::getHotelInfo('',$hid);
        $this->assign($data);

        $shareInfo['title'] = '净化效果';
        $shareInfo['summary'] = $data['summary'].'净化效果';
        $shareInfo['web_url'] = WEB_URL;
        $this->assign($shareInfo);

        $this->assign($shareInfo);
        $listRunDatas = $this->getHotelSevenDays($hid);
        $yznum = 0;
        foreach ($listRunDatas as $key => $val){
            if($val['indoor_pm'] > 0 && $val['indoor_pm'] <= 34){
                $yznum++;
            }
        }
        $this->assign('yznum',$yznum);
        $this->assign('pm25', DeviceRunModel::DevsRunInPm25($data['rooms']));
        $this->assign('indoor_pming', testingPm(DeviceRunModel::DevsRunInPm25($data['rooms'])));
        $this->assign('apm25', DeviceRunModel::DevsRunOutPm25($data['rooms']));
        $this->assign('outdoor_pming', testingPm(DeviceRunModel::DevsRunOutPm25($data['rooms'])));
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

}