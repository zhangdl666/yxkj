<?php
/**
 * Author: ' Silent
 * Time: 2017/11/30 13:58
 */

namespace Pc\Controller;


use Pc\Model\HotelModel;
use Pc\Model\RunInfoDataModel;
use Pc\Model\RunInfoModel;
use Pc\Model\UserObjModel;

class OpeningController extends BaseController
{
    private $user_id;
    public $model = 'user';

    public function _initialize()
    {
        $hotelData = HotelModel::getHotelName();
        $this->assign('hotelData', $hotelData);
        $this->assign('role', $this->identity()->getRoleId());
        $this->user_id = $this->identity()->getUserId();
        parent::_initialize(); // TODO: Change the autogenerated stub
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

    public function index(){
        $this->display('index');
    }


    public function getDevicesInfo()
    {
        $filterdate = I('get.date');
        $hotel_id = I('get.hotel_id');
        if ($hotel_id) {
            $HotelRoom = RunInfoDataModel::getHotelRooms($hotel_id);
        } else {
            $HotelRoom = RunInfoDataModel::getHotelRoom($this->user_id);
        }
        $this->assign('hotelnum',count($HotelRoom));
        // 开始时间
        $startTime = date('Y-m-d H:i:s', $HotelRoom[0]['ctime']);
        // 结束时间
        $endTime = date('Y-m-d H:i:s', time());


        $date = date("Y-m-d");
        $firstday = date('Y-m-01', strtotime($date));
        $firstdays = strtotime(date('Y-m-01', strtotime($date)));
        $lastday = strtotime(date('Y-m-d', strtotime("$firstday +1 month -1 day")));
        // 统计所有房间
        $monthsbai = 0;
        $jidubai = 0;
        $nianbai = 0;
        $anbai = 0;
        $anbais = 0;
        $hahdaya = 0;
        foreach ($HotelRoom as $key => &$val) {
            // 获取设备最新状态
            $data = RunInfoModel::getNewDevicesInfo($val['equipment_sno']);
            if ($data['data']['devices'][0]['info']['online'] == 0 && $data['data']['devices'][0]['info']['power'] == 0) {
                $val['status'] = 1;
            } else {
                // 算出是否达到了一个天次
                if($filterdate){
                    $dates = $filterdate . ' 14:00:00';
                    $beginToday = strtotime($dates);
                    $endToday = strtotime(date('Y-m-d H:i:s', strtotime("+1 day", $beginToday)));
                }else{
                    $dates = $date . ' 14:00:00';
                    $beginToday = strtotime($dates);
                    $endToday = strtotime(date('Y-m-d H:i:s', strtotime("+1 day", $beginToday)));
                }
                $datas = RunInfoModel::getDevicesHoures($val['equipment_sno'], 7 * 24);
                $infodata = array();
                foreach ($datas['data']['hours'] as $keys => $vals) {
                    if ($vals['date'] > $beginToday && $vals['date'] < $endToday) {
                        $infodata[] = $vals;
                    }
                }
                if ($infodata == 24) {
                    $val['status'] = 2;
                } else {
                    $val['status'] = 3;
                }

                //今日开启次数
                if($filterdate){
                    $dates = $filterdate . ' 14:00:00';
                    $beginToday = strtotime($dates);
                    $endToday = strtotime(date('Y-m-d H:i:s', strtotime("+1 day", $beginToday)));
                }else{
                    $dates = $date . ' 14:00:00';
                    $beginToday = strtotime($dates);
                    $endToday = strtotime(date('Y-m-d H:i:s', strtotime("+1 day", $beginToday)));
                }
                $datas = RunInfoModel::getDevicesHoures($val['equipment_sno'], 7 * 24);
                $infodata = array();
                foreach ($datas['data']['hours'] as $keys => $vals) {
                    if ($vals['date'] > $beginToday && $vals['date'] < $endToday) {
                        $infodata[] = $vals;
                    }
                }
                $numonese = ($this->chaifenshuzu($infodata, 1));

                $deviceData = RunInfoModel::getDevicesHoures($val['equipment_sno']);
                $monthDay = $deviceData['data']['hours'];
                $monthDays = array();
                // 算出开启天数

                // 算出本月度开启率
                $num = 0;
                foreach ($monthDay as $month => &$days) {
                    if ($days['date'] > $firstdays && $days['date'] < $lastday) {
                        $days['key'] = $num++;
                        $monthDays[] = $days;
                    }
                }
                $numone = ($this->chaifenshuzu($monthDays, 1));
                $monthsbai += ($numone / 30);
                // 算出本季度开启率
                $season = ceil(date('n') / 3);
                $startTimeone = strtotime(date('Y-m-01', mktime(0, 0, 0, ($season - 1) * 3 + 1, 1, date('Y'))));
                $endTimeone = strtotime(date('Y-m-t', mktime(0, 0, 0, $season * 3, 1, date('Y'))));
                $jiduDays = array();
                foreach ($monthDay as $months => &$dayss) {
                    if ($dayss['date'] > $startTimeone && $dayss['date'] < $endTimeone) {
                        $days['key'] = $num++;
                        $jiduDays[] = $dayss;
                    }
                }
                $numtwo = ($this->chaifenshuzu($jiduDays));
                $jidubai += ($numtwo / 120);
                // 算出本年度开启率
                $startTimetwo = strtotime(date('Y-01-01'));
                $endTimetwo = strtotime(date('Y-12-31'));
                $nianDays = array();
                foreach ($monthDay as $nians => &$n) {
                    if ($n['date'] > $startTimetwo && $n['date'] < $endTimetwo) {
                        $n['key'] = $num++;
                        $nianDays[] = $n;
                    }
                }
                $numthree = ($this->chaifenshuzu($nianDays));
                $nianbai += ($numthree / 365);
                // 安装日到现在开启率

                if($filterdate){
                    $startThree = $val['ctime'];
                    $endThree = strtotime($filterdate);
                    $anzhuanDays = array();
                    foreach ($monthDay as $nianss => &$ns) {
                        if ($ns['date'] > $startThree && $ns['date'] < $endThree) {
                            $ns['key'] = $num++;
                            $anzhuanDays[] = $ns;
                        }
                    }
                    $numfours = ($this->chaifenshuzu($anzhuanDays));
                    $this->assign('numfour',$numfours);
                }else{
                    $startThree = $val['ctime'];
                    $endThree = time();
                    $anzhuanDays = array();
                    foreach ($monthDay as $nianss => &$ns) {
                        if ($ns['date'] > $startThree && $ns['date'] < $endThree) {
                            $ns['key'] = $num++;
                            $anzhuanDays[] = $ns;
                        }
                    }
                    $numfour = ($this->chaifenshuzu($anzhuanDays));
                }

                $dayxx = ($endThree - $startThree) / 86400;
                $anbai += ($numfour / $dayxx);
                // 结算周期开启率
                $date = date("Y-m-d");
                $startfour = strtotime(date('Y-m-01', strtotime($date)));
                $endfour = time();
                $anzhuanDa = array();
                foreach ($monthDay as $niansss => &$nss) {
                    if ($nss['date'] > $startfour && $nss['date'] < $endfour) {
                        $nss['key'] = $num++;
                        $anzhuanDa[] = $nss;
                    }
                }
                // 结算周期开启天次
                $numfive = ($this->chaifenshuzu($anzhuanDa));
                $hahdaya += $numfive;
                $dayxxx = ($endfour - $startfour) / 86400;
                $anbais += ($numfive / $dayxxx);

                // 算出某一天开启次数
            }
        }
        $this->assign('rooms', $HotelRoom);
        $this->assign('monthsbai', filter_money($monthsbai));
        $this->assign('jidubai', filter_money($jidubai));
        $this->assign('nainbai', filter_money($nianbai));
        $this->assign('anbai', filter_money($anbai));
        $this->assign('anbais', filter_money($anbais));
        $this->assign('hahdats', $hahdaya);

        $this->assign('time', time());
        $this->assign('date', $date);
        $this->assign('numone',$numonese);
        $this->display('getDevicesInfo');
    }

    /**
     * 拆分数组
     * @param $data
     * @param $type
     */
    public function chaifenshuzu($data)
    {
        $num = 0;
        // 规则从今天14点到明天14点
        foreach ($data as $key => $val) {
            // 截取字符串,开始到结束
            if (substr($val['fulldate'], 11, 2) == 14 && substr($data[$key + 24]['fulldate'], 11, 2) == 14) {
                $num++;
            }
        }
        return $num;
    }
}