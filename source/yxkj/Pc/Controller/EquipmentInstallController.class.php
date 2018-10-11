<?php
/**
 * EquipmentInstallController.class.php
 * 后台 安装设备
 * @author baddl
 * @date   2017-09-14 15:31
 */
namespace Pc\Controller;
use Pc\Controller\BaseController;

class EquipmentInstallController extends BaseController{
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
		$wheres['type'] = 1;

		/* 角色 */
		$role_id = session('USERINFO.role_id');
		$this->assign('role_id',$role_id);
		//平台工程人员
		if($role_id == 4){
			$wheres['obj.u_id'] = session('USERINFO.id');
			$wheres['obj.status'] = array('gt',1);
		}
		//酒店工程经理
		elseif($role_id == 11) {
			$wheres['obj.h_id'] = session('USERINFO.hotel_id');
			$this->assign('room_num',1);
			$this->assign('distribution',1);
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
			$wheres['obj.status'] = $estatus;
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
        $where=array();
        $where['o.type'] = 1;
		/* 角色 */
		$role_id = session('USERINFO.role_id');
		//平台工程人员
		if($role_id == 4){
			$where['o.u_id'] = session('USERINFO.id');
		}
		//酒店工程经理
		elseif($role_id == 11){
			$where['o.h_id'] = session('USERINFO.hotel_id');
		}
		//平台工程经理

        // 获取条件查询
        $estatus = I('get.estatus');
        if(!empty($estatus)){
            $where['o.status'] = $estatus;
        }
		//统计
        $count_info = array(
            'status_1'=>0,//待分配
            'status_2'=>0,//待安装
            'status_3'=>0,//待确认
            'status_4'=>0,//已安装
            'room_all'=>0,//房间总数
            'total_num'=>0,//总单数
            'installed_house_num'=>0,//已安装房间数
            'finished_num'=>0,//待安装
        );
        $rows = M('OperOrder')->alias('o')
            ->where($where)
//            ->field('o.status,o.num,o.now_num')
            ->field('o.status,o.num,o.now_num,o.nume')
            ->select();
        $count_info['total_num'] = count($rows);
        if($rows){
            foreach ($rows as $val){
                if($val['status'] == 1){//待分配
                    $count_info['status_1'] ++;
                    $count_info['finished_num'] += $val['num'];
                    $count_info['room_all'] +=$val['num'];
                }elseif ($val['status'] == 2){//待安装
                    $count_info['status_2']++;
                    $count_info['installed_house_num'] += $val['now_num'];
                    $count_info['finished_num'] += ($val['nume'] - $val['now_num']);
//                    $count_info['finished_num'] += ($val['num'] - $val['now_num']);
//                    $count_info['room_all'] += $val['num'];
                    $count_info['room_all'] += $val['nume'];
                }elseif ($val['status'] == 3){//待确认
                    $count_info['status_3']++;
                    $count_info['installed_house_num'] += $val['now_num'];
                    $count_info['finished_num'] += ($val['num'] - $val['now_num']);
                    $count_info['room_all'] += $val['num'];
                }else{//已安装
                    $count_info['status_4']++;
                    $count_info['installed_house_num'] += $val['now_num'];
                    $count_info['room_all'] += $val['num'];
                }
            }
        }
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
        /*for ($i=0;$i<count($info['h_img']);$i++){
            if(!file_exists($_SERVER['DOCUMENT_ROOT']).$info['h_img'][$i]){
                $info['h_img'][$i]='';
            }
        }*/
		if($info['status'] >= 2 && $info['img']){
			$info['ok_img'] = $info['img'];
			$info['img'] = explode(',', $info['img']);
		}
		$info['num_ratio'] = intval(($info['now_nume']/$info['nume']*100));
		/*for ($i=0;$i<count($info['img']);$i++){
		    if(!file_exists($_SERVER['DOCUMENT_ROOT']).$info['img'][$i]){
                $info['img'][$i]='';
            }
        }*/
        //查看是否有打回
        $info['roll_back'] = M('OrderBack')->field('content')->where(array('oo_id'=>$info['id']))->select();

		$this->assign($info);

		//所有房间
		$install_roo = M('OperInfo')->field('id,room_sno')->where(array('oo_id'=>$info['id'],'status'=>1))->select();

        // for($i=0; $i < $info['num']; $i++){
		for($i=0; $i < $info['nume']; $i++){
			if($i < count($install_roo)){
				$room_arr[] = array('room_sno'=>$install_roo[$i]['room_sno'],'open'=>1,'id'=>$install_roo[$i]['id']);
				continue;
			}
			$room_arr[] = array('open'=>0);			
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
		$oid = I('get.id');
		$order_info = $this->model->field('id as oid,sno as osno,hc_id,u_id,ctime as octime,status as ostatus')->getById($oid);
		$this->assign($order_info);

		/* 酒店信息 */
		$hotel_info = HotelContractController::hotelInfo($order_info['hc_id']);
		$hotel_info['h_name'] = $hotel_info['name'];
		$this->assign($hotel_info);

		/* 合同信息 */
		$hc_info = HotelContractController::contractInfo($order_info['hc_id']);
		$hc_info['img'] = explode(',', $hc_info['img']);
		//保养频率
		$hc_info['maintenance'] = M('Maintenance')->getFieldById($hc_info['maintenance_id'],'num');
		//结算模式
		$accountst_info = M('AccountsType')->field('type,price')->getById($hc_info['at_id']);
		if($accountst_info['type'] == 1){
			$hc_info['at_name'] = '共享&emsp;'.$accountst_info['price'].'/天次';
		}else{
			$hc_info['at_name'] = '租赁&emsp;'.$accountst_info['price'].'/天';
		}
		//滞纳金
		$hc_info['ls_num'] = M('LatefeesScale')->getFieldById($hc_info['ls_id'],'num');
		$this->assign($hc_info);

		/* 工程人员 */
		$user_list = M('User')->field('id,real_name as name,mobile')->where(array('parent_id'=>session('USERINFO.id')))->select();
		$this->assign('user_list',$user_list);

		$this->display();
	}

	/**
	 * 处理打回
	 */
	public function roll_back(){
		$re_status = $this->model->roll_back_oper();
		$this->admin_ajax_return($re_status);
	}
}