<?php
/**
 * DeviceInfoController.class.php
 * 后台 房间与设备信息
 * @author baddl
 * @date   2018-01-29 22:37
 */
namespace Pc\Controller;
use Pc\Controller\BaseController;

class DeviceInfoController extends BaseController{
	/**
	 * 初始化 
	 */
	public function _initialize(){
		$this->model = 'OperInfo';
		parent::_initialize();
	}

	/**
	 * 提供查询条件
	 * @param  array  $wheres 查询条件
	 */
	protected function _set_wheres(&$wheres){
		$data = I('post.');
        /*$room_sno = I('post.room_sno');
        $equipment_sno = I('post.equipment_sno');
        $hotel_id = I('post.hotel_id');
        $hc_id = I('post.hc_id');*/
        if(!empty($data['hotel_id'])) {
            $wheres['hotel.id']= $data['hotel_id'];
            $this->assign('whotel',$data['hotel_id']);
        }
        if(!empty($data['hc_id'])) {
            $wheres['hc.id']= $data['hc_id'];
            $this->assign('whc',$data['hc_id']);
        }
        if(!empty($data['room_sno'])) {
            $wheres['room_sno']=array('like','%'.$data['room_sno'].'%');
            $this->assign('room_sno',$data['room_sno']);
        }
        if(!empty($data['equipment_sno'])) {
            $wheres['equipment_sno']=array('like','%'.$data['equipment_sno'].'%');
            $this->assign('equipment_sno',$data['equipment_sno']);
        }
        unset($wheres['status']);
	}

	/**
	 * 编辑前准备工作
	 */
	protected function _before_index_view(){
		//所有酒店
		$hotel_list = M('Hotel')->field('id,name')->where(array('is_sign'=>1,'status'=>1))->select();
		$this->assign('hotel_list',$hotel_list);
		//所有合同
		$hc_list = M('HotelContract')->field('id,name')->select();
		$this->assign('hc_list',$hc_list);
	}

	/**
	 * 根据酒店获取合同
	 */
	public function get_hc(){
		$hc_list = M('HotelContract')->field('id,name')->where(array('h_id'=>I('post.hotel')))->select();
		ajax_return(1,'获取成功',$hc_list);
	}

	/**
     * 编辑数据
     */
    public function edit()
    {
        //添加/编辑展示之前准备数据
        $this->_before_edit_view();

        //获取详情
        $id = I('get.id');
        $info = D('OoOiHHcView')->getById($id);
        $this->assign($info);
        $redonly = I('get.redonly');
        if ($redonly) {
            $this->assign('redonly', $redonly);
        }

        $this->display();
    }

	/**
	 * 编辑前准备工作
	 */
	protected function _before_edit_view(){
		//空调
		$equipmenta_list = M('Equipment')->field('id,name')->where(array('type'=>3))->select();
		$this->assign('equipmenta_list',$equipmenta_list);
	}
}