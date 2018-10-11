<?php
/**
 * OperOrderModel.class.php
 * 设备
 * @author: wy901216
 * @date: 2017/9/6  17:50
 */
namespace Pc\Model;
use Pc\Server\NewMessage;
use Pc\Server\PageModel;

class OperOrderModel extends BaseModel{
    //自动验证
    protected $_validate = array(
        array('img', 'require', '请上传确认单'),
    );

    public function getResultList($wheres){
        $this->alias('obj');
        $this->_setModel();
        $count = $this->where($wheres)->count();

        $listRows = C('PAGE_SIZE');
        $page = new PageModel($count, $listRows);  //自己获取请求中的页面直接使用
        $page->lastSuffix = false;
        $pageHtml = $page->show();

        $this->alias('obj');
        $this->_setModel();
        $re_datas = $this->where($wheres)->limit($page->firstRow,$page->listRows)->order('id desc')->select();
        //对数据进行处理
        $this->_handleRows($re_datas);
        foreach ($re_datas as &$value){
            /*if($value['type'] == 1){
                if(!empty($value['stime'])){
                    $value['datetime'] = date('Y-m-d',$value['stime']);
                }else{
                    $value['datetime'] = date('Y-m-d',$value['rtime']);
                }
            }else{
                $value['datetime'] = date('Y-m-d',$value['stime']);
            }*/
            $value['num_ratio'] = intval(($value['now_num']/$value['num']*100));
            if(!empty($value['etime']) && !empty($value['stime'])){
                $stime = date('Y-m-d',$value['stime']);
                $etime = date('Y-m-d',$value['etime']);
                if($stime == $etime){
                    $value['datetime'] = $stime;
                }else{
                    $value['datetime'] = $stime.'至'.$etime;
                }
            }elseif(!empty($value['stime'])){
                $value['datetime'] = date('Y-m-d',$value['stime']);
            }elseif(!empty($value['rtime'])){
                $value['datetime'] = date('Y-m-d',$value['rtime']);
            }
        }
        return array('pageHtml'=>$pageHtml,'items'=>$re_datas);
    }

    protected function _setModel(){
        $this->field('obj.*,b.name as hotel_name');
        $this->join('yx_hotel as b on b.id=obj.h_id');
    }


    public function getStatisticsData($wheres,$keyword,$estatus){
        //dump($wheres);
//        $rows = $this->where($wheres)->select();
//        $total_num = count($rows);       //总单数
        $where = array();
        $where['l.type'] = array('eq', $wheres['type']);
        //$where['l.status'] = array('gt',1);
        if($wheres['h_id']){
           $where['l.h_id'] = array('eq',$wheres['h_id']); 
       }        
        if($wheres['u_id']){
            $where['l.u_id'] = array('eq', $wheres['u_id']);
        }
        if($keyword){
            $where['p.name'] = array('like', '%' . $keyword . '%');
        }
        if($estatus){
            $where['l.status'] = array('eq', $estatus);
        }else{

        }
        //dump($where);exit;
        $rows = $this->alias('l')
                     ->where($where)
                     ->join('left join ' . C('DB_PREFIX') . HotelModel::TABLENAME_HOTEL . ' as p on p.id = l.h_id')
                     ->field('l.status,l.num,l.now_num')
                     ->select();
        //dump(M('')->_sql());exit;
        $total_num = count($rows);
        $distribute_num = 0;     //待分配数量
        $noinstall_num = 0;      //待安装数量
        $noconfirm_num = 0;      //待确认数量
        $finished_num = 0;       //已完成数量
        $installed_house_num = 0;    //已安装房间数
        $houses_total = 0;     //房间总数
        if(!empty($rows)){
            foreach($rows as $value){
                switch($value['status']){
                    case 1 :
                        $distribute_num++;
                        break;
                    case 2 :
                        $noinstall_num++;
                        break;
                    case 3 :
                        $noconfirm_num ++;
                        break;
                    case 4 :
                        $finished_num++;
                        break;
                }
                $houses_total+=$value['num'];
                $installed_house_num+=$value['now_num'];
            }
        }
        //$nostall_house_num = $houses_total - $installed_house_num;
        return array('distribute_num'=>$distribute_num,'noinstall_num'=>$noinstall_num,'noconfirm_num'=>$noconfirm_num,'finished_num'=>$finished_num,'total_num'=>$total_num,'installed_house_num'=>$installed_house_num,'houses_total'=>$houses_total);
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
                            $eqm = $value;
                            if($room_note[$i]){
                                $eqm['note']  = $room_note[$i];
                            }else{
                                $this->error = '请填写维修原因';
                                return false;
                            }
                            
                            $eqm['oo_id'] = $re_status;
                            $eqm['status']= 3;
                            $eqm['ctime'] = time();
                            $epm_data[] = $eqm;
                            break;
                        }
                    }
                }

                $re_status = M('OperInfo')->addAll($epm_data);
            }

            /* 平台工程经理做处理 */
            $user_idarr = M('User')->field('id')->where(array('role_id'=>5,'status'=>1))->select();
            if($user_idarr){
                $oper_title = '待处理维修工单';
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

                //操作消息消失
                $ooinfo = $this->field('type,sno,h_id')->getById($this_data['id']);
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
                $user_idarr = M('User')->field('id')->where(array('hotel_id'=>$ooinfo['h_id'],'role_id'=>11,'status'=>1))->select();
                if($user_idarr){
                    $user_ids = implode(',', i_array_column($user_idarr,'id'));
                    has_oper($msg_content,$user_ids,$oper_url,$oper_title);  
                }
               
            }
            //确认工单
            elseif($this_data['status'] == 4){
                $this_data['etime'] = time();

                // 查看工单类型
                $operOrder = $this->where(array('id' => $this_data['id']))->field('id,sno,type,h_id')->find();
                //操作消息消失
                not_oper($operOrder['sno'],session('USERINFO.id'));
                
                if ($operOrder) {
                    $uid = M('User')->where(array('hotel_id' => $operOrder['h_id'], 'role_id' => 11))->getField('id');
                    // 安装完成
                    if ($operOrder['type'] == 1) {
                        NewMessage::remind(3, $uid);
                    } else if ($operOrder['type'] == 2) {
                        NewMessage::remind(4, $uid);
                    } else if ($operOrder['type'] == 3) {
                        NewMessage::remind(5, $uid);
                    }
                }

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