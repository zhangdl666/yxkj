<?php
/**
 * Author: ' Silent
 * Time: 2017/9/13 17:10
 */

namespace Pc\Server;


use Pc\Controller\PHPSocketController;
use Pc\Model\UserObjModel;

class NewMessage
{
    /**
     * 处理未读消息
     * @param $left
     */
    public static function getLeftModule($left)
    {
        $userModel = new UserObjModel();
        $UserData = $userModel->getUser();
        switch ($UserData['role_id']) {
            // 平台工程经理
            case 5:
                foreach ($left as $key => &$val) {
                    switch ($val['method']) {
                        case 'EquipmentInstall/index':
                            // 查询是否有未安装的工单分配
                            $InstallData = M('OperOrder')->where(array('status' => 1, 'type' => 1))->field('id')->select();
                            if (!empty($InstallData)) {
                                $val['is_new'] = 1;
                            }
                            break;
                        case 'EquipmentUpkeep/index':
                            // 查询是否有保养的工单
                            $MaintainData = M('OperOrder')->where(array('status' => 1, 'type' => 2))->field('id')->select();
                            if (!empty($MaintainData)) {
                                $val['is_new'] = 1;
                            }
                            break;
                        case 'EquipmentMaintain/index':
                            // 查询是否有维修的工单
                            $RepairData = M('OperOrder')->where(array('status' => 1, 'type' => 3))->field('id')->select();
                            if (!empty($RepairData)) {
                                $val['is_new'] = 1;
                            }
                            break;
                    }
                }
                return $left;
            // 平台工程人员
            case 4:
                // 平台工程经理分配工单到我
                foreach ($left as $key => &$val) {
                    switch ($val['method']) {
                        case 'EquipmentInstall/index':
                            // 查询是否有未安装的工单分配
                            $InstallData = M('OperOrder')->where(array('status' => 2, 'u_id' => $UserData['id'], 'type' => 1))->field('id')->select();
                            if (!empty($InstallData)) {
                                $val['is_new'] = 1;
                            }
                            break;
                        case 'EquipmentUpkeep/index':
                            // 查询是否有保养的工单
                            $MaintainData = M('OperOrder')->where(array('status' => 2, 'u_id' => $UserData['id'], 'type' => 2))->field('id')->select();
                            if (!empty($MaintainData)) {
                                $val['is_new'] = 1;
                            }
                            break;
                        case 'EquipmentMaintain/index':
                            // 查询是否有维修的工单
                            $data = M('OperOrder')->where(array('status' => 2, 'u_id' => $UserData['id'], 'type' => 3))->field('id')->select();
                            if (!empty($data)) {
                                $val['is_new'] = 1;
                            }
                            break;
                    }
                }
                return $left;
            // 酒店工程经理
            case 11:
                foreach ($left as $key => &$val) {
                    switch ($val['method']) {
                        case 'EquipmentInstall/index':
                            // 查询是否有未安装的工单分配
                            $hotel_id = M('User')->where(array('id'=>$UserData['id']))->getField('hotel_id');
                            $InstallData = M('OperOrder')->where(array('status' => 3, 'h_id' => $hotel_id, 'type' => 1))->field('id')->select();
                            if (!empty($InstallData)) {
                                $val['is_new'] = 1;
                            }
                            break;
                        case 'EquipmentUpkeep/index':
                            // 查询是否有保养的工单
                            $hotel_id = M('User')->where(array('id'=>$UserData['id']))->getField('hotel_id');
                            $MaintainData = M('OperOrder')->where(array('status' => 3, 'h_id' => $hotel_id, 'type' => 2))->field('id')->select();
                            if (!empty($MaintainData)) {
                                $val['is_new'] = 1;
                            }
                            break;
                        case 'EquipmentMaintain/index':
                            // 查询是否有维修的工单
                            $hotel_id = M('User')->where(array('id'=>$UserData['id']))->getField('hotel_id');
                            $data = M('OperOrder')->where(array('status' => 3, 'h_id' => $hotel_id, 'type' => 3))->field('id')->select();
                            if (!empty($data)) {
                                $val['is_new'] = 1;
                            }
                            break;
                    }
                }
                return $left;
            // 酒店财务经理
            case 10:
                foreach ($left as $key => &$val) {
                    switch ($val['method']) {
                        case 'ReturnMoney/index':
                            // 查询是否有未结算的账单
                            $where['o.h_id'] = $_SESSION['USERINFO']['hotel_id'];
                            $where['o.status'] = 1;
                            $Returndata = M('ReturnMoney')
                                ->alias('o')
                                ->join('yx_hotel as h on h.id = o.h_id')
                                ->join('yx_accounts_type as t on t.id = o.at_id ')
                                ->join('yx_hotel_contract as c on c.id = o.hc_id ')
                                ->where($where)
                                ->field('o.*,h.name,t.type,t.price as type_price,c.name as contract_name,c.sno as contract_sno,c.ls_id')
                                ->order('o.mtime desc')
                                ->select();
                            if (!empty($Returndata)) {
                                $val['is_new'] = 1;
                            }
                            break;
                    }
                }
                return $left;
            case 6:
                foreach ($left as $key => &$val) {
                    switch ($val['method']) {
                        case 'ReturnMoney/index':
                            // 查询是否有未确认的账单
//                            $where['o.h_id'] = $_SESSION['USERINFO']['hotel_id'];
                            $where['o.status'] = 2;
                            $Returndata = M('ReturnMoney')
                                ->alias('o')
                                ->join('yx_hotel as h on h.id = o.h_id')
                                ->join('yx_accounts_type as t on t.id = o.at_id ')
                                ->join('yx_hotel_contract as c on c.id = o.hc_id ')
                                ->where($where)
                                ->field('o.*,h.name,t.type,t.price as type_price,c.name as contract_name,c.sno as contract_sno,c.ls_id')
                                ->order('o.mtime desc')
                                ->select();
                            if (!empty($Returndata)) {
                                $val['is_new'] = 1;
                            }
                            break;
                        case 'SaleGetmoney/index':
                            // 查询是否有未确认的账单
                            $datass = M('SaleGetmoney')->where(array('status'=>1))->select();
                            if (!empty($datass) && count($datass)>0) {
                                $val['is_new'] = 1;
                            }
                            break;
                        case 'Reimbursement/index':
                            $ReimbursementData=M('Reimbursement')->where(['status'=>2])->select();
                            if($ReimbursementData && count($ReimbursementData)>0){
                                $val['is_new'] = 1;
                            }
                    }
                }
                return $left;
                // 平台销售经理
            case 3:
                foreach ($left as $key => &$val) {
                    switch ($val['method']) {
                        case 'Reimbursement/index':
                            $users = M("User")->where(array('parent_id' => $_SESSION['USERINFO']['id'], 'role_id' => 2))->field('id')->select();
                            if(!empty($users)){
                                $user_ids = array();
                                foreach($users as $user){
                                    $user_ids[] = $user['id'];
                                }
                                if(!empty($user_ids)){
                                    $where['sale_id'] = array('in',$user_ids);    //报销人员ID
                                }
                            }
                            $where['status'] = array('in','1');
                            $data = M('Reimbursement')->where($where)->select();
                            if (!empty($data)) {
                                $val['is_new'] = 1;
                            }
                            break;
                    }
                }
                return $left;
//            case 2:
//                foreach ($left as $key => &$val) {
//                    switch ($val['method']) {
//                        case 'Reimbursement/index':
//                            $where['status'] = array('in','4');
//                            $where['sale_id'] = $_SESSION['USERINFO']['id'];
//                            $datas = M('Reimbursement')->where($where)->select();
//                            if (!empty($datas)) {
//                                // 发送系统消息
//                                $time = date('Y-m-d H:i:s',$datas['c_time']);
//                                $content = '你于'.$time.'申请的报销已被驳回,驳回原因:'.$datas['decline_remark'];
//                                has_oper($content,$_SESSION['USERINFO']['id']);
//                            }
//                            break;
//                    }
//                }
//                return $left;
            default :
                return $left;
        }

    }

    /**
     * 发送在线消息
     * 约定标识
     */
    public static function remind($type, $user_id = '')
    {

        switch ($type) {
            case 1:
                // 平台销售添加/编辑合同 -> 生成安装工单->给在线平台工程经理发送相关提醒
                $Userid = M('User')->where(array('status' => 1, 'role_id' => 5))->field('id')->select();
                if($Userid){
                    foreach ($Userid as $key => $val) {
                        $content = 'EquipmentInstall/index';
                        if($val['id']){
                            self::newMessage($val['id'], $content);
                        }
                    }
                }
                break;
            case 2:
                // 平台工程经理分配工单到平台工程人员->给在线平台工程人员发送相关提醒
                $content = 'EquipmentUpkeep/index';
                if($user_id){
                    self::newMessage($user_id, $content);
                }
                break;
            case 3:
                // 安装完成->酒店工程经理
                $content = 'EquipmentInstall/index';
                if($user_id) {
                    self::newMessage($user_id, $content);
                }
                break;
            case 4:
                // 保养完成->酒店工程经理
                $content = 'EquipmentUpkeep/index';
                if($user_id){
                    self::newMessage($user_id, $content);
                }
                break;
            case 5:
                // 维修完成-酒店工程经理
                $content = 'EquipmentMaintain/index';
                if($user_id){
                    self::newMessage($user_id, $content);
                }
                break;
            case 6:
                // 平台工程经理分配到平台工程人员->保养时间
                $content = 'EquipmentUpkeep/index';
                if($user_id){
                    self::newMessage($user_id, $content);
                }
                break;
            case 7:
                // 平台工程经理分配到平台工程人员->酒店报修
                $content = 'EquipmentMaintain/index';
                if($user_id){
                    self::newMessage($user_id, $content);
                }
                break;
            case 8:
                // 酒店财务->平台财务确认到账
                $Userid = M('User')->where(array('status' => 1, 'role_id' => 6))->field('id')->select();
                if($Userid){
                    foreach ($Userid as $key => $val) {
                        $content = 'ReturnMoney/index';
                        if($val['id']){
                            self::newMessage($val['id'], $content);
                        }
                    }
                }
                break;
            case 9:
                // 销售经理审核
                $content = 'ReturnMoney/index';
                if($user_id) {
                    self::newMessage($user_id, $content);
                }
                break;
            case 10:
                // 销售经理审核
                $content = 'Reimbursement/index';
                if($user_id){
                    self::newMessage($user_id, $content);
                }
                break;
        }
    }

    /**
     * 对在线用户显示未代办事项
     * to_uid指定为用户id
     */
    public static function newMessage($to_uid = '', $identity = '')
    {
        // 推送的url地址，使用自己的服务器地址
        $push_api_url = "http://".get_server_ip().":2121/";
        $post_data = array("type" => "publish", "content" => $identity, "to" => $to_uid);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $push_api_url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Expect:"));
        $return = curl_exec($ch);
        curl_close($ch);
    }

}