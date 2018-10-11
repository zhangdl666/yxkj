<?php
/**
 * TimingOperController.class.php
 * 定时执行
 * @author baddl
 * @date   2017-10-16 17:22
 */
namespace Pc\Controller;
use Think\Controller;
use Pc\Model\HotelGetModel;
use Pc\Model\HotelModel;
use Pc\Model\RunInfoModel;
use Pc\Server\Http;

class TimingOperController extends Controller{

	//是否生成保养工单
	public function create_upkeep(){
		$where = array('oorder.type'=>1,'oorder.status'=>4,'hc.etime'=>array('egt',time()));
		$cu_list  = D('HCOperordermView')->where($where)->select();
		foreach($cu_list as $key => $value){
			$d = floor((time()-$value['einstall'])/3600/24);
			if($d%$value['num'] == 0){
				$ooid_arr[]=  $value['oo_id'];
				$sno	   = get_reimbursement_sn();
				$sno_arr[] = $sno; 
				$data['sno'] = $sno;
				$data['h_id']= $value['h_id'];
				$data['hc_id'] = $value['id'];
				$data['num'] = $value['now_nume'];
				$data['nume'] = $value['now_nume'];
				$data['ctime'] = time();
				$data['type'] = 2;
				$order_data[] = $data;

				/* 平台工程经理做处理 */
				$userid_arr = M('User')->field('id')->where(array('role_id'=>5,'status'=>1))->select();
				$user_ids = implode(',', i_array_column($userid_arr,'id'));
				$oper_url = 'EquipmentUpkeep/index';
				has_oper('您有一条保养工单编号：'.$sno.' 待处理',$user_ids,$oper_url,'待处理保养工单');
			}
		}
		$re_sult = M('OperOrder')->addAll($order_data);

		//应保养的房间
		for($i=0; $i < count($ooid_arr); $i++) { 
			$oi_list = M('OperInfo')->field('equipment_sno,rt_id,floor,room_sno,is_window,orientation,place,e_id')->where(array('oo_id'=>$ooid_arr[$i]))->select();
			$ooid    = M('OperOrder')->getFieldBySno($sno_arr[$i],'id');
			foreach($oi_list as &$oival){
				$oival['oo_id'] = $ooid;
				$oival['ctime'] = time();
				$oival['status']= 2;
			}
			M('OperInfo')->addAll($oi_list);
		}

		$hcid_arr = i_array_column($order_data,'hc_id');
		$hcids = implode(',', $hcid_arr);
		if($re_sult){
			$hcids .= '成功生成保养单';
		}else{
			$hcids .= '生成保养单失败';
		}
		file_put_contents('./create_upkeep.txt', date('Y-m-d').'合同ID:'.$hcids."\r\n",FILE_APPEND);
	}

	/**
	 * 结算工单  根据设备使用情况计算出合同本月应缴费用
	 */
	public function clearing_money(){
		$now_time_d = Date('d');
		if($now_time_d >3){
			die('当前还未到结算周期');
		}

		$where = array('oorder.type'=>1,'oorder.status'=>4,'einstall'=>array('elt',time()),'etime'=>array('egt',time()));
		$cm_list  = D('HCOperorderView')->where($where)->select();
		
		//今天是否是一个月的开始
		$today = date('d');

		//上一月总天数
		$month_days = date('t',strtotime(date('Y-m-d H:i:s')." -1 hour"));
		/* 上一个月日期 */
		$date_time = date('Y-m',strtotime(date('Y-m-d H:i:s')." -1 hour"));

		foreach ($cm_list as $cmval) {
			//共享
			if($cmval['at_type'] == 1){
				//安装合同工单下所有房间
				//$rlist = M('OperInfo')->field('equipment_sno')->where(array('oo_id'=>$cmval['oo_id']))->select();
				
				/*foreach ($rlist as $value) {
					$use_data = RunInfoModel::getDevicesTime($value['equipment_sno'],date('Y-m-d',strtotime(date('Y-m-d H:i:s')." -1 hour")));
					$days += $use_data['monthDay'];
				}*/
				$start_time = $date_time.'-1';
				$end_time = $date_time.'-'.$month_days;
				$days = M('DeviceOperNum')->where(array('hc_id'=>$cmval['id'],'oper_time'=>array('between',$start_time.','.$end_time)))->count();
				//结算价格
				//上一月设备使用天次
				$rprice = $days*$cmval['at_price'];
				$rpsno = get_reimbursement_sn();
				$rmoney_data['sno']   = $rpsno;
				$rmoney_data['h_id']  = $cmval['h_id'];
				$rmoney_data['hc_id'] = $cmval['id'];
				$rmoney_data['at_id'] = $cmval['at_id'];
				$rmoney_data['ls_id'] = $cmval['ls_id'];
				$rmoney_data['num']	  = $days;
				$rmoney_data['rtime'] = strtotime(date('Y-m').'-3');
				$rmoney_data['rprice']= $rprice;
				$rmoney_data['etime'] = date('Y-m',strtotime(date('Y-m-d H:i:s')." -1 hour"));
				$rmoney_data['ctime'] = time();
				if($rprice == 0){
					$hc_ids1 .= $hc_ids1 ? ','.$cmval['id'] : $cmval['id'];
					$rmoney_data['price'] = 0;
					$rmoney_data['time'] = time();
					$rmoney_data['status'] = 3;
					$data1[] = $rmoney_data;
					unset($rmoney_data);
				}else{
					$hc_ids .= $hc_ids ? ','.$cmval['id'] : $cmval['id'];
					$data[] = $rmoney_data;
				}
			}
			//租赁
			elseif($cmval['at_type'] == 2){
				//上月安装
				if($cmval['oo_etime'] > strtotime($date_time.'-1') && $cmval['oo_etime'] < strtotime($date_time.'-'.$month_days.' 23:59:59')){
					$now_day = date('d',$cmval['oo_etime']);
					$month_days = $month_days*1-$now_day*1; 
				}
				//房间数
				//结算价格
				$rprice = $month_days*$cmval['now_nume']*$cmval['at_price'];
				$rpsno = get_reimbursement_sn();
				$rmoney_data['sno']   = $rpsno;
				$rmoney_data['h_id']  = $cmval['h_id'];
				$rmoney_data['hc_id'] = $cmval['id'];
				$rmoney_data['at_id'] = $cmval['at_id'];
				$rmoney_data['ls_id'] = $cmval['ls_id'];
				//$rmoney_data['num']	  = $days;
				$rmoney_data['num']	  = $month_days*$cmval['now_nume'];
				$rmoney_data['rtime'] = strtotime(date('Y-m').'-3 23:59:59');
				$rmoney_data['rprice']= $rprice;
				$rmoney_data['etime'] = date('Y-m',strtotime(date('Y-m-d')." -1 hour"));
				$rmoney_data['ctime'] = time();

				if($rprice == 0){
					$hc_ids1 .= $hc_ids1 ? ','.$cmval['id'] : $cmval['id'];
					$rmoney_data['price'] = 0;
					$rmoney_data['time'] = time();
					$rmoney_data['status'] = 3;
					$data1[] = $rmoney_data;
					unset($rmoney_data);
				}else{
					$hc_ids .= $hc_ids ? ','.$cmval['id'] : $cmval['id'];
					$data[] = $rmoney_data;
				}
			}
			//月租
			elseif($today == 1){
				//上月安装
				if($cmval['oo_etime'] > strtotime($date_time.'-1') && $cmval['oo_etime'] < strtotime($date_time.'-'.$month_days.' 23:59:59')){
					$now_day = date('d',$cmval['oo_etime']);
					$month_days = $month_days*1-$now_day*1; 
				}

				//房间数
				//结算价格
				$rprice = $cmval['month_price'];
				$rpsno = get_reimbursement_sn();
				$rmoney_data['sno']   = $rpsno;
				$rmoney_data['h_id']  = $cmval['h_id'];
				$rmoney_data['hc_id'] = $cmval['id'];
				$rmoney_data['at_id'] = $cmval['at_id'];
				$rmoney_data['ls_id'] = $cmval['ls_id'];
				//$rmoney_data['num']	  = $days;
				$rmoney_data['num']	  = $month_days*$cmval['now_nume'];
				$rmoney_data['rtime'] = strtotime(date('Y-m').'-3 23:59:59');
				$rmoney_data['rprice']= $rprice;
				$rmoney_data['etime'] = date('Y-m',strtotime(date('Y-m-d')." -1 hour"));
				$rmoney_data['ctime'] = time();

				if($rprice == 0){
					$hc_ids1 .= $hc_ids1 ? ','.$cmval['id'] : $cmval['id'];
					$rmoney_data['price'] = 0;
					$rmoney_data['time'] = time();
					$rmoney_data['status'] = 3;
					$data1[] = $rmoney_data;
					unset($rmoney_data);
				}else{
					$hc_ids .= $hc_ids ? ','.$cmval['id'] : $cmval['id'];
					$data[] = $rmoney_data;
				}
			}

			if($rprice != 0){
				/* 酒店账务经理做处理  */
	            $msg_content = '您有一条结算工单号：'.$rpsno.' 待处理';
	            //酒店财务经理
	            $user_idarr = M('User')->field('id')->where(array('hotel_id'=>$cmval['h_id'],'role_id'=>10,'status'=>1))->select();
	            $user_ids = implode(',', i_array_column($user_idarr,'id'));
	            has_oper($msg_content,$user_ids,'ReturnMoney/index','待处理结算单');
			}
			
			if(count($data1) >= 50){
				$re_sult1 = M('ReturnMoney')->addAll($data1);
				if($re_sult1){
					$hc_ids1 = date('Y-m',strtotime(date('Y-m-d')." -1 day")).'合同ID：'.$hc_ids1.'结算成功';
				}else{
					$hc_ids1 = date('Y-m',strtotime(date('Y-m-d')." -1 day")).'合同ID：'.$hc_ids1.'结算失败';
				}
				file_put_contents('./ReturnMoney.txt',$hc_ids1."\r\n",FILE_APPEND);
				$hc_ids1 = '';
				unset($data1);
			}

			if(count($data) >= 50){
				$re_sult = M('ReturnMoney')->addAll($data);
				if($re_sult){
					$hc_ids = date('Y-m',strtotime(date('Y-m-d')." -1 day")).'合同ID：'.$hc_ids.'结算成功';
				}else{
					$hc_ids = date('Y-m',strtotime(date('Y-m-d')." -1 day")).'合同ID：'.$hc_ids.'结算失败';
				}
				file_put_contents('./ReturnMoney.txt',$hc_ids."\r\n",FILE_APPEND);
				$hc_ids = '';
				unset($data);
			}
		}

		if(!empty($data1)){
			$re_sult1 = M('ReturnMoney')->addAll($data1);
			if($re_sult1){
				$hc_ids1 = date('Y-m',strtotime(date('Y-m-d')." -1 day")).'合同ID：'.$hc_ids1.'结算成功';
			}else{
				$hc_ids1 = date('Y-m',strtotime(date('Y-m-d')." -1 day")).'合同ID：'.$hc_ids1.'结算失败';
			}
			file_put_contents('./ReturnMoney.txt',$hc_ids1."\r\n",FILE_APPEND);
		}

		if(!empty($data)){
			$re_sult = M('ReturnMoney')->addAll($data);
			if($re_sult){
				$hc_ids = date('Y-m',strtotime(date('Y-m-d')." -1 day")).'合同ID：'.$hc_ids.'结算成功';
			}else{
				$hc_ids = date('Y-m',strtotime(date('Y-m-d')." -1 day")).'合同ID：'.$hc_ids.'结算失败';
			}
			file_put_contents('./ReturnMoney.txt',$hc_ids."\r\n",FILE_APPEND);
		}
		
	}

	/**
     *更新逾期时间
     *每天执行一次
     * @author 刘小伟
 	 * @date   2017-10-17 14:02
     */
    public function Beoverdue(){
        $money =  M('ReturnMoney')
            ->alias('o')
            ->join('yx_accounts_type as t on t.id = o.at_id ' )
            ->field('o.*,t.price as type_price')
            -> where(array('o.status' =>1))
            ->select();
        foreach ($money as $key =>$val){
            //如果逾期，则更新
            if($val['rtime'] < time()){
                //逾期天数 = （当前执行时间 -应打款时间）/86400
                $mtime= ceil((time() -$val['rtime']) /86400);
                $latefees_scale_items = M('LatefeesScale')->field('num')->where(array('id'=> $val['ls_id']))->find();
                //滞纳金 =应付总价*滞纳金比例*天数
                $mprice =(($val['rprice'])*( $latefees_scale_items['num']/10000))*$mtime;
                //更新
                $num =  M('ReturnMoney') ->where(array('id'=>$money[$key]['id']))->setField(array('mtime'=>$mtime,'mprice'=>$mprice));
                //写入记录
                $name = M('Hotel') -> where(array('id' =>$money[$key]['id'])) ->field('name') ->find();
                if(is_numeric($num)){
                    $hotel = $name['name'].'逾期时间更新成功,当前已逾期'.$mtime.'天,当前滞纳金'.$mprice.'元';
                    file_put_contents('./Beoverdue.txt', $hotel."\r\n",FILE_APPEND);
                }else{
                    $hotel = $name['name'].'逾期时间更新失败';
                    file_put_contents('./Beoverdue.txt', $hotel."\r\n",FILE_APPEND);
                }
            }
        }
    }

    /**
     * 查询酒店认领时间
     * @return float
     * @Author ' Silent
     */
    public function getCheckTime()
    {
//        G('begin');
        // 查询认领中间表,所有认领酒店
        $where = array();
        $where['is_default'] = array('eq', 1);
        $where['ctime'] = array('neq', '');
        $data = M(HotelGetModel::TABLENAME)->where($where)->field('id')->select();
        foreach ($data as $key => $val) {
            // 查看当前酒店是否认领了
            $status = M(HotelModel::TABLENAME_HOTEL)->where(array('id' => $val))->getField('is_get');
            if ($status == 1) {
                // 算出该酒店,是否在30天内没有签合同
                $data = HotelGetModel::getInfos($val);
                $ctimes = $data['ctime'];
                // 查出该酒店是否签约了合同
                $contract = M('HotelContract')->where(array('h_id' => $val))->order('ctime desc')->find();
                // 没有提交合同
                if (empty($contract)) {
                    // 结束时间
                    $endTime = strtotime(date('Y-m-d H:i:s', strtotime("+30day", $ctimes)));
                    // 还未到30天
                    $nowTime = time();
                    if ($nowTime < $endTime) {
                        $nums = (round(($endTime - $nowTime) / 3600 / 24));
                        return $nums;
                    } else {
                        // 清除酒店信息
                        $HotelUserModel = D(HotelModel::TABLENAME_HOTEL);
                        $HotelUserModel->startTrans();
                        $HotelGetStatus = HotelGetModel::cancelInfo($data['sale_id'], $data['h_id']);
                        $HotelStatus = HotelModel::cancelClaim($data['h_id']);
                        if ($HotelGetStatus && $HotelStatus) {
                            $HotelUserModel->commit();
                        }
                    }
                }
            }
        }
//        G('end');
//        echo G('begin','end').'s';
    }

    /**
     * 每天下午2点把用户和设备进行解绑
     * 04786300FA93
     */
    public function device_unbind(){
    	$unbind_url = 'http://api.bjhike.com/api/v2_unbind';
    	//获取绑定关系
    	$deviceUsers_url = 'http://api.bjhike.com/DeviceUsers';
    	//查找所有已经绑定了的设备的用户
  		$device_list = M('Device')->field('mac,device_id')->select();
    	foreach ($device_list as $value){
    		if($key == 2){
    			break;
    		}
    		$du_data['CompanyToken'] = C('HIKE.companyToken');
			$du_data['DeviceMac'] 	 = $value['mac'];
			$deviceUsers = json_decode(Http::get($deviceUsers_url,$du_data),true);
			foreach($deviceUsers['用户设备关系'] as $k => $v){
				if($v['主客关系'] == '主人'){
					$data['openid'] = $v['openid'];
					break;
				}
			}

			if(empty($data['openid'])){
				//echo '没有主人了';
				continue;
			}
    		$data['companyToken'] = C('HIKE.companyToken');
    		$data['mac'] 	 = $value['mac'];
	  		$data['deviceid']= $value['device_id'];
	  		$re_data = json_decode($this->curl_post_ssl($unbind_url,json_encode($data)));
	  		//M('Device')->where(array('uid'=>$value['uid']))->save(array('uid'=>null));
	  		if($re_data->code != 200){
	  			file_put_contents('./err_dunbind.txt', "\r\n".var_export($data,true).$re_data->message,FILE_APPEND);
	  			//echo '失败';
	  			sleep(1);
	  		}else{
	  			//echo '成功';
		  		file_put_contents('./success_dunbind.txt', "\r\n".var_export($data,true).$re_data->message,FILE_APPEND);
		  		sleep(1);
	  		}
    	}
    }

    /**
     * 批量向设备表中写入酒店ID
     */
    public function device_into_hotelid(){
    	$device_list = M('Device')->field('id,mac')->select();
    	foreach ($device_list as $mac_val) {
    		$arr[] = $mac_val;

    		if(count($arr) >= 50){
    			$mac_arr = i_array_column($arr,'mac');
				$re_data = D('OoOinfoView')->field('equipment_sno,h_id')->where(array('equipment_sno'=>array('in',$mac_arr),'oinfo.status'=>1))->select();
				foreach ($re_data as $val) {
					for($i=0; $i < count($arr); $i++) {
						if($val['equipment_sno'] == $arr[$i]['mac']){
							$mac_data['id']  = $arr[$i]['id'];
							$mac_data['h_id']= $val['h_id'];
							M('Device')->save($mac_data);
							break;
						}
					}					
				}
				unset($arr,$mac_arr,$re_data);
			}

			//剩余设备
			if($arr){
				$mac_arr = i_array_column($arr,'mac');
				$re_data = D('OoOinfoView')->field('equipment_sno,h_id')->where(array('equipment_sno'=>array('in',$mac_arr)))->select();
				foreach ($re_data as $val) {
					for($i=0; $i < count($arr); $i++) {
						if($val['equipment_sno'] == $arr[$i]['mac']){
							$mac_data['id']  = $arr[$i]['id'];
							$mac_data['h_id']= $val['h_id'];
							M('Device')->save($mac_data);
							break;
						}
					}					
				}
			}
    	}
    }

    /**
 * @name ssl Curl Post数据
 * php的curl只支持pem格式、der、eng格式
 * @param string $url 接收数据的api
 * @param string $vars 提交的数据
 * @param int $second 要求程序必须在$second秒内完成,负责到$second秒后放到后台执行
 * @return string or boolean 成功且对方有返回值则返回
 */
public function curl_post_ssl($url, $vars){
	$ch = curl_init();
	curl_setopt($ch,CURLOPT_TIMEOUT,$second);
	curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch,CURLOPT_URL,$url);
	curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
	curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,false);
	curl_setopt($ch,CURLOPT_POST, 1);
	curl_setopt($ch,CURLOPT_POSTFIELDS,$vars);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                          
	    'Content-Type: application/json',                                                                                
	    'Content-Length: '.strlen($vars))                                                                       
	);
	$data = curl_exec($ch);
	curl_close($ch);
	if($data)
	    return $data;
	else   
	    return false;
}

/**
 * 每天计算共享模式下设备使用数
 */
public function device_oper_num(){
	//power开机状态 1开机  
	//所有设备
	$device_list = D('OoOiHcAtView')->field('h_id,hc_id,equipment_sno')->where(array('atype.type'=>1))->order('h_id asc')->select();
	/*foreach ($device_list as $value) {
		$new_device[$value['h_id']][] = $value['equipment_sno'];
	}*/

	//昨天开始时间 结束时间
	$before_time = date('Y-m-d',strtotime(date('Y-m-d')." -31 day"));
	$start_time = strtotime($before_time);
	$end_time = strtotime($before_time.' 23:59:59');
	foreach ($device_list as $value) {
		$device_oper_info = M('UploadDeviceInfo_'.$value['h_id'])->field('upload_time,power')->where(array('device_code'=>$value['equipment_sno'],array('upload_time'=>array('between',$start_time.','.$end_time))))->order('upload_time asc')->select();
		$open_device_time = '';
		$oper_num = 0;
		foreach ($device_oper_info as $val) {
			if(empty($open_device_time) && $val['power']==1){
				$open_device_time = $val['upload_time'];
			}elseif($open_device_time && $val['power']==0){
				$oper_num += number_format(($val['upload_time']-$open_device_time)/3600,2);
				$open_device_time = '';
			}
		}
		
		if($open_device_time && $val['power']==1){
			$oper_num += number_format(($val['upload_time']-$open_device_time)/3600,2);
			$open_device_time = '';
		}

		if($oper_num > 1){
			$device_oper_data['hc_id']  = $value['hc_id'];
			$device_oper_data['device'] = $value['equipment_sno'];
			$device_oper_data['num']	= $oper_num;
			$device_oper_data['oper_time'] = $before_time;
			M('DeviceOperNum')->add($device_oper_data);
		}
	}
}
}
