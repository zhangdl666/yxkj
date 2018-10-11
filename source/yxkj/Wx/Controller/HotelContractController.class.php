<?php
/**
 * HotelContractController.class.php
 * 后台 合同管理
 * @author baddl
 * @date   2017-09-19 11:09
 */
namespace Wx\Controller;
use Wx\Controller\BaseController;
use Org\Net\Http;

class HotelContractController extends BaseController{
	/**
	 * 列表
	 */
	public function index(){
		$model = 'HotelcHotelgHotelView';
		$hids = $this->hcc_where();
		$wheres['hotelc.h_id'] = array('in',$hids);
		$wheres['hotelg.sale_id'] = array('neq',0);
		$this->_set_where($wheres);
		$data_list = $this->model->getList($model,$wheres,$order_str='hotelc.ctime desc');
		$this->assign($data_list);
		cookie('__forwards__', $_SERVER['REQUEST_URI']);
		$this->assign('page_title','首页');
		$this->display();
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
			case 3:
				# 平台销售经理
				//下级销售人员
				$sale_arr = M('User')->field('id')->where(array('parent_id'=>session('USERINFO.id')))->select();
				$sale_arr = i_array_column($sale_arr,'id');
				$saleids = implode(',', $sale_arr);
				//签约酒店
				$hid_arr = M('HotelGet')->field('h_id')->where(array('sale_id'=>array('in',$saleids),'is_default'=>1))->select();
				$hid_arr = i_array_column($hid_arr,'h_id');
				$hccwhere = implode(',', $hid_arr);
				break;
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
	 * 搜索条件
	 */
	private function _set_where(&$wheres){
		$keyword = trim(I('get.keyword'));
		if(!empty($keyword)){
			$wheres['hotel.name'] = array('like','%'.$keyword.'%');
			$this->assign('keyword',$keyword);
		}
	}

	/**
	 * 查看酒店信息
	 */
	public function hinfo(){
		/* 已认领酒店 */
		$hotels = $this->model->getHotel();
		$this->assign('hotel_items',$hotels);

		/* 所有酒店类型 */
		$hotel_types = D('HotelType')->field('name,id')->order('id desc')->select();
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

		/* 酒店房间类型数量 */
		$hrt_items = D('HotelrtRoomtView')->where(array('h_id'=>$hid))->select();
		foreach ($room_type as &$value) {
			foreach ($hrt_items as $hrtk => $hrtval) {
				if($value['id'] == $hrtval['id']){
					$value['room_num'] = $hrtval['room_num'];
				}
			}
		}
		$hotel_info['hrt_items'] = $room_type;
		$hotel_info['is_get'] = 1;
		$this->assign($hotel_info);

		$this->display();
	}

	/**
	 * 新增合同
	 */
	public function add(){
		$this->assign('back_url',$_SERVER['HTTP_REFERER']);
		/* 已认领酒店 */
		$hotels = $this->model->getHotel();
		$this->assign('hotel_items',$hotels);

		//添加/编辑展示之前准备数据
		$this->_before_edit_view();
		$this->assign('hid',I('get.hid'));

		$this->display();
	}


	/**
	 * 查看酒店信息
	 */
	public function read(){
		$hcid = I('get.id');
		$this->assign('hcid',$hcid);

		$hotel_info = $this->hotelInfo($hcid);
		$this->assign($hotel_info);

		cookie('__forwards__', $_SERVER['REQUEST_URI']);
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
		$hc_info = $this->model->field('h_id,einstall')->getById($hcid);
		/* 酒店信息 */
		/*$hotel_info = \Pc\Model\HotelModel::findById($hc_info['hid']);
		$hotel_info['sale_tell'] = M('User')->getFieldById($hotel_info['sale_id'],'mobile');*/
		$hotel_info = $this->hotelInfo($hcid);
		$hotel_info['hc_status'] = $hc_info['einstall']>0 ? 1 : 0;
		$this->assign($hotel_info);

		cookie('__forwards__', $_SERVER['REQUEST_URI']);

		$this->display();
	}

	/**
	 * 查看合同信息
	 */
	public function readc(){
		$hcid = I('get.id');
		$this->assign('hcid',$hcid);

		$hc_info = $this->contractInfo($hcid);
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
        
        //滞纳金条件
		$hc_info['ls_remark'] = M('LateFee')->getFieldById($hc_info['ls_remark'],'content');
		$this->assign($hc_info);

		$this->assign($hc_info);
        //var_dump($hc_info);exit;
		cookie('__forwards__', $_SERVER['REQUEST_URI']);
		$this->display();
	}

    /**
     * 合同信息
     */
    public function contractInfo($hcid){
        /* 合同信息 */
        $hc_info = D('HotelContract')->find($hcid);
        $hc_info['hc_name'] = $hc_info['name'];
        if($hc_info['img']){
            $hc_info['himgs'] = explode(',', $hc_info['img']);
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
        $hc_info['hc_status'] = $hc_info['einstall']>0 ? 1 : 0;


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
	 * 编辑酒店信息
	 */
	public function edit(){
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

		$this->assign($hc_info);

		$hotel_rts = D('HotelrtRoomtView')->where(array('hotelrt.h_id'=>$hc_info['h_id']))->select();
		$this->assign('hotel_rts',$hotel_rts);

		$this->display();
	}

    /**
	 * 查看安装信息
	 */
	public function installe(){
		$hcid = I('get.id');
		$this->assign('hcid',$hcid);

		$operOrder_info = M('OperOrder')->field('id,sno,num,now_num,nume,now_nume,stime,etime,img,type,status,u_id')->where(array('hc_id'=>$hcid))->find();
		/*if(!empty($operOrder_info) && $operOrder_info['status'] != 1){*/
			//计算进度
			$operOrder_info['num_ratio'] = intval(($operOrder_info['now_nume']/$operOrder_info['nume'])*100);
			$hc_info = $this->model->field('rsinstall,reinstall')->find($hcid);

			//已安装设备房间
			if($operOrder_info['now_num'] > 0){
				$oper_info = M('OperInfo')->field('id,room_sno')->where(array('oo_id'=>$operOrder_info['id']))->select();
				$this->assign('oper_info',$oper_info);
			}

			//安装负责人
			if($operOrder_info['u_id']){
				$u_info = M('User')->field('real_name as name,mobile')->getById($operOrder_info['u_id']);
				$operOrder_info = array_merge($operOrder_info,$hc_info,$u_info);
			}else{
				$operOrder_info = array_merge($operOrder_info,$hc_info);
			}

			//所有房间
//			for($n=0; $n < $operOrder_info['num']; $n++) {
            for($n=0; $n < $operOrder_info['nume']; $n++) {
				if($n < count($oper_info)){
					$room_arr[] = array('id'=>$oper_info[$n]['id'],'room_sno'=>$oper_info[$n]['room_sno'],'open'=>1);
				}else{
					$room_arr[] = array('open'=>0);
				}
			}
			$this->assign('room_arr',$room_arr);

			if($operOrder_info['img']){
				$hc_img = explode(',', $operOrder_info['img']);
				$operOrder_info['img'] = $hc_img;
			}
		/*}*/
		//查看是否有打回
        $operOrder_info['roll_back'] = M('OrderBack')->field('content')->where(array('oo_id'=>$operOrder_info['id']))->select();

		$this->assign($operOrder_info);

		$this->display();
	}

    /**
     * 加载更多数据
     */
    public function show_more_rm_data($key, $num = 10){
        $get_datas = $this->model->showMoreRmDatas($key,$num);
        if ($get_datas) {
            $this->get_ajax_return(1,'加载成功！',$get_datas);
        } else {
            $this->get_ajax_return(0,'加载失败！');
        }
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
		$model = 'RmoneyAccountstView';
		$wheres = array('hc_id'=>$hcid);
		$order_str = 'rmoney.ctime desc';
		$re_datas = $this->model->getRmList($model,$wheres,$order_str);
		$this->assign($re_datas);

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
		if(empty($estatus)){
			$oowhere['oorder.status'] = array('in','1');
		}else{
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
		if($ooinfo['type'] == 2){
			$ooinfo['type_name'] = '保养';
		}else{
			$ooinfo['type_name'] = '维修';
		}
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

		$install_time = M('OperInfo')->where(array('equipment_sno'=>$oi_info['equipment_sno'],'status'=>1))->getField('ctime');
		$room_info['install_time'] = $install_time ? date('Y-m-d',$install_time) : '';

		//保养/维修次数
		/*if($type == 2){*/
			$room_info['all_count'] = D('OoOinfoView')->where(array('hc_id'=>$oi_info['hc_id'],'room_sno'=>$rno,'type'=>$type,'oinfo.status'=>4))->count();
		/*}else{*/
			$room_info['note'] = D('OoOinfoView')->where(array('hc_id'=>$oi_info['hc_id'],'room_sno'=>$rno,'type'=>$type,'oinfo.status'=>array('in','3,4')))->getField('note');
			$room_info['note'] = $room_info['note'] ? $room_info['note'] : '';
		/*}*/

		$etime = D('OoOinfoView')->where(array('equipment_sno'=>$oi_info['equipment_sno'],'oinfo.id'=>array('neq',$oi_info['oi_id']),'type'=>$type,'oinfo.status'=>4))->order('oinfo.etime desc')->getField('oi_etime');
		$room_info['ctime'] = !empty($etime) ? date('Y-m-d',$etime) : '';
		$room_info['etime'] = $etime;

		if($status == 4){
			$room_info['type_name'] = M('EquipmentOper')->getFieldById($oi_info['eo_id'],'name');
			$room_info['type_name'] = $room_info['type_name'] ? $room_info['type_name'] : '';
		}

		ajax_return(1,'获取成功',$room_info);
	}

    /**
	 * 查看安装信息
	 */
	public function get_install_room_info(){
		$id = I('post.id');
		$oper_info = M('OperInfo')->field('oo_id,equipment_sno,rt_id,floor,room_sno,is_window,orientation,place,eo_id,e_id,note')->getById($id);
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
		//空调品牌
		//$oper_info['air_name'] = M('Equipment')->getFieldById($oper_info['e_id'],'name');
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
		

		ajax_return(1,'获取成功',$oper_info);
	}

    /**
	 * 酒店信息
	 */
	public function hotelInfo($hcid){
		$hc_info = $this->model->field('h_id,einstall')->getById($hcid);

		/* 酒店信息 */
		$hotel_info = \Pc\Model\HotelModel::findById($hc_info['h_id']);
		if(!empty($hotel_info['img'])){
			$hotel_info['himgs'] = $hotel_info['img'];
			$hotel_info['img'] = implode(',', $hotel_info['img']);
		}
		/* 酒店房间类型数量 */
		$hotel_info['hrt_items'] = D('HotelrtRoomtView')->where(array('h_id'=>$hc_info['h_id']))->select();
		$hotel_info['hc_status'] = $hc_info['einstall']>0 ? 1 : 0;
		return $hotel_info;
	}

	/**
	 * 添加/编辑展示之前准备数据
	 */
	protected function _before_edit_view(){
		/* 酒店所有客房 */
		$hotel_rts = D('HotelrtRoomtView')->where(array('hotelrt.h_id'=>I('get.hid')))->select();
		$this->assign('hotel_rts',$hotel_rts);

		/* 签约客房 */
		if(I('get.id')){
			$hcre_items = M('HcRoomEquipment')->where(array('hc_id'=>I('get.id')))->select();
			$this->assign('hcre_items',$hcre_items);
		}

		/* 所有设备类型 */
		$equipment_items = M('Equipment')->field('id,name,type')->where(array('type'=>array('in','1,2')))->select();
		$this->assign('equipment_items',$equipment_items);

		/* 保养频率 */
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
	 * 合同中基础信息填写
	 */
	public function hoperation(){
		$data = I('post.');
		if($data['pcc_area']){
			$pcc  = explode(',', $data['pcc_area']);
			$data['provice']= $pcc[0];
			$data['city'] 	= $pcc[1];
			$data['county'] = $pcc[2];
			unset($data['pcc_area']);
		}		

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
			$hcid = $data['hc_id'];
			unset($data['hrtname'],$data['uhotel'],$data['hc_id']);
		}

		if(!isset($data['uhoteluser'])){
			$arr_keys = array_keys($data);
			for($i=0; $i < count($arr_keys); $i++) {
				if(empty($data[$arr_keys[$i]]) && $arr_keys[$i] != 'county'){
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
	 * 文件下载
	 * @param  string  $hcid 合同ID
	 */
	public function download_file(){
		$hcid = I('post.hcid') ? I('post.hcid') : I('get.hcid');
		$check= I('post.check');
		$filename = $this->model->getFieldById($hcid,'img');
		if($check){
			if($filename){
				ajax_return(1,'查找成功');
			}else{
				$this->admin_ajax_return(0);
			}
			exit;
		}
		//Http::download(APP_PATH.$filename);
		$file_pos = strripos($filename, '/');
		$file_name= substr($filename, ($file_pos*1+1));
		$file = fopen($filename,"r"); // 打开文件 
		// 输入文件标签 
		Header("Content-type: application/octet-stream"); 
		Header("Accept-Ranges: bytes"); 
		Header("Accept-Length: ".filesize($filename)); 
		Header("Content-Disposition: attachment; filename=" . $file_name); 
		// 输出文件内容 
		echo fread($file,filesize($filename)); 
		fclose($file); 
		exit;
		//header("Location:".WEB_URL.$filename);
	}
}
		