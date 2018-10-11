<?php
/**
 * 造模拟统计数据
 * @author baddl
 * @datetime 2017-12-11 10:50
 */
 namespace Pc\Controller;
 use Think\Controller;

 class CreateDataController extends Controller{
 	/**
 	 * 设备最新一次状态
 	 */
 	public function deviceData(){
 		//$mac='D0BAE42B0974';
 		$mac = I('get.mac');
 		$arr = array();
 		$device_info = M('DeviceRunInfo1')->field('deviceid,mac,online,power,speed,model,timed,timeopen,timeoff')->where(array('mac'=>$mac))->order('id desc')->find();
 		$data = M('DeviceRunHistory')->field('pm25,co2,tvoc,temp,humi,date as updated')->where(array('mac'=>$mac))->order('id desc')->find();
 		if($device_info || $data){
 			$arr['code'] = 200;
 			$arr['message'] = '';
 			$arr['devices']['info'] = $device_info;
 			$arr['devices']['data'] = $data;
 		}else{
 			$arr['code'] = 0;
 			$arr['message'] = '数据获取失败';
 		}

 		echo json_encode($arr);
 		return;
 	}

 	/**
 	 * 以天为单位的数据接口
 	 */
 	public function days(){
 		//$mac='D0BAE42B0974';
 		$mac = I('get.mac');
 		$day = I('get.days')<30 ? I('get.days') : 30;
 		$daytime = strtotime(date("Y-m-d",strtotime("-".$day." day")));
 		$days = M('DeviceRunHistory')->field('deviceid,pm25,apm25,co2,tvoc,temp,humi,date,fulldate')->where(array('mac'=>$mac,'date'=>array('egt',$daytime)))->order('date asc')->select();
 		if($days){
 			$arr['code'] = 200;
 			$arr['message'] = '';
 			$arr['deviceid']= $days[0]['deviceid'];
 			$arr['mac']= $mac;
 			foreach ($days as $value) {
 				$stime_format = date('Y-m-d',$value['date']);
				$stime = strtotime($stime_format);
				$etime = $stime*1+86400;
 				foreach ($days as $val){
 					if($val['date']>=$stime && $val['date']<=$etime){
	 					unset($val['deviceid']);
	 					$data[] = $val;
	 					break;
	 				}
 				}
 			}
 			$arr['days'] = $data;
 		}else{
 			$arr['code'] = 0;
 			$arr['message'] = '获取失败';
 		}

 		echo json_encode($arr);
 		return;
 	}

 	/**
 	 * 以小时为单位的数据
 	 */
 	public function hours(){
 		//$mac='D0BAE42B0974'
 		$mac = I('get.mac');
 		$hour= I('get.hours')<168 ? I('get.hours') : 168;
 		$hourtime = strtotime(date("Y-m-d H",strtotime("-".$hour." hours")));
 		$hours = M('DeviceRunHistory')->field('deviceid,pm25,apm25,co2,tvoc,temp,humi,date,fulldate')->where(array('mac'=>$mac,'date'=>array('egt',$hourtime)))->order('date asc')->select();
 		if($hours){
 			$arr['code'] = 200;
 			$arr['message'] = '';
 			$arr['deviceid']= $hours[0]['deviceid'];
 			$arr['mac']= $mac;
 			foreach ($hours as $value) {
 				$stime_format = date('Y-m-d',$value['date']);
				$stime = strtotime($stime_format);
				$etime = $stime*1+3600;
 				foreach ($hours as $val){
 					if($val['date']>=$stime && $val['date']<=$etime){
	 					unset($val['deviceid']);
	 					$data[] = $val;
	 					break;
	 				}
 				}
 			}
 			$arr['hours'] = $data;
 		}else{
 			$arr['code'] = 0;
 			$arr['message'] = '获取失败';
 		}

 		echo json_encode($arr);
 		return;
 	}

 	/**
 	 * 获取设备某时间段的信息
 	 */
 	public function device_alldata(){
 		/*$mac='D0BAE42B0974';
 		$startime = '1505145600';
 		$endtime = '1505231999';*/
 		$mac = I('get.mac');
 		$startime = I('get.starttime');
 		$endtime = I('get.endtime');

 		$alldata = M('DeviceRunHistory')->field('deviceid,pm25,apm25,co2,tvoc,temp,humi,date,fulldate')->where(array('mac'=>$mac,'date'=>array('between',$startime.','.$endtime)))->order('date asc')->select();
 		if($alldata){
 			$arr['code'] = 200;
 			$arr['message'] = '';
 			$arr['deviceid']= $alldata[0]['deviceid'];
 			$arr['mac']= $mac;
 			foreach ($alldata as $value) {
				unset($value['deviceid']);
				$data[] = $value;
 			}
 			$arr['data'] = $data;
 		}else{
 			$arr['code'] = 0;
 			$arr['message'] = '获取失败';
 		}

 		echo json_encode($arr);
 		return;
 	}

 	/**
 	 * 获取设备信息
 	 */
 	public function deviceInfo(){
 		//$mac='D0BAE42B0974'
 		$mac = I('get.mac');

 		$arr['code'] = 200;
 		$arr['message'] = '';
 		$device = M('DeviceRunInfo')->where(array('mac'=>$mac))->find();
 		if($device){
 			$arr['devices'][] = $device;
 		}else{
 			$arr['devices'] = '';
 			$arr['message'] = '获取失败';
 		}

 		echo json_encode($arr);
 		return;
 	}

 	/**
 	 * 获取时间段
 	 */
 	public function deviceLoginLog(){
 		//$mac='D0BAE42B0974'
 		$mac = I('post.mac');
 		
 		$device = M('DeviceOffon')->field('time,type')->where(array('mac'=>$mac))->select();
 		if($device){
 			$arr = $device;
 		}else{
 			$arr = array();
 		}

 		echo json_encode($arr);
 		return;
 	}
 }