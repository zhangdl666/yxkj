<?php
/**
 * HotelContractController.class.php
 * 后台 合同控制器
 * @author baddl
 * @date   2017-09-04 18:02
 */
namespace Pc\Controller;
use Pc\Controller\BaseController;
use Org\Net\Http;
use Pc\Server\NewMessage;

class HotelContractController extends BaseController{
	/**
	 * 初始化 
	 */
	public function _initialize(){
		parent::_initialize();
		$this->placeholder = '请输入归属酒店';
	}

	/**
	 * 新增合同
	 */
	public function add(){
		/* 已认领酒店 */
		$hotels = $this->model->getHotel();
		$this->assign('hotel_items',$hotels);
		
		//添加/编辑展示之前准备数据
		$this->_before_edit_view();
		$this->assign('hid',I('get.hid'));
		$this->assign('back_url',$_SERVER['HTTP_REFERER']);

		$this->display();
	}

	/**
	 * 编辑酒店信息
	 */
	public function edit(){
		/* 所有酒店类型 */
		$hotel_types = D('HotelType')->getList();
		$this->assign('ht_items',$hotel_types);
		//添加/编辑展示之前准备数据
		$this->_before_edit_view();
		
		$hcid = I('get.id');
		$this->assign('hcid',$hcid);
		$hotel_info = $this->hotelInfo($hcid);
		$this->assign($hotel_info);

		/* 所有房间类型 */
		$room_type = D('RoomType')->getList();
		foreach ($room_type as &$value) {
			foreach ($hotel_info['hrt_items'] as $key => $hrtval) {
				//已选中
				if($value['id'] == $hrtval['id']){
					$value['checked']  = 1;
					$value['room_num'] = $hrtval['room_num'];
				}
			}
		}
		$this->assign('rt_items',$room_type);

		$this->display();
	}

	/**
	 * 编辑酒店信息
	 */
	public function edituser(){
		$hcid = I('get.id');
		$this->assign('hcid',$hcid);

		$hotel_info = $this->hotelInfo($hcid);
		$this->assign($hotel_info);

		$this->display();
	}

	/**
	 * 编辑合同信息
	 */
	public function editc(){
		$this->_before_edit_view();		

		$hcid = I('get.id');
		$this->assign('hcid',$hcid);
		$hc_info = $this->contractInfo($hcid);
		if($hc_info['img']){
            $hc_info['himgs'] = explode(',', $hc_info['img']);
        }
		$this->assign($hc_info);

		$hotel_rts = D('HotelrtRoomtView')->where(array('hotelrt.h_id'=>$hc_info['h_id']))->select();
		$this->assign('hotel_rts',$hotel_rts);

		$this->display();
	}

	/**
	 * 酒店信息
	 */
	public function hotelInfo($hcid){
		$hc_info = M('HotelContract')->field('h_id,einstall')->getById($hcid);
		/* 酒店信息 */
		$hotel_info = \Pc\Model\HotelModel::findById($hc_info['h_id']);
		if(!empty($hotel_info['img'])){
			$hotel_info['himgs'] = $hotel_info['img'];
			$hotel_info['img'] = implode(',', $hotel_info['img']);
		}
		/* 酒店房间类型数量 */
		$hotel_info['hrt_items'] = D('HotelrtRoomtView')->where(array('h_id'=>$hc_info['h_id']))->select();
		$hotel_info['einstall']  = $hc_info['einstall'];
		return $hotel_info;
	}

	/**
	 * 查看酒店信息
	 */
	public function read(){
		$hcid = I('get.id');
		$this->assign('hcid',$hcid);

		$hotel_info = $this->hotelInfo($hcid);
		$this->assign($hotel_info);
		$this->display();
	}

	/**
	 * 查看联系人信息
	 */
	public function readuser(){
		$hcid = I('get.id');
		$this->assign('hcid',$hcid);
		$hotel_info1 = $this->hotelInfo($hcid);
		$this->assign($hotel_info1);
		/* 酒店ID */
		$hid = $this->model->getFieldById($hcid,'h_id');
		/* 酒店信息 */
		$hotel_info = \Pc\Model\HotelModel::findById($hid);
		$hotel_info['sale_tell'] = M('User')->getFieldById($hotel_info['sale_id'],'mobile');
		$this->assign($hotel_info);
		$this->display();
	}

	/**
	 * 合同信息
	 */
	public function contractInfo($hcid){
		/* 合同信息 */
		$hc_info = D('HotelContract')->find($hcid);
		$hc_info['hc_name'] = $hc_info['name'];

		//酒店合同与客房数、设备数
		$hcre_items = D('HoteltHcreView')->where(array('hc_id'=>$hcid))->select();
		//所有设备
		$equipment = M('Equipment')->field('id,name')->where(array('type'=>array('in','1,2')))->select();
		$or_num = 0;
		foreach ($hcre_items as &$value) {
			foreach ($equipment as $val) {
				if($value['e1_id'] == $val['id']){
					$value['e1_name'] = $val['name'];
				}
				if($value['e2_id'] == $val['id']){
					$value['e2_name'] = $val['name'];
				}

				if(!empty($value['e1_name']) && !empty($value['e2_name'])){
					break;
				}
			}
			$or_num += $value['r_num'];
		}
		$this->assign('hcre_items',$hcre_items);
		$this->assign('or_num',$or_num);

		return $hc_info;
	}

	/**
	 * 查看合同信息
	 */
	public function readc(){
		$hcid = I('get.id');
		$this->assign('hcid',$hcid);

		$hc_info = $this->contractInfo($hcid);
        if($hc_info['img']){
            $hc_info['himgs'] = explode(',', $hc_info['img']);
        }
        if($hc_info['sinstall'] && $hc_info['einstall']){
			$sinstall = date('Y-m-d',$hc_info['sinstall']);
			$einstall = date('Y-m-d',$hc_info['einstall']);
        	if($sinstall == $einstall){
        		$hc_info['oper_time'] = $sinstall;
        	}else{
        		$hc_info['oper_time'] = $sinstall.'至'.$einstall;
        	}
        }elseif($hc_info['sinstall']){
        	$hc_info['oper_time'] = date('Y-m-d',$hc_info['sinstall']);
        }else{
        	$hc_info['oper_time'] = date('Y-m-d',$hc_info['rsinstall']).'至'.date('Y-m-d',$hc_info['reinstall']);
        }

		//保养频率
		$hc_info['maintenance'] = M('Maintenance')->getFieldById($hc_info['maintenance_id'],'num');
		//结算模式
		$accountst_info = M('AccountsType')->field('type,price')->getById($hc_info['at_id']);
		if($accountst_info['type'] == 1){
			$hc_info['at_name'] = '共享&emsp;'.$accountst_info['price'].'元/天次';
		}elseif($accountst_info['type'] == 2){
			$hc_info['at_name'] = '租赁&emsp;'.$accountst_info['price'].'元/天';
		}else{
			$hc_info['at_name'] = '月租&emsp;'.$hc_info['month_price'].'元/月';
		}
		//滞纳金
		$hc_info['ls_num'] = M('LatefeesScale')->getFieldById($hc_info['ls_id'],'num');
		$this->assign($hc_info);
		//滞纳金条件
		$hc_info['ls_remark'] = M('LateFee')->getFieldById($hc_info['ls_remark'],'content');
		$this->assign($hc_info);

		$this->display();
	}

	/**
	 * 查看安装信息
	 */
	public function installe(){
		$hcid = I('get.id');
		$this->assign('hcid',$hcid);

		$operOrder_info = M('OperOrder')->field('id,sno,num,nume,now_num,now_nume,stime,etime,u_id,img,type,status')->where(array('hc_id'=>$hcid))->find();
		//计算进度
		$operOrder_info['num_ratio'] = intval(($operOrder_info['now_nume']/$operOrder_info['nume'])*100);
		$hc_info = $this->model->field('rsinstall,reinstall')->getById($hcid);
		$this->assign($hc_info);
		
		if(!empty($operOrder_info) && $operOrder_info['status'] != 1){
			//已安装设备房间
			if($operOrder_info['now_nume'] > 0){
				$oper_info = M('OperInfo')->field('id,room_sno')->where(array('oo_id'=>$operOrder_info['id']))->select();
				$this->assign('oper_info',$oper_info);
			}

			//已分配工程人员
			if($operOrder_info['u_id']){
				$user_info = M('User')->field('real_name,mobile')->getById($operOrder_info['u_id']);
				$operOrder_info['u_name'] = $user_info['real_name'];
				$operOrder_info['mobile'] = $user_info['mobile'];
			}
			

			//所有房间
//			for($n=0; $n < $operOrder_info['num']; $n++) {
            for($n=0; $n < $operOrder_info['nume']; $n++) {
                if ($n < count($oper_info)) {
                    $room_arr[] = array('id' => $oper_info[$n]['id'], 'room_sno' => $oper_info[$n]['room_sno'], 'open' => 1);
                } else {
                    $room_arr[] = array('open' => 0);
                }
            }
			$this->assign('room_arr',$room_arr);
		}

		if($operOrder_info['img']){
			$operOrder_info['imgs'] = explode(',', $operOrder_info['img']);
		}
		//查看是否有打回
        $operOrder_info['roll_back'] = M('OrderBack')->field('content')->where(array('oo_id'=>$operOrder_info['id']))->select();

		$this->assign($operOrder_info);
		$this->display();
	}

	/**
	 * 回款信息
	 */
	 public function return_money(){
	 	$hcid = I('get.id');
		$this->assign('hcid',$hcid);

		//回款情况
		$rm_count = $this->model->return_info($hcid);
		$this->assign($rm_count);

		//列表信息
		$this->assign($this->model->getRmList($hcid));

		$this->display();
	 }

	/**
	 * 查看安装信息
	 */
	public function get_install_room_info(){
		$id = I('post.id');
		$oper_info = $this->model->getRoomInfo($id);
		
		ajax_return(1,'获取成功',$oper_info);		
	}

	/**
	 * 查看保养/维修信息
	 */
	public function get_oper_room_info(){
		$id = I('post.id');
		$oper_info = $this->model->getRoomInfo($id);
		ajax_return(1,'获取成功',$oper_info);		
	}
	

	/**
	 * 列表展示之前准备数据
	 */
	protected function _before_index_view(){
		/* 合同统计信息 */
		$hc_count_info = $this->model->hcCountInfo();

		$this->assign($hc_count_info);
	}

	/**
	 * 添加/编辑展示之前准备数据
	 */
	protected function _before_edit_view(){

		/* 酒店所有客房 */
		/*if(I('get.hid')){
			//$h_id = $this->model->getFieldById(I('get.id'),'h_id');
			$hrthtWhere = array('hotelrt.h_id'=>I('get.hid'));
		}*/
		$hotel_rts = D('HotelrtRoomtView')->where(array('hotelrt.h_id'=>I('get.hid')))->select();
		$this->assign('hotel_rts',$hotel_rts);

		/* 历史认领 */
		/*if($h_id){
			$historys = D('HotelgUserView')->where(array('h_id'=>$h_id))->select();
			$this->assign('historys',$historys);
		}*/

		/* 签约客房 */
		if(I('get.id')){
			$hcre_items = M('HcRoomEquipment')->where(array('hc_id'=>I('get.id')))->select();
			$this->assign('hcre_items',$hcre_items);
		}

		/* 所有设备类型 */
		$equipment_items = M('Equipment')->field('id,name,type')->where(array('type'=>array('in','1,2')))->select();
		$this->assign('equipment_items',$equipment_items);

		/* 维保频率 */
		$maintenance_items = M('Maintenance')->field('id,num')->select();
		$this->assign('maintenance_items',$maintenance_items);

		/* 滞纳金 */
		$latefees_scale_items = M('LatefeesScale')->field('id,num')->select();
		$this->assign('latefees_scale_items',$latefees_scale_items);

		/* 产生滞纳金条件 */
		$latefee_items = M('LateFee')->field('id,content')->where(array('status'=>1))->select();
		$this->assign('latefee_items',$latefee_items);

		/* 结算模式 */
		$accounts_type_items = M('AccountsType')->field('id,type,price,remark')->select();
		$this->assign('accounts_type_items',$accounts_type_items);
	}

	/**
	 * 提供查询条件
	 * @param  array  $wheres 查询条件
	 */
	protected function _set_wheres(&$wheres){
		$keyword = trim(I('get.keyword'));
		$stime = I('get.stime');
		$etime = I('get.etime');
		//关键词
		if(!empty($keyword)){
			unset($wheres['name']);
			$wheres['hotel.name'] = array('like','%'.$keyword.'%');
			$this->assign('keyword',$keyword);
		}
		//日期
		if(!empty($stime) && !empty($etime)){
			$wheres['hotelc.ctime'] = array('between',array(strtotime($stime),strtotime($etime)+86399));
			$this->assign('stime',$stime);
			$this->assign('etime',$etime);
		}elseif(!empty($stime)){
			$wheres['hotelc.ctime'] = array('egt',strtotime($stime));
			$this->assign('stime',$stime);
		}elseif(!empty($etime)){
			$wheres['hotelc.ctime'] = array('elt',strtotime($etime)+86399);
			$this->assign('etime',$etime);
		}
		unset($wheres['status']);
	}

	/**
	 * 查看酒店信息
	 */
	public function hinfo(){
		/* 已认领酒店 */
		$hotels = $this->model->getHotel();
		$this->assign('hotel_items',$hotels);

		/* 所有酒店类型 */
		$hotel_types = D('HotelType')->getList();
		$this->assign('ht_items',$hotel_types);

		/* 所有房间类型 */
		$room_type = D('RoomType')->getList();		

		$hid = I('get.hid') ? I('get.hid') : $hotels[0]['id'];
		//酒店ID
		$hotel_info = \Pc\Model\HotelModel::findById($hid);
		if(!empty($hotel_info['img'])){
			$hotel_info['himgs'] = $hotel_info['img'];
			$hotel_info['img'] = implode(',', $hotel_info['img']);
		}
		$this->assign($hotel_info);

		//酒店房间类型及数量
		$hrt_items = M('HotelRoomType')->field('rt_id,room_num')->where(array('h_id'=>$hid))->select();
		//echo M('HotelRoomType')->_sql();
		//print_r($room_type);
		//print_r($hrt_items);exit;
		foreach ($room_type as &$rtvalue) {
			foreach ($hrt_items as $hrtvalue) {
				if($rtvalue['id'] == $hrtvalue['rt_id']){
					$rtvalue['room_num'] = $hrtvalue['room_num'];
				}
			}
		}

		$this->assign('rt_items',$room_type);
		
		$this->display();
	}

	/**
	 * 获取酒店信息根据酒店ID
	 */
	public function Hotel_info(){
		$hid = I('post.h_id');
		//酒店ID
		$hotel_info = \Pc\Model\HotelModel::findById($hid);
		if(!empty($hotel_info['img'])){
			$hotel_info['himgs'] = $hotel_info['img'];
			$hotel_info['img'] = implode(',', $hotel_info['img']);
		}

		ajax_return(1,'查询成功',$hotel_info);
	}

	/**
	 * 合同中基础信息填写
	 */
	public function hoperation(){
		$data = I('post.');
		if(!isset($data['uhoteluser']) && empty($data['hrtname'])){
			ajax_return(0,'请选择客房类型');
			exit;
		}else{
			//去掉多余的
			for ($i=0; $i < count($data['room_nums']); $i++) { 
				if(empty($data['room_nums'][$i]) || $data['room_nums'][$i] == 0){
					continue;
				}
				$room_nums[] = $data['room_nums'][$i];
			}
			unset($data['room_nums']);

			for($j=0; $j < count($data['hrtname']); $j++) {
				if(empty($room_nums[$j])){
					ajax_return(0,'请完善客房类型信息');
					exit;
				}
				/* 酒店客房类型数量 */
				$ht_arr[] = array('h_id'=>$data['h_id'],'rt_id'=>$data['hrtname'][$j],'room_num'=>$room_nums[$j]);
				$data['room_num'] += $room_nums[$j];
			}
			$uhotel = $data['uhotel'];
			unset($data['hrtname'],$data['uhotel']);
		}

		if(!isset($data['uhoteluser'])){
			$arr_keys = array_keys($data);
			for($i=0; $i < count($arr_keys); $i++) {
				if(empty($data[$arr_keys[$i]])){
					ajax_return(0,'请完善酒店信息');
					exit;
				}
			}
		}
		
		/* 修改酒店信息 */
		$result = $this->model->upHotel($data,$ht_arr);

		if(!$result){
			$this->admin_ajax_return(0);
		}else{
			if($uhotel){
				$this->admin_ajax_return(1);
			}else{
				ajax_return(1,'操作成功',U('add',array('hid'=>$data['h_id'])));
			}
		}	
	}

	/**
	 * 设备订单信息
	 * @param  int  $id  合同ID 
	 */
	public function oper_info($id){
		$info = $this->model->order_info($id);
		//酒店信息
		$h_info = M('Hotel')->field('name,project_name,project_tell')->getById($info['h_id']);
		$info = array_merge($h_info,$info);

		$this->assign($info);
		$this->display();
	}

	/**
	 * 查看设备相关信息根据工单号加房间号
	 * @param  int  	$oid  		工单号
	 * @param  string 	$room_sno	房间号
	 */
	public function equipment_info(){
		$oid = I('post.oid');
		$room_sno = I('post.room_sno');
		$info = M('OperInfo')->where(array('oo_id'=>$oid,'room_sno'=>$room_sno))->find();
		ajax_return(1,'获取成功',$info);
	}

	/**
	 * 回款列表根据合同ID
	 * @param  int  $id  合同ID
	 */
	public function rmoney($id){
		$rmoney_items = D('RmoneyAccountstView')->where(array('hc_id'=>$id))->selelct();
		//回款次数
		$rnum = 0;
		//逾期次数
		$nnum = 0;
		foreach ($rmoney_items as $key => $value) {
			if($value['status'] > 1){
				$rprice += $value['price'];
				++$rnum;
			}elseif($value['mtime']>0 and $value['status'] == 1){
				$nprice += $value['rprice'];
				++$nnum;
			}
		}

		$this->assign('rmoney_items',$rmoney_items);
		$ths->assing(array('rprice'=>$rprice,'rnum'=>$rnum,'nprice'=>$nprice,'nnum'=>$nnum));
		$this->display();
	}

	/**
	 * 保养
	 * @param  int  $id  合同ID
	 */
	public function upkeep(){
		$hcid = I('get.id');
		$this->assign('hcid',$hcid);

		$oowhere = array('hc_id'=>$hcid,'oorder.type'=>2);
		$items = $this->model->get_oorder_list($oowhere);
		$this->assign('items',$items);
		//保养次数
		$upkeep_num = 0;
		foreach ($items as $value) {
			if($value['status'] == 4){
				++$upkeep_num;
			}
		}
		//酒店
		if(empty($items)){
			$hid = $this->model->getFieldById($hcid,'h_id');
		}else{
			$hid = $items[0]['h_id'];
		}

		$h_name = M('Hotel')->getFieldById($hid,'name');
		$this->assign(array('h_name'=>$h_name,'upkeep_num'=>$upkeep_num));

		$this->display();
	}

	/**
	 * 维修
	 * @param  int  $id  合同ID
	 */
	public function maintain(){
		$hcid = I('get.id');
		$this->assign('hcid',$hcid);

		$oowhere = array('hc_id'=>$hcid,'oorder.type'=>3);
		//状态
		$estatus = I('get.estatus');
		if($estatus){
			/*$oowhere['oorder.status'] = array('in','1,2,3,4');
		}else{*/
			$oowhere['oorder.status'] = $estatus;
			$this->assign('estatus',$estatus);
		}

		$items = $this->model->get_oorder_list($oowhere);
		$this->assign('items',$items);
		$this->display();
	}

	/**
	 * 查看 保养/维修
	 */
	public function read_info(){
		$id = I('get.id');
		$ooinfo = D('OoUserView')->getById($id);
		if($ooinfo['img']){
			$ooinfo['img'] = explode(',', $ooinfo['img']);
		}

        if($ooinfo['stime'] && $ooinfo['etime']){
        	$stime = date('Y-m-d',$ooinfo['stime']);
        	$etime = date('Y-m-d',$ooinfo['etime']);
        	if($etime == $stime){
        		$ooinfo['oper_time'] = $stime;
        	}else{
        		$ooinfo['oper_time'] = $stime.'至'.$etime;
        	}
        }elseif($ooinfo['stime']){
        	$ooinfo['oper_time'] = $stime;
        }

		$ooinfo['h_name'] = M('Hotel')->getFieldById($ooinfo['h_id'],'name');
		//查看是否有打回
        $ooinfo['roll_back'] = M('OrderBack')->field('content')->where(array('oo_id'=>$ooinfo['id']))->select();

		$this->assign($ooinfo);

		//所有房间号
		//$oid = M('OperOrder')->where(array('hc_id'=>$ooinfo['hc_id'],'type'=>'1'))->getField('id');
		$room_arr = M('OperInfo')->field('room_sno,status')->where(array('oo_id'=>$ooinfo['id']))->select();
		$this->assign('room_arr',$room_arr);

		$this->display();
	}

	/**
	 * 查看房间信息 保养/维修
	 */
	public function get_room_info(){
		$id= I('post.hc_id');
		$rno = I('post.room_sno');
		$type= I('post.type');
		$status=I('post.status');

		//房间类型
		$oi_info = D('OoOinfoView')->field('oi_id,rt_id,hc_id,equipment_sno,eo_id')->where(array('oo_id'=>$id,'room_sno'=>$rno))->find();
		//设备
		$hcre_info = M('HcRoomEquipment')->field('e1_id,e2_id')->where(array('hc_id'=>$oi_info['hc_id'],'rt_id'=>$oi_info['rt_id']))->find();
		//所有设备类型
		$ewhere['id'] = array('in',$hcre_info['e1_id'].','.$hcre_info['e2_id']);
		$all_einfo = M('Equipment')->field('id,name')->where($ewhere)->select();
		foreach ($all_einfo as $value) {
			if($value['id'] == $hcre_info['e1_id']){
				$room_info['e1_name'] = $value['name'];
			}else{
				$room_info['e2_name'] = $value['name'];
			}
		}

		//安装时间
		$install_time = M('OperInfo')->where(array('equipment_sno'=>$oi_info['equipment_sno'],'status'=>1))->getField('ctime');
		$room_info['install_time'] = $install_time ? date('Y-m-d',$install_time) : '';

		//保养次数
		if($type == 2){
			$room_info['all_count'] = D('OoOinfoView')->where(array('hc_id'=>$oi_info['hc_id'],'room_sno'=>$rno,'type'=>$type,'oinfo.status'=>4))->count();
		}else{
			$room_info['note'] = D('OoOinfoView')->where(array('hc_id'=>$oi_info['hc_id'],'room_sno'=>$rno,'type'=>$type,'oinfo.status'=>array('in','3,4')))->getField('note');
			$room_info['note'] = $room_info['note'] ? $room_info['note'] : '';
		}

		$etime = D('OoOinfoView')->where(array('equipment_sno'=>$oi_info['equipment_sno'],'oinfo.id'=>array('neq',$oi_info['oi_id']),'type'=>$type,'oinfo.status'=>4))->order('oinfo.etime desc')->getField('oi_etime');
		$room_info['ctime'] = $etime ? date('Y-m-d',$etime) : '';
		
		if($status == 4){
			$room_info['type_name'] = M('EquipmentOper')->getFieldById($oi_info['eo_id'],'name');
			$room_info['type_name'] = $room_info['type_name'] ? $room_info['type_name'] : '';
		}

		ajax_return(1,'获取成功',$room_info);
	}

	/**
	 * 文件下载
	 * @param  string  $hcid 合同ID
	 */
	public function download_file($hcid){
		$filename = $this->model->getFieldById($hcid,'img');
		Http::download(APP_PATH.$filename,$showname);
	}
}
