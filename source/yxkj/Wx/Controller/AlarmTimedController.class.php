<?php
/**
 * Created by PhpStorm.
 * User: rzhd
 * Date: 2018/9/11
 * Time: 9:28
 */

namespace Wx\Controller;


use Think\Controller;

class AlarmTimedController extends Controller{
    /***********************************************告警信息数据开始****************************************************/
    /*
     * 告警数据写入数据库
     * ***/
    public function alarmData(){
        global $db;

        //获取告警等级设置数据
        $sql = 'select mac,h_id from yx_alarm_pm25 order by type asc';
        $Sets = $db->query($sql);
        $alarmSets = array();
        foreach($Sets as $Set){
            $alarmSets[$Set['h_id']][] = $Set;
        }

        //获取酒店数据
        $h_ids = $db->select('mac,h_id')->from('yx_device')->where("h_id != ''")->query();

        //获取告警信息表中设备对应的最新告警信息
        $sql = 'select * from yx_alarm_pm25_info where is_end<>2 order by id desc group by equipment_sno';
        $alarmdatas = $db->query($sql);

        $alarms = array();
        foreach($alarmdatas as $alarmdata){
            $alarms[$alarmdata['equipment_sno']] = $alarmdata;
        }

        foreach($h_ids as $value){
            $mark = 0;
            //获取酒店对应的所有设备
            $sql = "select * from upload_device_info_".$value['h_id']."where h_id=".$value['h_id']." order by id desc group device_code";
            $datas = $db->query($sql);
            foreach($datas as $data){
                if($data['power'] == 1){  //当前数据设备开启状态
                    $info = $alarms[$data['device_code']];

                    if(!empty($info)){    //有设备信息
                        if($info['is_end'] == 0) {    //未开始
                            foreach($alarmSets[$value['h_id']] as $alarmSet){
                                if($info['start_pm25'] >= $alarmSet['start']){
                                    if($data['indoor_pm'] >= $alarmSet['start']){
                                        $end_time = $data['upload_time'];
                                        $len_time = $end_time-$info['start_time'];
                                        if($len_time >= $alarmSet['time']){     //PM2.5满足，持续时长也满足
                                            $sql = "update yx_alarm_pm25_info set end_pm25=".$data['indoor_pm'].",end_time=".$end_time.",len_time=".$len_time.",ap_id=".$alarmSet['id'].",is_end=1 where id=".$info['id'];
                                            $db->query($sql);
                                            $mark = 1;
                                            break;

                                        }else{      //PM2.5满足，持续时长不满足
                                            $sql = "update yx_alarm_pm25_info set end_pm25=".$data['indoor_pm'].",end_time=".$end_time.",len_time=".$len_time." where id=".$info['id'];
                                            $db->query($sql);
                                            break;

                                        }

                                    }else{
                                        //删除当前数据，新增一条数据，也可以直接修改这条数据
                                        $sql = "update yx_alarm_pm25_info set start_pm25=".$data['indoor_pm'].",start_time=".$data['upload_time'].",ap_id=0 where id=".$info['id'];
                                        $db->query($sql);
                                        break;
                                    }
                                }

                            }

                        }elseif($info['is_end'] == 1) {    //正在进行
                            foreach($alarmSets[$value['h_id']] as $alarmSet){
                                if($data['indoor_pm'] >= $alarmSet['start']){
                                    if($alarmSet['id'] == $info['ap_id']){
                                        //修改当前的数据的信息
                                        $end_time = $data['upload_time'];
                                        $len_time = $end_time-$info['start_time'];

                                        $sql = "update yx_alarm_pm25_info set end_pm25=".$data['indoor_pm'].",end_time=".$end_time.",len_time=".$len_time." where id=".$info['id'];
                                        $db->query($sql);
                                        break;
                                    }else{
                                        //修改当前数据的信息状态为结束，并新插入一条数据
                                        $sql = "update yx_alarm_pm25_info set is_end=2 where id=".$info['id'];
                                        $db->query($sql);
                                        $sql2 = "insert into yx_alarm_pm25_info (equipment_sno,start_pm25,start_time,len_time,is_end,ap_id) values('".$data['device_code']."',".$data['indoor_pm'].",".$data['upload_time'].",0,0,0)";
                                        $db->query($sql2);
                                        $mark = 1;
                                        break;
                                    }

                                }
                            }

                        }else{    //关闭状态
                            foreach($alarmSets[$value['h_id']] as $alarmSet){
                                if($data['indoor_pm'] >= $alarmSet['start']){
                                    $sql = "update yx_alarm_pm25_info set start_pm25=".$data['indoor_pm'].",start_time=".$data['upload_time'].",is_end=0 where id=".$info['id'];
                                    $db->query($sql);
                                    break;
                                }
                            }

                        }

                    }else{     //没有设备信息
                        $status = 0;
                        foreach($alarmSets[$value['h_id']] as $alarmSet){
                            if($data['indoor_pm'] >= $alarmSet['start']){
                                $status = 1;
                                break;
                            }
                        }
                        if($status == 1){
                            $sql = "insert into yx_alarm_pm25_info (equipment_sno,start_pm25,start_time,len_time,is_end,ap_id) values('".$data['device_code']."',".$data['indoor_pm'].",".$data['upload_time'].",0,0,0)";
                            $db->query($sql);
                            break;
                        }
                    }



                }else{    //当前设备关闭状态
                    $info = $alarms[$data['device_code']];
                    if(!empty($info)){   //有设备信息
                        if($info['is_end'] == 0) {    //未开始
                            //修改数据为关闭状态  清空其他的字段
                            $sql = "update yx_alarm_pm25_info set start_pm25=0,start_time=0,len_time=0,is_end=3,ap_id=0 where id=".$info['id'];
                            $db->query($sql);
                            break;
                        }elseif($info['is_end'] == 1) {    //正在进行
                            //数据库数据修改为结束状态，新插入一条数据
                            $sql = "update yx_alarm_pm25_info set is_end=2 where id=".$info['id'];
                            $db->query($sql);
                            $sql2 = "insert into yx_alarm_pm25_info (equipment_sno,start_pm25,len_time,is_end,ap_id) values('".$data['device_code']."',0,0,3,0)";
                            $db->query($sql2);
                            break;
                        }else{
                            break;
                        }

                    }else{    //没有设备信息
                        $sql = "insert into yx_alarm_pm25_info (equipment_sno,start_pm25,len_time,is_end,ap_id) values('".$data['device_code']."',0,0,3,0)";
                        $db->query($sql);
                        break;
                    }
                }

            }

            if($mark == 1){
                //推消息
                $this->sendMessage($value['h_id']);
            }
        }

    }

    /*
     * 推送告警信息
     * 推送的对象：酒店财务经理、酒店工程经理、酒店总经理、酒店信息维护人员
     * role_id=10、11、12、13
     * ****/
    public function sendMessage($h_id){
        global $db;

        $sql = "select id from yx_module where method='Alarm/index' and status=1";
        $module = $db->query($sql);
        //$roles = M("Role")->where('FIND_IN_SET('.$module_id.',oper_module)')->select();
        $sql = "select * from yx_role where 'FIND_IN_SET(".$module[0]['id'].",oper_module)'";
        $roles = $db->query($sql);
        $role_ids = array();
        foreach($roles as $role){
            $role_ids[] = $role['id'];
        }

        if(!empty($role_ids)){
            $sql = "select id from yx_user where status=1 and type=2 and  and hotel_id=".$h_id." and role_id in ".$role_ids;
            $users = $db->query($sql);
            $uids = array();
            foreach($users as $user){
                $uids[] = $user['id'];
            }
            $uids = implode(',',$users);
            has_oper($content='您有新的告警信息需查看',$uids,'Alarm/index','查看提醒','查看消息');
        }
    }
    /***********************************************告警信息数据结束****************************************************/

    /**
     * 赋初始值
     */
    public function set_start_val(){
        $alarm_pm25_list = M('AlarmPm25Info')->field('id,start_time,end_time,c_time,up_time')->select();
        foreach ($alarm_pm25_list as $key => $value) {
            if(empty($value['c_time']) && empty($value['up_time'])){
                M('AlarmPm25Info')->save(array('id'=>$value['id'],'c_time'=>$value['start_time'],'up_time'=>$value['end_time']));
            }elseif(empty($value['c_time'])){
                M('AlarmPm25Info')->save(array('id'=>$value['id'],'c_time'=>$value['start_time']));
            }elseif(empty($value['up_time'])){
                M('AlarmPm25Info')->save(array('id'=>$value['id'],'up_time'=>$value['end_time']));
            }
        }

    }
}
