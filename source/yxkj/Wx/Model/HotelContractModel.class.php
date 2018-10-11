<?php
/**
 * HotelContractModel.class.php
 * 后台 合同模型
 * @author baddl
 * @date   2017-09-19 11:11
 */
namespace Wx\Model;
use Wx\Model\BaseModel;

class HotelContractModel extends BaseModel{
	//添加酒店工作人员的验证规则
    protected $_validate = array(
        array('name', 'require', '合同名称不能为空'),
        array('stime', 'require', '请选择合同开始时间'),
        array('etime', 'require', '请选择合同结束时间'),
        array('rsinstall', 'require', '请选择预约安装开始时间'),
        array('reinstall', 'require', '请选择预约安装结束时间'),
        array('maintenance_id', 'require', '请选择保养频率'),
        array('at_id', 'require', '请选择结算模式'),
        array('ls_id', 'require', '请选择滞纳金比例'),
        array('ls_remark', 'require', '请选择产生滞纳金条件'),
        array('img', 'require', '请上传合同附件'),
    );

	/**
	 * 对数据进行处理
	 */
    protected function _handleRows(&$re_datas){
		//酒店所有类型
		$htype = M('HotelType')->field('id,name')->select();
		foreach ($re_datas['datas'] as &$value) {
			foreach ($htype as $htval) {
				if($htval['id'] == $value['ht_id']){
					$value['ht_name'] = $htval['name'];
					break;
				}
			}

			//酒店图片
			if($value['h_img']){
				$h_imgs = explode(',', $value['h_img']);
				$value['h_img'] = $h_imgs[0];
			}			
		}
	}

	/**
	 * 我已认领酒店
	 */
	public function getHotel(){
		$hotels = D('HotelGetHotelView')->where(array('hotelg.sale_id'=>session('USERINFO.id'),'hotelg.is_default'=>'1','hotel.status'=>1))->select();
		return $hotels;
	}

	/**
	 * 修改酒店信息
	 */
	public function upHotel($data,$ht_arr){
		$hmodel = M('Hotel');
        /*if(!checkMobile($data['tell'])){
            return false;
        }*/
		$hmodel->startTrans();
		$hid = $data['h_id'];
		$uph_user = $data['uhoteluser'];
		unset($data['h_id'],$data['uhoteluser']);
		/* 是否有变更 */
		$hwhere = $data;
		$hwhere['id'] = $hid;
		$is_exsits = $hmodel->where($hwhere)->getField('id');
		$re_sult = true;
		if(!$is_exsits){
			$re_sult = $hmodel->where(array('id'=>$hid))->save($data);
			if(!$re_sult){
				$this->rollback();
				return false;
			}
		}

		$re_sult1 = true;
		if(empty($uph_user)){
			/* 更新酒店房间类型数量 */
			M('HotelRoomType')->where(array('h_id'=>$hid))->delete();
			$re_sult1 = M('HotelRoomType')->addAll($ht_arr);
		}

		if($re_sult && $re_sult1){
			$hmodel->commit();
			return true;
		}else{
			$hmodel->rollback();
			return false;
		}
	}

	/**
	 * 对添加/编辑数据进行操作
	 */
	public function operation(){
		$data = I('post.');
		$id = $data['id'];
		//合同开始时间
		//合同结束时间
		/* 合同周期 */
		if($data['stime'] >= $data['etime']){
			$this->error = '合同周期时间不正确';
			return false;
		}
		//用-分成数组
		$stime = explode('-', $data['stime']);
		$etime = explode('-', $data['etime']);
		//通过年,月差计算月份差
		$months = ($etime[0] - $stime[0]) * 12 + $etime[1] - $stime[1];
		if($etime[0]==$stime[0] && $etime[1] == $stime[1] ){
			$this->data['period'] = 1;
		}else if($etime[2]>$stime[2]){
			$this->data['period'] = $months*1 + 1;
		}else{
			$this->data['period'] = $months;
		}

		/* 产生滞纳金条件 */
		if(empty($data['ls_remark'])){
			$this->error = '请选择产生滞纳金条件';
			return false;
		}

		/* 签约客房 */
		for($i=0; $i < count($data['rt_id']); $i++) { 
			if(empty($data['r_num'][$i]) || empty($data['e1_num'][$i]) || empty($data['e2_num'][$i])){
				$this->error = '请完善签约客房';
				return false;
				exit;
			}
			/* 酒店合同与客房数、设备数 */
			$hcre_arr[] = array('h_id'=>$data['h_id'],'rt_id'=>$data['rt_id'][$i],'r_num'=>$data['r_num'][$i],'e1_id'=>$data['e1_id'][$i],'e1_num'=>$data['e1_num'][$i],'e2_id'=>$data['e2_id'][$i],'e2_num'=>$data['e2_num'][$i]);
			$rnum_count += $data['r_num'][$i]*1;
			$rnum_counte += $data['e2_num'][$i]*1;
		}
		
		$this->startTrans();
		$this->data['stime'] = strtotime($this->data['stime']);
		$this->data['etime'] = strtotime($this->data['etime']);
		$this->data['rsinstall'] = strtotime($this->data['rsinstall']);
		$this->data['reinstall'] = strtotime($this->data['reinstall']);
		/* 查找滞纳金比例 */
		//$ls_num = M('latefees_scale')->getFieldById($data['ls_id'],'num');
		//$this->data['ls_remark'] = '次月第四个工作日0点未收到打款，产生滞纳金，金额为本账期应收金额的'.$ls_num.'‱，按天累加';
			
		//查找模式
		$atype_type = M('AccountsType')->getFieldById($this->data['at_id'],'type');
		if($atype_type == 3){
			$month_price = $this->data['month_price'];
			if($month_price <= 0){
				$this->error = '请输入月租费用';
				return false;
			}
		}
//月租
if(empty($data['month_price'])){
	$this->data['month_price'] = 0;
}

		//添加
		if(empty($id)){
			$this->data['ctime'] = time();
			$this->data['sno']   = get_reimbursement_sn();
			//查看酒店是否签约
			$is_sign_hotel = $this->where(array('h_id'=>$data['h_id']))->getField('id');

			$re_status = $this->add();
			if(!$re_status){
				return false;
			}
			$hcid = $re_status;
			for ($j=0; $j < count($hcre_arr); $j++) { 
				$hcre_arr[$j]['hc_id'] = $hcid;
			}
			
			$re_status = M('HcRoomEquipment')->addAll($hcre_arr);
			M('Hotel')->where(array('id'=>$data['h_id']))->save(array('is_sign'=>1));
			
			if(!$re_status){
				return false;
			}
			
			//安装工单
			$oo_sno = get_reimbursement_sn();
			$oo_data['sno'] 	= $oo_sno;
			$oo_data['h_id'] 	= $data['h_id'];
			$oo_data['hc_id'] 	= $hcid;
			$oo_data['num'] 	= $rnum_count;
			$oo_data['nume'] 	= $rnum_counte;
			$oo_data['ctime'] 	= time();
			$oo_data['rtime'] 	= strtotime($data['rsinstall']);
			M('OperOrder')->add($oo_data);

			//销售人员信息扩展
			$room_num = M('SaleExt')->where(array('u_id'=>session('USERINFO.id')))->getField('room_num');
			$all_rcount = $room_num+$rnum_count;
			//渠道等级
			$channel_list = M('ChannelLevel')->field('id,room_num')->order('room_num asc')->select();
			foreach ($channel_list as $value) {
				if($all_rcount >= $value['room_num']){
					$cl_id = $value['id'];
					continue;
				}
				break;
			}
            //$hc_num=M('SaleExt')->where(array('u_id'=>session('USERINFO.id')))->getField('hc_num');

            $sale_data['room_num'] = $all_rcount;
			$sale_data['hc_num']= array('exp','hc_num+1');
			$sale_data['cl_id'] = $cl_id;
			if(!$is_sign_hotel){
				$sale_data['hotel_num'] = array('exp','hotel_num+1');
			}
			
			M('SaleExt')->where(array('u_id'=>session('USERINFO.id')))->save($sale_data);

			/* 添加消息 */
			//平台工程经理
			$userid_arr = M('User')->field('id')->where(array('role_id'=>5,'status'=>1))->select();
			if($userid_arr){
				$user_ids = implode(',', i_array_column($userid_arr,'id'));
				$oper_title   = '待处理安装工单';
				$oper_url	  = 'EquipmentInstall/index';
				$oper_content = '您有一条安装工单编号：'.$oo_sno.' 待分配';
				has_oper($oper_content,$user_ids,$oper_url,$oper_title);
			}
		}
		//编辑
		else{
			/* 签约客房 */
			M('HcRoomEquipment')->where(array('hc_id'=>$data['id']))->delete();
			for ($j=0; $j < count($hcre_arr); $j++) { 
				$hcre_arr[$j]['hc_id'] = $data['id'];
			}
			
			$re_status = M('HcRoomEquipment')->addAll($hcre_arr);
			if(!$re_status){
				$this->rollback();
				return false;
			}

            $is_exsits = $this->where($this->data)->getField('id');
            if(!$is_exsits){
            	$re_status = $this->save();
            }else{
            	$re_status = true;
            }
			
			
			M('OperOrder')->where(array('hc_id'=>$id))->save(array('num'=>$rnum_count,'nume'=>$rnum_counte,'rtime'=>strtotime($data['rsinstall'])));
		
			//销售人员信息扩展
			$room_num = M('SaleExt')->where(array('u_id'=>session('USERINFO.id')))->getField('room_num');
			$all_rcount = ($room_num+$rnum_count)-$data['or_num'];
			//渠道等级
			$channel_list = M('ChannelLevel')->field('id,room_num')->order('room_num asc')->select();
			foreach ($channel_list as $value) {
				if($all_rcount >= $value['room_num']){
					$cl_id = $value['id'];
					continue;
				}
				break;
			}
			$sale_data['room_num'] = $all_rcount;
			$sale_data['cl_id'] = $cl_id;
			M('SaleExt')->where(array('u_id'=>session('USERINFO.id')))->save($sale_data);
		}

		if($re_status){
			$this->commit();
		}else{
			$this->rollback();
		}

		return $re_status;
	}

	/**
	 * 统计回款情况
	 * @param  int  $hcid  合同ID
	 */
	public function return_info($hcid){
		$rmwhere = array('hc_id'=>$hcid);

		//统计回款情况
		$rm_info = M('ReturnMoney')->field('rprice,price,mtime,mprice,status')->where($rmwhere)->select();
		$rm_num = 0;
		$rm_price= 0;
		$nrm_num = 0;
		$nrm_price = 0;
		foreach ($rm_info as $rmval) {
			//已回款金额、次数
			if($rmval['status'] == 3){
				$rm_price = $rm_price*1+$rmval['price'];
				++$rm_num;
			}
			//逾期未缴次数、逾期金额
			elseif($rmval['status'] == 1){
				$nrm_price = $nrm_price*1+$rmval['rprice'];
				++$nrm_num;
			}	
		}
		return array('rm_price'=>number_format($rm_price,2),'rm_num'=>$rm_num,'nrm_price'=>number_format($nrm_price,2),'nrm_num'=>$nrm_num);
	}

	/**
     * 获取数据
     * @param  array  $data
     * @return array
     */
    public function getRmList($model,$wheres=null,$order_str=null){
        $datas = $this->getDatas($model,$wheres,1,$order_str);
        session('MODEL',$model);
        session('WHERES',$wheres);
        session('ORDER',$order_str);
        //>>6.分配数据
        $re_datas = $this->limitDatas($datas,1,1);

        //对数据进行处理
        if($re_datas){
            $this->_rmHandleRows($re_datas);
        }

        //>>6.1序列化保存在session里面
        $datas = serialize($datas);
        session('DATAS', $datas);
        return $re_datas;
    }

    /**
     * ajax获取加载数据
     * @param $key 页码
     * @param $num 加载个数
     * @return array
     */
    public function showMoreRmDatas($key, $num = 10){
        $data = session('DATAS');
        $data = unserialize($data);
        $page = I('get.page') > 1 ? I('get.page') : 1;
        $get_datas = $this->limitDatas($data, $page, $key, $num);
        //对数据进行处理
        if($get_datas){
            $this->_rmHandleRows($get_datas);
        }
        return $get_datas;
    }

	/**
	 * 对数据进行处理
	 */
	protected function _rmHandleRows(&$re_datas){
		foreach ($re_datas['datas'] as &$value) {
			if($value['status'] == 1){
				$value['status'] = '待结算';
			}elseif($value['status'] == 2){
				$value['status'] = '待确认';
			}else{
				$value['status'] = '已结算';
			}

			if($value['type'] == 1){
				$value['type'] = '共享'.$value['at_price'].'元/天次';
			}elseif($value['type'] == 2){
				$value['type'] = '租赁'.$value['at_price'].'元/天';
			}else{
				$value['type'] = '月租'.$value['at_price'].'元/月';
			}

			$value['rtime'] = date('Y-m-d',$value['rtime']);
			if(!empty($value['time'])){
				$value['time']  = date('Y-m-d',$value['time']);
			}
			
		}
	}

	/**
	 * 保养维修列表
	 * @param  array  $oowhere  条件
	 */
	public function get_oorder_list($oowhere){
		$list = D('OoUserView')->where($oowhere)->order('oorder.ctime desc')->select();
		foreach ($list as &$value) {
			if($value['stime'] && $value['etime']){
	        	$stime = date('Y-m-d',$value['stime']);
	        	$etime = date('Y-m-d',$value['etime']);
	        	if($etime == $stime){
	        		$value['oper_time'] = $stime;
	        	}else{
	        		$value['oper_time'] = $stime.'至'.$etime;
	        	}
	        }elseif($value['stime']){
	        	$value['oper_time'] = $stime;
	        }
		}
		return $list;
	}
}