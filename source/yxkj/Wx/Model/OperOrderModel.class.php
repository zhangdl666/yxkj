<?php
/**
 * EquipmentUpkeepController.class.php
 * 后台 设备保养
 * @author baddl
 * @date   2017-09-23 11:23
 */
namespace Wx\Model;
use Wx\Model\BaseModel;
use Pc\Server\NewMessage;

class OperOrderModel extends BaseModel{
	/**
	 * 对数据进行处理
	 * @param  array  $datas 	数据
	 */
	protected function _handleRows(&$datas){
		$hid_arr = i_array_column($datas['datas'],'h_id');
		$hotel_ids = M('Hotel')->field('id,name,img')->where(array('id'=>array('in',$hid_arr)))->select();
		foreach ($datas['datas'] as $key => &$value) {
			foreach ($hotel_ids as $hkey => $hval) {
				if($value['h_id'] == $hval['id']){
					$value['h_name'] = $hval['name'];
                    if($hval['img']){
                        $value['h_img']  = explode(',', $hval['img']);
                        $value['h_img']  = $value['h_img'][0];
                    }
					break;
				}
			}

			//时间处理
            /*if($value['type'] == 1){
                if(!empty($value['stime'])){
                    $value['datetime'] = date('Y-m-d',$value['stime']);
                }else{
                    $value['datetime'] = date('Y-m-d',$value['rtime']);
                }
            }else{
                $value['datetime'] = date('Y-m-d',$value['stime']);
            }*/

			if(!empty($value['etime']) && !empty($value['stime'])){
                $stime = date('Y/m/d',$value['stime']);
                $etime = date('Y/m/d',$value['etime']);
                if($stime == $etime){
                    $value['datetime'] = $stime;
                }else{
                    $value['datetime'] = $stime.'至'.$etime;
                }
			}elseif(!empty($value['stime'])){
				$value['datetime'] = date('Y/m/d',$value['stime']);
			}elseif(!empty($value['rtime'])){
				$value['datetime'] = date('Y/m/d',$value['rtime']);
			}else{
                $value['datetime'] = '';
            }
		}
	}

	/**
	 * 安装/保养/维修
	 */
	public function oioperation($data){
		$this->startTrans();

		if($data['id']){
			//保养或维修
			$data['status'] = 4;
            $oi_where = $data;
            unset($oi_where['eo_id']);
            //是否已经处理
            $is_oper = M('OperInfo')->where($oi_where)->getField('id');
            //当前工单是否打回的
            $roll_back = $this->where(array('id'=>$data['oo_id']))->getField('roll_back');
            if($is_oper && empty($roll_back)){
                $this->error = '此设备已处理';
                return false;
            }

			$data['etime'] = time();
			$re_sult = M('OperInfo')->save($data);
		}else{
			$rsno = trim($data['room_sno']);
			$esno = trim($data['equipment_sno']);
			if(empty($rsno)){
				$this->error = '房间号不能为空';
				return false;
			}elseif(empty($esno)){
				$this->error = '请扫描设备二维码';
				return false;
			}
			//设备号是否已经存在
			$is_exsits = M('OperInfo')->where(array('equipment_sno'=>$data['equipment_sno'],'status'=>1))->getField('id');
			if($is_exsits){
				$this->error = '此设备已安装';
				return false;
			}
            //查看此酒店该房间是否已经安装
            $oi_id = M('OperInfo')->where(array('oo_id'=>$data['oo_id'],'floor'=>$data['floor'],'room_sno'=>$data['room_sno']))->getField('id');
			//已经安装,替换
            if($oi_id){
                //替换
                $data['status'] = 1;
                $data['etime'] = time();
                $data['id'] = $oi_id;
                $re_sult = M('OperInfo')->save($data);
            }else{
                //查看是否已经全部安装完
                $oorder = $this->field('now_nume,nume')->where(array('id'=>$data['oo_id']))->find();
                if($oorder['now_nume'] == $oorder['nume']){
                    $this->error = '安装个数已达到最大值';
                    return false;
                }
                //安装
                $data['status'] = 1;
                $data['ctime'] = time();
                $data['etime'] = time();
                $re_sult = M('OperInfo')->add($data);
            }
            

            /* 酒店ID加入设备表 */
            //酒店id
            $hotel_id = M('OperOrder')->getFieldById($data['oo_id'],'h_id');
            M('Device')->where(array('mac'=>$data['equipment_sno']))->save(array('h_id'=>$hotel_id));
            /* 签约设备数 */ 
            //销售
           /* $h_id = $this->getFieldById($data['oo_id'],'h_id');
            $sale_id = M('HotelGet')->where(array('h_id'=>$h_id,'is_default'=>1))->getField('sale_id');
            M('SaleExt')->where(array('u_id'=>$sale_id))->setInc('equipment_num');*/
		}

		if(!$re_sult){
			$this->rollback();
			return false;
		}

		/*if($data['id']){
			$ooid = M('OperInfo')->getFieldById($data['id'],'oo_id');
		}else{*/
			$ooid = $data['oo_id'];
		/*}*/

		//是否已经处理过设备了
		$is_exsitse = $this->where(array('id'=>$ooid))->getField('stime');
		if(!$is_exsitse){
			//更新处理设备数及开始处理时间
			$re_sult = $this->where(array('id'=>$ooid))->save(array('stime'=>time(),'now_num'=>array('exp','now_num+1'),'now_nume'=>array('exp','now_nume+1')));

			//更新合同中设备安装时间
			if(empty($data['id'])){
				$hc_id = $this->getFieldById($ooid,'hc_id');
				M('HotelContract')->save(array('id'=>$hc_id,'sinstall'=>time()));
			}
		}else{
            //已经安装,替换
            if(!$oi_id){
                //保养和维修
                if($is_oper && $roll_back){
                    ;
                }else{
                    //更新处理设备数
                    $re_sult = $this->where(array('id'=>$ooid))->save(array('now_num'=>array('exp','now_num+1'),'now_nume'=>array('exp','now_nume+1')));  
                }
            }
		}

		if($re_sult){
			$this->commit();
		}else{
			$this->rollback();
		}
		return $re_sult;
	}

	/**
	 * 对添加/编辑数据进行操作
	 */
	public function operation(){
		$id = I('post.id');
		$this->startTrans();

		//添加
		if(empty($id)){
			if($this->data['type'] == 3){
                $room_sno = I('post.room_sno');
                if(!$room_sno){
                    $this->error = '请选择要维修的房间';
                    return false;
                }
                $room_note= I('post.note');
                $equipment_infos = M('OperInfo')->field('id,equipment_sno,rt_id,floor,room_sno,is_window,orientation,place,e_id')->where(array('id'=>array('in',$room_sno)))->select();

                $oo_sno = get_reimbursement_sn();
                $this->data['sno']  = $oo_sno;
                $this->data['num']  = count($room_sno);
                $this->data['nume']  = count($room_sno);
                $this->data['ctime']= time();
            }
            $re_status = $this->add();

            if($re_status && !empty($equipment_infos)){
                foreach ($equipment_infos as $value) {
                    for($i=0; $i < count($room_sno); $i++){
                        if($room_sno[$i] == $value['id']){
                            unset($value['id']);
                            $epm = $value;
                            if($room_note[$i]){
                                $epm['note'] = $room_note[$i];  
                            }else{
                                $this->error = '请填写维修原因';
                                return false;
                            }
                            
                            $epm['oo_id']= $re_status;
                            $epm['status']= 3;
                            $eqm['ctime'] = time();
                            $epm_data[] = $epm;
                            break;
                        }
                    }
                }

                $re_status = M('OperInfo')->addAll($epm_data);
            } 

            /* 平台工程经理做处理 */
            $user_idarr = M('User')->field('id')->where(array('role_id'=>5,'status'=>1))->select();
            if($user_idarr){
                $oper_title  = '待处理维修工单';
                $msg_content = '您有一条维修工单号：'.$oo_sno.' 待处理';
                $user_ids = implode(',', i_array_column($user_idarr,'id'));
                $oper_url = 'EquipmentMaintain/index';
                has_oper($msg_content,$user_ids,$oper_url,$oper_title);
            }
		}
		//编辑
		else{
			$this_data = $this->data;
			//上传凭证
            if($this->data['status'] == 3){
                if(!$this->data['img']){
                    $this->error = '请上传确认单';
                    return false;
                }

                $ooinfo = $this->field('type,sno,h_id')->getById($this_data['id']);
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
                $user_id = M('User')->where(array('hotel_id'=>$ooinfo['h_id'],'role_id'=>11,'status'=>1))->getField('id');
                if($user_id){
                    has_oper($msg_content,$user_id,$oper_url,$oper_title);
                }
                
            }
            //确认工单
            elseif($this->data['status'] == 4){
            	// 查看工单类型
                $operOrder = $this->where(array('id' => $this_data['id']))->field('id,sno,type,h_id')->find();
                //操作消息消失
                not_oper($operOrder['sno'],session('USERINFO.id'));

                if ($operOrder) {
                    $uid = M('User')->where(array('hotel_id' => $operOrder['h_id'], 'role_id' => 11,'status'=>1))->getField('id');
                    // 安装完成
                    if ($operOrder['type'] == 1) {
                        NewMessage::remind(3, $uid);
                    } else if ($operOrder['type'] == 2) {
                        NewMessage::remind(4, $uid);
                    } else if ($operOrder['type'] == 3) {
                        NewMessage::remind(5, $uid);
                    }
                }

                $this_data['etime'] = time();

                //是否设备安装工单
                $oo_info = $this->field('type,now_num,h_id,hc_id')->getById(I('post.id'));
                if($oo_info['type'] == 1){
                    $sale_id = M('HotelGet')->where(array('h_id'=>$oo_info['h_id'],'is_default'=>1))->getField('sale_id');
                    //更新销售扩展信息 签约设备数
                    M('SaleExt')->where(array('u_id'=>$sale_id))->setInc('equipment_num',$oo_info['now_num']);
                
                	//更新合同结束安装设备时间
                    M('HotelContract')->save(array('id'=>$oo_info['hc_id'],'einstall'=>time()));
                }
            }elseif($this->data['status'] == 2){
                $is_exsits = $this->where($this_data)->getField('id');
                if($is_exsits){
                   return true; 
                }

                $ooinfo = $this->field('type,sno,h_id,u_id')->getById($this_data['id']);
                //操作消息消失
                if($ooinfo['u_id']){
                    not_oper($ooinfo['sno'],$ooinfo['u_id']);
                }else{
                    not_oper($ooinfo['sno'],session('USERINFO.id'));
                }

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
                //平台工程人员做处理
                if($this_data['u_id']){
                    has_oper($msg_content,$this_data['u_id'],$oper_url,$oper_title);
                }
            }

            $is_exsits = $this->where($this_data)->getField('id');
            $re_status = true;
            if(!$is_exsits){
                $re_status = $this->save($this_data);
            }
		}

		if($re_status){
            $this->commit();
        }else{
            $this->rollback();
        }

        return $re_status;
	}

    /**
     * 打回处理
     */
    public function roll_back_oper(){
        $data = I('post.');
        if(empty($data['remark'])){
            $this->error = '请填写打回原因';
            return false;
        }
        $this->startTrans();

        $ob_data['ctime'] = time();
        $ob_data['oo_id'] = $data['id'];
        $ob_data['content']= $data['remark'];
        $ob_status = M('OrderBack')->add($ob_data);

        $oo_data['id'] = $data['id'];
        $oo_data['status'] = 2;
        $oo_data['roll_back'] = 1;
        $re_status = $this->save($oo_data);

        if($ob_status && $re_status){
            $this->commit();

            $ooinfo = $this->field('type,sno,h_id,u_id')->getById($data['id']);
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
            //平台工程人员做处理
            if($ooinfo['u_id']){
                has_oper($msg_content,$ooinfo['u_id'],$oper_url,$oper_title);
            }

            return true;
        }else{
            $this->rollback();
            return false;
        }
    }
}