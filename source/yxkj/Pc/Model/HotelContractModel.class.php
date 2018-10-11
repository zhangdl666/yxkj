<?php
/**
 * HotelContractModel.class.php
 * 后台合同管理模型
 * @author baddl
 * @date   2017-09-04 18:03
 */
namespace Pc\Model;
use Pc\Model\BaseModel;
use Pc\Server\PageModel;

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
        array('ls_id', 'require', '请选择滞纳金'),
        array('ls_remark', 'require', '请选择产生滞纳金条件'),
        array('img', 'require', '请上传合同附件'),
    );

	/**
	 * 获取列表数据
	 * @param  array  $wheres 	查询条件
	 * @return array
	 */
	public function getResultList($wheres){
		//签约酒店
		$hids = $this->hcc_where();
		$wheres['hotelc.h_id'] = array('in',$hids);
		$wheres['hotelg.sale_id'] = array('neq',0);
		$count = D('HotelcHotelgHotelView')->where($wheres)->count();

		$listRows = C('PAGE_SIZE');
        $page = new PageModel($count, $listRows);  //自己获取请求中的页面直接使用
        $page->lastSuffix = false;
        $pageHtml = $page->show();

		$re_datas = D('HotelcHotelgHotelView')->field('id,name,h_name,h_tell,uname,mobile,ctime,period,einstall')->where($wheres)->limit($page->firstRow,$page->listRows)->order('hotelc.ctime desc')->select();
		//对数据进行处理
		$this->_handleRows($re_datas);

		return array('pageHtml'=>$pageHtml,'items'=>$re_datas);
	}

	/**
	 * 查询数据具体信息
	 * @param  int  $id 	查询单条数据信息的ID号
	 * @return array
	 */
	public function getInfo($id){
		$re_data = D('HotelcHotelgHotelView')->where(array('hotelc.id'=>$id))->find();
		return $re_data;
	}

	/**
	 * 我已认领酒店
	 */
	public function getHotel(){
		$hotels = D('HotelGetHotelView')->where(array('hotelg.sale_id'=>session('USERINFO.id'),'hotelg.is_default'=>'1','hotel.status'=>1))->select();
		return $hotels;
	}

	/**
	 * 对添加/编辑数据进行操作
	 */
	public function operation(){
		$id = I('post.id');
		$data = I('post.');
		if(empty($data['month_price'])){
			$this->data['month_price'] = 0;
		}
		for($i=0; $i < count($data['rt_id']); $i++) {
			if(empty($data['r_num'][$i]) || empty($data['e1_num'][$i]) || empty($data['e2_num'][$i])){
				$this->error = '请完善签约客房';
				return false;
				exit;
			}
			/* 酒店合同与客房数、设备数 */

			$hcre_arr[] = array('h_id'=>$data['h_id'],'rt_id'=>$data['rt_id'][$i],'r_num'=>$data['r_num'][$i],'e1_id'=>$data['e1_id'][$i],'e1_num'=>$data['e1_num'][$i],'e2_id'=>$data['e2_id'][$i],'e2_num'=>$data['e2_num'][$i]);
			$rnum_count += $data['r_num'][$i]*1;
			$rnum_counte+= $data['e2_num'][$i]*1;
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

		//添加
		if(empty($id)){
			$this->data['ctime'] = time();
			$this->data['sno']   = get_reimbursement_sn();
			/*$this->data['stime'] = strtotime($this->data['stime']);
			$this->data['etime'] = strtotime($this->data['etime']);
			$this->data['rsinstall'] = strtotime($this->data['rsinstall']);
			$this->data['reinstall'] = strtotime($this->data['reinstall']);*/
			/* 查找滞纳金比例 */
			/*$ls_num = M('latefees_scale')->getFieldById($data['ls_id'],'num');
			$this->data['ls_remark'] = '次月第四个工作日0点未收到打款，产生滞纳金，金额为本账期应收金额的'.$ls_num.'‱，按天累加';
			*/
			//查看酒店是否签约
			$is_sign_hotel = $this->where(array('h_id'=>$data['h_id']))->getField('id');
			
			$re_status = $this->add();
			if(!$re_status){
				return false;
			}

			$hcid = $re_status;
			/* 签约客房 */
			/*for($i=0; $i < $data['rt_id']; $i++) { 
				$arr[] = array('h_id'=>$data['h_id'],'hc_id'=>$re_status,'rt_id'=>$data['rt_id'][$i],'r_num'=>$data['r_num'][$i],'e1_id'=>$data['e1_id'][$i],'e1_num'=>$data['e1_num'][$i],'e2_id'=>$data['e2_id'][$i],'e2_num'=>$data['e2_num'][$i]);
			}*/
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
            //$a=M('SaleExt')->where(array('u_id'=>session('USERINFO.id')))->field('hc_num')->find();
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
			//酒店信息是否有改动
			/*$is_h_exsits = M('Hotel')->where($data)->getField('id');
			if(!$is_h_exsits){
				$re_status = M('Hotel')->where(array('id'=>$data['h_id']))->save($data);
				if(!$re_status){
					return false;
				}
			}*/

			/* 酒店客房类型及房数 */
			/*M('HotelRoomType')->where(array('h_id'=>$data['h_id']))->delete();
			for ($j=0; $j < $data['hrt_id']; $j++) { 
				$arr_hrt[] = array('h_id'=>$data['h_id'],'rt_id'=>$data['hrt_id'][$j],'room_num'=>$data['room_num'][$j]);
			}
			if(!empty($arr_hrt)){
				$re_status = M('HcRoomEquipment')->addAll($arr);
				if(!$re_status){
					return false;
				}
			}*/

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


			/*if(!empty($arr)){
				$re_status = M('HcRoomEquipment')->addAll($arr);
				if(!$re_status){
					return false;
				}
			}
*/
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
	 * 合同条件
	 */
	protected function hcc_where(){
		$role_id = session('USERINFO.role_id');
		switch ($role_id) {
			case 2:
				# 平台销售人员
				//签约酒店
				$hid_arr = M('HotelGet')->field('h_id')->where(array('sale_id'=>session('USERINFO.id'),'is_default'=>1))->select();
				$hid_arr = i_array_column($hid_arr,'h_id');
				$hccwhere = implode(',', $hid_arr);
				break;
			/*case 3:
				# 平台销售经理
				//下级销售人员
				$sale_arr = M('User')->field('id')->where(array('parent_id'=>session('USERINFO.id')))->select();
				$sale_arr = i_array_column($sale_arr,'id');
				$saleids = implode(',', $sale_arr);
				//签约酒店
				$hid_arr = M('HotelGet')->field('h_id')->where(array('sale_id'=>array('in',$saleids),'is_default'=>1))->select();
				$hid_arr = i_array_column($hid_arr,'h_id');
				$hccwhere = implode(',', $hid_arr);
				break;*/
			case 10:
				# 酒店财务经理
				//所属酒店
				$hccwhere = M('User')->getFieldById(session('USERINFO.id'),'hotel_id');
				break;
			case 12:
				# 酒店总经理
				//所属酒店
				$hccwhere = M('User')->getFieldById(session('USERINFO.id'),'hotel_id');
				break;
			/*case 9:
				# 平台总经理
			case 1:
				# 平台总经理
				//$rmwhere = array();
				break;*/
			default:
				# 平台总经理
				$hotel_list = M('HotelContract')->field('h_id')->group('h_id')->select();
				$hid_arr = i_array_column($hotel_list,'h_id');
				$hccwhere = implode(',', $hid_arr);
				break;
		}

		return $hccwhere;
	}

	/**
	 * 合同统计信息
	 */
	public function hcCountInfo(){
		//签约酒店ID集合
		$hids = $this->hcc_where();
		$wheres['hotelc.h_id'] = array('in',$hids);
		$wheres['hotelg.sale_id'] = array('neq',0);
		//总合同
		$hc_list = D('HotelcHotelgHotelView')->field('id,stime,etime')->where($wheres)->select();
		//合同IDS
		$hcids_arr = i_array_column($hc_list,'id');
		$hcids = implode(',', $hcids_arr);
		//逾期未缴费
		//$rm_where = array('hc_id'=>array('in',$hcids),'mtime'=>array('gt',0),'status'=>1);
		$rm_where = array('hc_id'=>array('in',$hcids),'rtime'=>array('lt',time()),'price'=>0,'status'=>1);
		$rm_list = M('ReturnMoney')->field('hc_id')->where($rm_where)->group('hc_id')->select();
		$rm_hcids_arr = i_array_column($rm_list,'hc_id');

		//总合同份数
		$all_count = count($hc_list);
		//履约中
		$exsits_count = 0;
		//已逾期
		$nexsits_count= 0;
		//已到期
		$more_count   = 0;
		foreach ($hc_list as $key => $value) {
			//履约中
			if($value['etime'] >= strtotime(date('Y-m-d',time()))){
				if(in_array($value['id'], $rm_hcids_arr)){
					++$nexsits_count;
				}else{
					++$exsits_count;
				}				
			}
			//已到期  if($value['etime'] <= time())
			else{
				++$more_count;
			}
		}

		return array('all_count'=>$all_count,'exsits_count'=>$exsits_count,'nexsits_count'=>$nexsits_count,'more_count'=>$more_count);
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
	 * 获取回款列表数据
	 * @return array
	 */
	public function getRmList($hcid){
		$wheres = array('hc_id'=>$hcid);
		$count = D('RmoneyAccountstView')->where($wheres)->count();

		$listRows = C('PAGE_SIZE');
        $page = new PageModel($count, $listRows);  //自己获取请求中的页面直接使用
        $page->lastSuffix = false;
        $pageHtml = $page->show();

		$re_datas = D('RmoneyAccountstView')->where($wheres)->limit($page->firstRow,$page->listRows)->order('rmoney.ctime desc')->select();
		//对数据进行处理
		$this->_handleRows($re_datas);

		return array('pageHtml'=>$pageHtml,'items'=>$re_datas);
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

		if(empty($uph_user)){
			/* 更新酒店房间类型数量 */
			M('HotelRoomType')->where(array('h_id'=>$hid))->delete();
			$re_sult = M('HotelRoomType')->addAll($ht_arr);
		}

		if($re_sult){
			$hmodel->commit();
			return true;
		}else{
			$hmodel->rollback();
			return false;
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

	/**
	 * 订单信息
	 * @param  int  $id  合同ID
	 * @return array
	 */
	public function order_info($id){
		$info = M('OperOrder')->field('id,sno,num,nume,now_num,now_nume,rtime,img')->where(array('hc_id'=>$id))->find();
		$hc_info = M('HotelContract')->field('h_id,rsinstall,reinstall,sinstall,einstall,install_img')->getById($id);
		$info = array_merge($info,$hc_info);
		return $info;
	}

	/**
	 * 设备及房间信息
	 * @param int  $id  设备保养维修情况ID
	 */
	public function getRoomInfo($id){
		$oper_info = M('OperInfo')->field('oo_id,equipment_sno,rt_id,floor,room_sno,is_window,orientation,place,eo_id,e_id,note')->where(array('id'=>$id))->find();
		//是否有窗
		if($oper_info['is_window'] == 1){
			$oper_info['iswindow'] = '有';
		}else{
			$oper_info['iswindow'] = '无';
		}

		//朝向
		switch ($oper_info['orientation']) {
			case 1:
				$oper_info['orienta'] = '东';
				break;
			case 2:
				$oper_info['orienta'] = '南';
				break;
			case 3:
				$oper_info['orienta'] = '西';
				break;
			default:
				$oper_info['orienta'] = '北';
				break;
		}

		//安装位置
		switch ($oper_info['place']) {
			case 1:
				$oper_info['place'] = '出风口';
				break;
			case 2:
				$oper_info['place'] = '回风口';
				break;
			default:
				$oper_info['place'] = '其他';
				break;
		}

		//房间类型
		$oper_info['rt_name'] = M('RoomType')->getFieldById($oper_info['rt_id'],'name');
		//处理方式
		if($oper_info['eo_id']){
			$oper_info['type_name'] = M('EquipmentOper')->getFieldById($oper_info['eo_id'],'name');
		}
		$oper_info['note'] = $oper_info['note'] ? $oper_info['note'] : '';

		//净化器类型  监控器类型
		$device = M('HcRoomEquipment')->alias('hcre')->join('join yx_oper_order oo on oo.hc_id=hcre.hc_id')
				  ->field('e1_id,e2_id')
				  ->where(array('hcre.rt_id'=>$oper_info['rt_id'],'oo.id'=>$oper_info['oo_id']))
				  ->find();
		$equipment = M('Equipment')->select();
		foreach ($equipment as $value) {
			if($value['id'] == $oper_info['e_id']){
				//空调品牌
				$oper_info['air_name'] = $value['name'];
			}elseif($device['e1_id'] == $value['id']){
				//净化器类型
				$oper_info['e1_name'] = $value['name'];
			}elseif($device['e2_id'] == $value['id']){
				//监控器类型
				$oper_info['e2_name'] = $value['name'];
			}
		}

		//安装时间
		$install_time = M('OperInfo')->where(array('equipment_sno'=>$oper_info['equipment_sno'],'status'=>1))->getField('ctime');
		$oper_info['install_time'] = $install_time ? date('Y-m-d',$install_time) : '';
		
		return $oper_info;
	}
}