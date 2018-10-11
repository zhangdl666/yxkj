<?php
/**
 * 模拟定时器
 * @author baddl
 * @datetime 2017-11-07 10:24
 */
namespace Pc\Server;

class Timing{
	/**
	 * 生成保养工单
	 * @param  string  $sno  安装工单号
	 * @return boolen
	 */
	public static function create_upkeep($sno){
		M('OperOrder')->startTrans();
		//查找安装工单数据
		$oo_info = M('OperOrder')->field('id,h_id,hc_id,now_nume')->where(array('sno'=>$sno,'status'=>4))->find();
		$by_sno = get_reimbursement_sn();
		$oo_data['sno'] 	= $by_sno;
		$oo_data['h_id']	= $oo_info['h_id'];
		$oo_data['hc_id']	= $oo_info['hc_id'];
		$oo_data['num'] 	= $oo_info['now_nume'];
		$oo_data['nume'] 	= $oo_info['now_nume'];
		$oo_data['ctime'] 	= time();
		$oo_data['type']  	= 2;
		$oo_data['status']	=1;

		/* 平台工程经理做处理 */
		$userid_arr = M('User')->field('id')->where(array('role_id'=>5,'status'=>1))->select();
		$user_ids = implode(',', i_array_column($userid_arr,'id'));
		$oper_url = 'EquipmentUpkeep/index';
		has_oper('您有一条保养工单编号：'.$by_sno.' 待处理',$user_ids,$oper_url,'待处理保养工单');

		$oo_result = M('OperOrder')->add($oo_data);

		//安装工单对应房间数据
		$oi_info = M('OperInfo')->where(array('oo_id'=>$oo_info['id']))->select();
		foreach ($oi_info as $value) {
			unset($value['id'],$value['etime'],$value['eo_id']);
			$data = $value;
			$data['oo_id'] = $oo_result;
			$data['ctime'] = time();
			$data['status']= 2;
			$oi_data[] = $data;
		}
		$oi_result = M('OperInfo')->addAll($oi_data);

		if($oo_result && $oi_result){
			M('OperOrder')->commit();
			return $oo_data['sno'];
		}else{
			M('OperOrder')->rollback();
			return false;
		}
	}


	/**
	 * 生成结算单
	 */
	public static function create_return_money($sno){
		//$where = array('oorder.type'=>1,'oorder.status'=>4,'einstall'=>array('elt',time()),'etime'=>array('egt',time()));
		$cm_list  = D('HCOperorderView')->where($where)->select();
		foreach ($cm_list as $cmval) {
			//安装合同工单下所有房间
			$rlist = M('OperInfo')->field('equipment_sno')->where(array('oo_id'=>$cmval['oo_id']))->select();
			$days = 0;
			foreach ($rlist as $value) {
				$use_data = RunInfoModel::getDevicesTime($value['equipment_sno'],date('Y-m-d',strtotime(date('Y-m-d')." -1 day")));
				$days += $use_data['monthDay'];
			}

			//共享
			if($cmval['at_type'] == 1){
				//结算价格
				//上一月设备使用天次
				$rprice = $days*$cmval['at_price'];
				$rmoney_data['sno']  = get_reimbursement_sn();
				$rmoney_data['h_id']  = $cmval['h_id'];
				$rmoney_data['hc_id'] = $cmval['id'];
				$rmoney_data['at_id'] = $cmval['at_id'];
				$rmoney_data['ls_id'] = $cmval['ls_id'];
				$rmoney_data['num']	  = $days;
				$rmoney_data['rtime'] = strtotime(date('Y-m').'-3');
				$rmoney_data['rprice']= $rprice;
				$rmoney_data['etime'] = date('Y-m',strtotime(date('Y-m-d')." -1 day"));
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
			else{
				//上一月总天数
				$month_days = date('t',strtotime(date('Y-m-d')." -1 month"));

				/* 是否上一个月安装的工单 */
				$date_time = date('Y-m',strtotime(date('Y-m-d')." -1 month"));
				$date_time = strtotime($date_time.'-1');
				//上月安装
				if($date_time > $cmval['oo_etime'] && $cmval['oo_etime'] > strtotime($date_time.'-'.$month_days.' 23:59:59')){
					$now_day = date('d',$cmval['oo_etime']);
					$month_days -= $now_day; 
				}
				
				//房间数
				//结算价格
				$rprice = $month_days*$cmval['now_nume']*$cmval['at_price'];
				$rmoney_data['sno']  = get_reimbursement_sn();
				$rmoney_data['h_id']  = $cmval['h_id'];
				$rmoney_data['hc_id'] = $cmval['id'];
				$rmoney_data['at_id'] = $cmval['at_id'];
				$rmoney_data['ls_id'] = $cmval['ls_id'];
				$rmoney_data['num']	  = $days;
				$rmoney_data['rtime'] = strtotime(date('Y-m').'-3 23:59:59');
				$rmoney_data['rprice']= $rprice;
				$rmoney_data['etime'] = date('Y-m',strtotime(date('Y-m-d')." -1 day"));
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


		/*
		insert into yx_return_money(sno,h_id,hc_id,at_id,ls_id,num,rtime,rprice,etime,ctime) value('20171107152107549465','205','12','18','1',1,'1509984000','50000','2017-11','1510038937');
		insert into yx_return_money(sno,h_id,hc_id,at_id,ls_id,num,rtime,rprice,etime,ctime) value('20171107152107549466','205','12','18','1',1,'1509984000','100000','2017-11','1510038237');
		insert into yx_return_money(sno,h_id,hc_id,at_id,ls_id,num,rtime,rprice,etime,ctime) value('20171107152107549467','205','12','18','1',1,'1509984000','200000','2017-11','1510038157');
		*/
	}

}