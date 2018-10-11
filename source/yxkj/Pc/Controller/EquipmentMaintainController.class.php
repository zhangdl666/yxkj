<?php
/**
 * EquipmentMaintainController.class.php
 * 后台 设备维修
 * @author baddl
 * @date   2017-09-15 16:45
 */
namespace Pc\Controller;
use Pc\Controller\BaseController;

class EquipmentMaintainController extends BaseController{
	/**
	 * 初始化 
	 */
	public function _initialize(){
		$this->model = 'OperOrder';
		parent::_initialize();
		$this->placeholder = '请输入酒店名称';
	}

	/**
	 * 提供查询条件
	 * @param  array  $wheres 查询条件
	 */
	protected function _set_wheres(&$wheres){
		unset($wheres['status']);
		$wheres['type'] = 3;

		/* 角色 */
		$role_id = session('USERINFO.role_id');
		//平台工程人员
		if($role_id == 4){
			$wheres['obj.u_id'] = session('USERINFO.id');
			$wheres['obj.status'] = array('gt',1);
		}
		//酒店工程经理
		elseif($role_id == 11) {
			$wheres['obj.h_id'] = session('USERINFO.hotel_id');
			//$wheres['obj.status'] = array('gt',1);
			//$this->assign('hleader',1);
		}
		//平台工程经理
		//平台总经理
		else{
			$this->assign('distribution',1);
		}

		//搜索
		$estatus = I('get.estatus');
		$keyword = I('get.keyword');
		if($estatus){
			if($role_id == 11 && $estatus == 2){
				$wheres['obj.status'] = array('in','1,2');
			}else{
				$wheres['obj.status'] = $estatus;
			}
			
			$this->assign('estatus',$estatus);
		}

		if($keyword){
			unset($wheres[$this->query]);
			$wheres['b.name'] = array('like','%'.$keyword.'%');
			$this->assign('keyword',$keyword);
		}
	}

	/**
	 * 列表展示前准备数据
	 */
	protected function _before_index_view(){
		$count_where['type'] = 3;
		/* 角色 */
		$role_id = session('USERINFO.role_id');
		//平台工作人员
		if($role_id == 4){
			$count_where['u_id'] = session('USERINFO.id');
			$count_where['status'] = array('gt',1);
		}
		//酒店工程经理
		elseif($role_id == 11) {
			$count_where['h_id'] = session('USERINFO.hotel_id');
			//$count_where['status'] = array('gt',1);
			$this->assign('room_num',1);
		}
		//平台工作经理
		//平台总经理
        // 获取条件查询
        $estatus = I('get.estatus');
        $keyword = I('get.keyword');
		//统计
		$count_info = $this->model->getStatisticsData($count_where, $keyword, $estatus);
		$this->assign($count_info);
	}

	/**
	 * 查看详情
	 */
	public function read(){
		$id = I('get.id');
		$info = $this->model->alias('obj')->field('obj.*,h.name as h_name,h.img as h_img,u.real_name as u_name,mobile')
				->join('yx_hotel as h on h.id=obj.h_id')
				->join('left join yx_user as u on u.id=obj.u_id')
				->where('obj.id='.$id)->find();
		$info['h_img'] = explode(',', $info['h_img']);
		if($info['status'] >= 2 && $info['img']){
			$info['ok_img'] = $info['img'];
			$info['img'] = explode(',', $info['img']);
		}
		$info['num_ratio'] = intval(($info['now_nume']/$info['nume']*100));
		//查看是否有打回
        $info['roll_back'] = M('OrderBack')->field('content')->where(array('oo_id'=>$info['id']))->select();

		$this->assign($info);

		//所有房间
		$install_roo = M('OperInfo')->field('id,room_sno,status')->where(array('oo_id'=>$info['id']))->select();
		for($i=0; $i < $info['num']; $i++){
			if($install_roo[$i]['status'] == 4){
				$room_arr[] = array('room_sno'=>$install_roo[$i]['room_sno'],'open'=>1,'id'=>$install_roo[$i]['id']);
				continue;
			}
			$room_arr[] = array('room_sno'=>$install_roo[$i]['room_sno'],'open'=>0,'id'=>$install_roo[$i]['id']);			
		}
		$this->assign('room_arr',$room_arr);

		if(I('get.readonly')){
			$this->assign('readonly',I('get.readonly'));
		}
		
		$this->display();
	}

	/**
	 * 分配工单	 
	 */
	public function distribution(){
		$id = I('get.id');
		$info = $this->model->alias('obj')->field('obj.*,h.name as h_name,h.img as h_img,u_id')
				->join('yx_hotel as h on h.id=obj.h_id')
				->where('obj.id='.$id)->find();
		$info['h_img'] = explode(',', $info['h_img']);
		if($info['status'] > 2){
			$info['img'] = explode(',', $info['img']);
		}
		$info['num_ratio'] = intval(($info['now_nume']/$info['nume']*100));
		$this->assign($info);

		//所有房间
		$install_roo = M('OperInfo')->field('id,room_sno')->where(array('oo_id'=>$info['id']))->select();
        // for($i=0; $i < $info['num']; $i++){
        for($i=0; $i < $info['nume']; $i++){
			if($install_roo[$i]['status'] == 4){
				$room_arr[] = array('room_sno'=>$install_roo[$i]['room_sno'],'open'=>1,'id'=>$install_roo[$i]['id']);
				continue;
			}
			$room_arr[] = array('room_sno'=>$install_roo[$i]['room_sno'],'open'=>0,'id'=>$install_roo[$i]['id']);			
		}
		$this->assign('room_arr',$room_arr);

		/* 工程人员 */
		$user_list = M('User')->field('id,real_name as name,mobile')->where(array('parent_id'=>session('USERINFO.id')))->select();
		$this->assign('user_list',$user_list);

		$this->display();
	}

	/**
	 * 添加前展示
	 */
	protected function _before_edit_view(){
		$hotel_info = M('Hotel')->field('id,name,tell')->getById(session('USERINFO.hotel_id'));
		$this->assign($hotel_info);

		//酒店下所有合同
		$hc_items = M('HotelContract')->field('id,name')->where(array('h_id'=>session('USERINFO.hotel_id'),'etime'=>array('egt',time())))->select();
		$this->assign('hc_items',$hc_items);

		//所有已安装设备
		if(!empty($hc_items)){
			$equipment = D('OoOinfoView')->field('oi_id,room_sno')->where(array('hc_id'=>$hc_items[0]['id'],'type'=>1,'oorder.status'=>4))->select();
			$this->assign('al_equipment',$equipment);
		}
	}

	/**
	 * 获取合同中已经安装了的房间
	 */
	public function get_rooms(){
		$hc_id = I('post.hcid');
		$equipment = D('OoOinfoView')->field('oi_id,room_sno')->where(array('hc_id'=>$hc_id,'type'=>1,'oorder.status'=>4))->select();
		ajax_return(1,'获取成功',$equipment);
	}

	/**
	 * 是否已经有维修单未完成
	 */
	public function is_maintian(){
		$oi_id = I('post.sno');
		$sno = M("OperInfo")->getFieldById($oi_id,'equipment_sno');
		$result = D('OoOinfoView')->where(array('equipment_sno'=>$sno,'type'=>3,'oorder.status'=>array('neq',4)))->getField('room_sno');
		if($result){
			ajax_return(1,'正在维修中');
		}else{
			ajax_return(0,'等待申请');
		}
	}
}