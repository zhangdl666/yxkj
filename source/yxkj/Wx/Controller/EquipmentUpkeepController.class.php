<?php
/**
 * EquipmentUpkeepController.class.php
 * 后台 设备保养
 * @author baddl
 * @date   2017-09-15 11:23
 */
namespace Wx\Controller;
use Wx\Controller\BaseController;

class EquipmentUpkeepController extends BaseController{
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
		$wheres['type'] = 2;

		/* 角色 */
		$role_id = session('USERINFO.role_id');
		//平台工程人员
		if($role_id == 4){
			$wheres['u_id'] = session('USERINFO.id');
			$wheres['status'] = 2;
			$this->assign('estatus',2);
			$this->assign('install',1);
            $flagWhere=array(
                'type'=>2,
                'status'=>2,
                'u_id'=>$wheres['u_id'],
            );
            $flagNumRes=$this->model->where($flagWhere)->count();
            if($flagNumRes != 0){
                $this->assign('flagTwo',1);
            }
		}
		//酒店工程经理
		elseif($role_id == 11) {
			$wheres['h_id'] = session('USERINFO.hotel_id');
			$wheres['status'] = 2;
			$this->assign('estatus',2);
			$this->assign('install',1);
            $flagWhere=array(
                'type'=>2,
                'status'=>3,
                'h_id'=>$wheres['h_id'],
            );
            $flagNumRes=$this->model->where($flagWhere)->count();
            if($flagNumRes != 0){
                $this->assign('flagThree',1);
            }
		}
		//平台工程经理
		//平台总经理
		else{
			$wheres['status'] = 1;
			$this->assign('estatus',1);
			$this->assign('distribution',1);
            if($role_id == 5){
                $flagWhere=array(
                    'type'=>2,
                    'status'=>1,
                );
                $flagNumRes=$this->model->where($flagWhere)->count();
                if($flagNumRes != 0){
                    $this->assign('flagOne',1);
                }
            }
		}

		//搜索
		$estatus = I('get.estatus');
		if($role_id == 11 && $estatus == 2){
			$wheres['status'] = array('in','1,2');
			$this->assign('estatus',$estatus);
		}elseif($estatus){
			$wheres['status'] = $estatus;
			$this->assign('estatus',$estatus);
		}
	}

	/**
	 * 提供排序方式
	 * @param string $orders 排序方式
	 */
	protected function _set_order(&$orders){
		$orders = 'ctime desc';
	}

	/**
	 * 列表展示前准备数据
	 */
	protected function _before_index_view(){
		/*$count_where['type'] = 2;*/
		/* 角色 */
		/*$role_id = session('USERINFO.role_id');
		//平台工作人员
		if($role_id == 4){
			$count_where['status'] = array('gt',1);
		}
		//酒店工程经理
		elseif($role_id == 11) {
			$count_where['h_id'] = session('USERINFO.hotel_id');
			$count_where['status'] = array('gt',1);
		}*/
		//平台工作经理
		//平台总经理

		//统计
		/*$count_info = $this->model->getStatisticsData($count_where);
		$this->assign($count_info);*/
	}

	/**
	 * 查看详情
	 */
	public function read(){
		$id = I('get.id');
		$info = $this->model->alias('obj')->field('obj.*,h.name as h_name,h.img as h_img,u.real_name as u_name,mobile,rtime,stime,etime')
				->join('yx_hotel as h on h.id=obj.h_id')
				->join('left join yx_user as u on u.id=obj.u_id')
				->where('obj.id='.$id)->find();
		if($info['h_img']){
            $info['h_img'] = explode(',', $info['h_img']);
        }else{
            $info['h_img'][] = '/Public/Images/elogo.png';
        }
		if($info['status'] >= 2 && $info['img']){
			$info['imgs'] = explode(',', $info['img']);
		}
		$info['num_ratio'] = intval(($info['now_nume']/$info['nume']*100));
		if(!empty($info['etime']) && !empty($info['stime'])){
            $stime = date('Y-m-d',$info['stime']);
            $etime = date('Y-m-d',$info['etime']);
            if($stime == $etime){
                $info['datetime'] = $stime;
            }else{
                $info['datetime'] = $stime.'至'.$etime;
            }
		}elseif(!empty($info['stime'])){
			$info['datetime'] = date('Y-m-d',$info['stime']);
		}elseif(!empty($info['rtime'])){
			$info['datetime'] = date('Y-m-d',$info['rtime']);
		}
		$info['roll_back_status'] = $info['roll_back'];
		//查看是否有打回
        $info['roll_back'] = M('OrderBack')->field('content')->where(array('oo_id'=>$info['id']))->select();

		$this->assign($info);

		//所有房间
		$install_roo = M('OperInfo')->field('id,room_sno,status')->where(array('oo_id'=>$info['id']))->select();
        // for($i=0; $i < $info['num']; $i++){
		for($i=0; $i < $info['nume']; $i++){
			if($install_roo[$i]['status'] == 4){
				$room_arr[] = array('room_sno'=>$install_roo[$i]['room_sno'],'open'=>1,'id'=>$install_roo[$i]['id']);
				continue;
			}
			$room_arr[] = array('room_sno'=>$install_roo[$i]['room_sno'],'open'=>0,'id'=>$install_roo[$i]['id']);
		}
		$this->assign('room_arr',$room_arr);
		$this->assign('estatus',I('get.status'));

		/*if($info['status'] == 2){
			//上传图片所需
	        $appid = C('WEIXIN.AppID');
	        $appSecret = C('WEIXIN.AppSecret');
	        $jssdk = new JSSDK($appid,$appSecret);
	        $sign_package = $jssdk->getSignPackage();
	        $this->assign($sign_package);
	    }*/

		$this->display();
	}

	/**
	 * 分配工单	 
	 */
	public function distribution(){
		$id = I('get.id');
		$status = $this->model->getFieldById($id,'status');
		if(in_array($status,array(1,2))){
			$this->assign(array('status'=>$status,'id'=>$id));
		}
		
		/* 工程人员 */
		$user_list = M('User')->field('id,real_name as name,mobile,img')->where(array('parent_id'=>session('USERINFO.id')))->select();
        foreach ($user_list as &$val){
            if(!empty($val['img']) && !file_exists($_SERVER['DOCUMENT_ROOT'].$val['img'])){
                $val['img'] = '';
            }
        }
		$this->assign('user_list',$user_list);

		$this->display();
	}

	/**
	 * 扫一扫
	 */
	public function oper(){
		$equipment_info = D('OperInfo')->alias('oi')->field('oi.id,oo_id,equipment_sno,floor,room_sno,oo.type,rt.name as rt_name,u.real_name as uname,mobile')
						->join('join yx_oper_order as oo on oi.oo_id=oo.id')
						->join('join yx_user as u on oo.u_id=u.id')
						->join('left join yx_room_type as rt on rt.id=oi.rt_id')
						->where(array('oi.equipment_sno'=>I('get.sno'),'oo_id'=>I('get.id'),'oo.status'=>I('get.status')))->find();
		$this->assign($equipment_info);

		if($equipment_info['type'] == 2){
			$status = '保养';
			$eoper = M('EquipmentOper')->where(array('type'=>1))->select();
			$this->assign(array('status'=>$status,'eoper_items'=>$eoper));
		}elseif($equipment_info['type'] == 3){
			$status = '维修';
			$eoper = M('EquipmentOper')->where(array('type'=>2))->select();
			$this->assign(array('status'=>$status,'eoper_items'=>$eoper));
		}else{
			$status = '安装';
			$this->assign(array('status'=>$status));
		}

		$this->display();
	}

	/**
	 * 安装/保养/维修
	 */
	public function oioperation(){
		if($this->model->create() !== false){
			$data = I('post.');
			$re_status = $this->model->oioperation(I('post.'));

			$name = M('EquipmentOper')->where(array('id'=>$data['eo_id']))->getField('name');
			if($name=='更换' && $re_status){
				ajax_return(1,'操作成功',U('EquipmentInstall/oper_info',array('oo_id'=>$data['oo_id'],'id'=>$data['id'])));
				exit;
			}elseif($re_status){
				ajax_return(1,'操作成功',cookie('__forwards__'));
				exit;
			}
		}
		ajax_return(0,$this->model->getError());
	}

	/**
	 * 处理确认
	 */
	public function operover(){
		$data = I('post.');
		//上传凭证
        if(!$data['img']){
            $this->error = '请上传确认单';
            return false;
        }
		$data['status'] = 3;
		$data['roll_back'] = 0;
		$re_sult = $this->model->save($data);

        $ooinfo = $this->model->field('type,sno,h_id')->getById($data['id']);
        //操作消息消失
        not_oper($ooinfo['sno'],session('USERINFO.id'));
        
        if($ooinfo['type'] == 1){
        	$oper_title  = '待处理安装工单';
            $msg_content = '您有一条安装工单号：'.$ooinfo['sno'].' 待处理';
            $oper_url = 'EquipmentInstall/index';
        }elseif($ooinfo['type'] == 2){
        	$oper_title  = '待处理保养工单';
            $msg_content = '您有一条保养工单号：'.$ooinfo['sno'].' 待处理';
            $oper_url = 'EquipmentUpkeep/index';
        }else{
        	$oper_title  = '待处理维修工单';
            $msg_content = '您有一条维修工单号：'.$ooinfo['sno'].' 待处理';
            $oper_url = 'EquipmentMaintain/index';
        }
        //酒店工程经理做处理
        $user_id = M('User')->field('id')->where(array('hotel_id'=>$ooinfo['h_id'],'role_id'=>11,'status'=>1))->select();
        if($user_id){
        	$user_ids = implode(',', i_array_column($user_id,'id'));
        	has_oper($msg_content,$user_ids,$oper_url,$oper_title);
        }

		$this->admin_ajax_return($re_sult);
	}

	public function getNoHaddleNum(){
        $userInfo=session('USERINFO');

        $where=array();
        $where['type'] = 2;
        if($userInfo['role_id'] == 5){//平台工程经理
            $where['status'] = 1;
        }elseif ($userInfo['role_id'] == 4){//平台工程人员
            $where['status'] = 2;
            $where['u_id'] = $userInfo['id'];
        }elseif ($userInfo['role_id'] == 11){//酒店工程经理
            $where['status'] = 3;
            $where['h_id'] = $userInfo['hotel_id'];
        }
        $num=$this->model->where($where)->count();
        return $num;
    }
}