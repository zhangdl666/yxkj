<?php
/**
 * OperInfoModel.class.php
 * 房间与设备信息
 * @author  baddl
 * @datetime: 2018-01-29 22:30
 */
namespace Pc\Model;
use Pc\Server\PageModel;

class OperInfoModel extends BaseModel{
	/**
	 * 获取列表数据
	 * @param  array  $wheres 	查询条件
	 * @return array
	 */
	public function getResultList($wheres,$orders='id desc'){
		$count = D('OoOiHHcView')->where($wheres)->count('distinct(equipment_sno)');
		$listRows = C('PAGE_SIZE');
        $page = new PageModel($count, $listRows);  //自己获取请求中的页面直接使用
        $page->lastSuffix = false;
        $pageHtml = $page->show();

		$re_datas = D('OoOiHHcView')->where($wheres)->limit($page->firstRow,$page->listRows)->order($orders)->group('equipment_sno')->select();
		//对数据进行处理
		$this->_handleRows($re_datas);

		return array('pageHtml'=>$pageHtml,'items'=>$re_datas);
	}

	/**
	 * 对添加/编辑数据进行操作
	 */
	public function operation(){
		$id = I('post.id');
		//添加
		if(empty($id)){
			$this->data['ctime'] = time();
			$re_status = $this->add(); 
		}
		//编辑
		else{
			$data = I('post.');
			$data['status'] = 1;
            $is_exsits = $this->where($data)->getField('id');
            if($is_exsits){
                return true;
            }
            //旧MAC地址
            $old_mac = $this->getFieldById($id,'equipment_sno');
            if($old_mac != $data['equipment_sno']){
            	//新设备是否已经存在
            	$new_is_exsits = $this->where(array('equipment_sno'=>$data['equipment_sno']))->getField('id');
            	if($new_is_exsits){
            		$this->error = '此设备已存在,请更换设备';
            		return false;
            	}
            }
            //更改所有此设备对应全部订单处理信息
            $oi_ids = $this->field('id')->where(array('equipment_sno'=>$old_mac))->select();
			$oi_idarr = i_array_column($oi_ids,'id');
			unset($data['id'],$data['status']);
			$re_status = $this->where(array('id'=>array('in',$oi_idarr)))->save($data);
		}

		return $re_status;
	}
}