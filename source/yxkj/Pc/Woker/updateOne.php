<?php
/**
 * 修改时间,重启进程
 * 更新当天数据
 * @author: ' Silent
 * @time: 2018/1/31 16:11
 */

use Workerman\Worker;

require_once __DIR__ . '/Workerman/Autoloader.php';
date_default_timezone_set("PRC");

class updateData
{
    /**
     * 设置定时器更新数据  20秒一次
     * @author ' Silent <1136359934@qq.com>
     */
    public function getUpdate()
    {
        $time_intervalone = 20;
        \Workerman\Lib\Timer::add($time_intervalone, array($this, 'getData'), false);
    }

    /**
     * 设置定时器 告警数据  20秒一次
     * @author  baddl 
     * @date    2018-09-23 16:55
     */
    public function getalarmData(){
        $time_intervalone = 20;
        \Workerman\Lib\Timer::add($time_intervalone, array($this, 'alarmData'), false);
    }

    /**
     * 每10分钟更新数据
     * @author ' Silent <1136359934@qq.com>
     */
    public function getUpTimes()
    {
        $time_intervalone = 600;
        \Workerman\Lib\Timer::add($time_intervalone, array($this, 'getTimes'), false);
    }

    /**
     * 设置定时删除数据 20秒一次
     * @author ' Silent <1136359934@qq.com>
     */
    public function elkData()
    {
        $time_intervalone = 20;
        \Workerman\Lib\Timer::add($time_intervalone, array($this, 'delData'), false);
    }

    /**
     * 保存数据
     * @author ' Silent <1136359934@qq.com>
     */
    public function getTimes()
    {
        \Workerman\Http\Http::get('http://yxkj.irzhd.com/RunInfo/getCurlHotelId');
    }

    /**
     * 定时删除数据
     * @author ' Silent <1136359934@qq.com>
     */
    public function delData()
    {
        $beginTodays = mktime(14, 0, 0, date('m'), date('d'), date('Y'));
        // 如果当前时间小于14点
        if (time() < $beginTodays) {
            // 获取昨天14点时间和现在时间
            $beginToday = mktime(14, 0, 0, date('m'), date('d'), date('Y'));
            $beginToday = strtotime(date('Y-m-d H:i:s', strtotime("-1 day", $beginToday)));
            $endToday = time();
        } else {
            // 获取今天14点时间到明天时间
            $beginToday = mktime(14, 0, 0, date('m'), date('d'), date('Y'));
            $endToday = strtotime(date('Y-m-d H:i:s', strtotime("+1 day", $beginToday)));
        }
        global $db;
        $data = $db->select('mac,h_id')->from('yx_device')->where("h_id != ''")->query();
        $num = $this->listArrayKey($data, 'h_id');
        foreach ($num as $e => $l) {
            $db->delete("yx_upload_device_info_" . $l['h_id'])->where('upload_time < ' . $beginToday)->query();
        }
    }

    /**
     * 拉取数据
     * @author ' Silent <1136359934@qq.com>
     */
    public function getData()
    {
        global $db;
        global $redisChat;
        $data = $db->select('mac,h_id')->from('yx_device')->where("h_id != ''")->query();
        $num = $this->listArrayKey($data, 'h_id');

        foreach ($num as $e => $l) {
            // 创建表名
            $this->creataTable($l['h_id']);
            // 添加数据开始
            $macdata = $db->select('mac')->from('yx_device')->where("h_id = " . $l['h_id'])->query();
            // 循环开始添加数据
            $str = '';
            foreach ($macdata as $keys => $vals) {
                if ($keys >= 1) {
                    $str .= '|' . $vals['mac'];
                } else {
                    $str .= $vals['mac'];
                }
            }
            $params = array('CompanyToken' => '523c374d-fa46-d6a7-a068-d80d88612dfc', 'DeviceMacs' => $str, 'DataType' => 'ALL', 'Info' => '1',);
            $url = "http://api.bjhike.com/DeviceData_Batch";
            $DeviceData = json_decode(Workerman\Http\Http::post($url, $params), true);
            $listArray = array();
            $beginTodays = mktime(14, 0, 0, date('m'), date('d'), date('Y'));
            if (time() < $beginTodays) {
                // 获取昨天14点时间和现在时间
                $beginToday = mktime(14, 0, 0, date('m'), date('d'), date('Y'));
                $beginToday = strtotime(date('Y-m-d H:i:s', strtotime("-1 day", $beginToday)));
                $endToday = time();
            } else {
                // 获取今天14点时间到明天时间
                $beginToday = mktime(14, 0, 0, date('m'), date('d'), date('Y'));
                $endToday = strtotime(date('Y-m-d H:i:s', strtotime("+1 day", $beginToday)));
            }
            $indoor_pm = 0;
            $outdoor_pm = 0;
            foreach ($DeviceData['设备数据'] as $key => $val) {
                $uploadtime = strtotime(strtr($val['上传时间'], array(".0" => '')));
                if ($uploadtime > $beginToday && $uploadtime < $endToday) {
                    // redis设备保存数据
                    $keyone = date('Y-m-d/H') . '_' . $l['h_id'] . '_' . $val['设备地址'];
                    $datas = array('time' => $keyone, 'indoor_pm' => 0, 'outdoor_pm' => 0, 'num' => 1, 'dev_code' => $val['设备地址']);
                    if ($redisChat->exists($keyone)) {
                        $redisChat->hIncrBy($keyone, 'indoor_pm', $val['检测PM2.5']);
                        $redisChat->hIncrBy($keyone, 'outdoor_pm', $val['室外PM2.5']);
                        $redisChat->hIncrBy($keyone, 'num', 1);
                    } else {
                        $redisChat->hMset($keyone, $datas);
                        // 设置过期时间(保存三个月零十秒)
                        $redisChat->expire($keyone, 7689610);
                        $redisChat->hIncrBy($keyone, 'indoor_pm',$val['检测PM2.5']);
                        $redisChat->hIncrBy($keyone, 'outdoor_pm', $val['室外PM2.5']);
                        $redisChat->hIncrBy($keyone, 'num', 1);
                    }
                    $indoor_pm += $val['检测PM2.5'];
                    $outdoor_pm += $val['室外PM2.5'];
                    $listArray[$key]['device_code'] = $val['设备地址'];
                    $listArray[$key]['upload_times'] = strtr($val['上传时间'], array(".0" => ''));
                    $listArray[$key]['upload_time'] = strtotime(strtr($val['上传时间'], array(".0" => '')));
                    $listArray[$key]['online'] = $val['online'];
                    $listArray[$key]['power'] = $val['power'];
                    $listArray[$key]['indoor_pm'] = $val['检测PM2.5'];
                    $listArray[$key]['outdoor_pm'] = $val['室外PM2.5'];
                    $listArray[$key]['timed'] = $val['timed'];
                    $listArray[$key]['timeopen'] = $val['timeopen'];
                    $listArray[$key]['timeoff'] = $val['timeoff'];
                    $listArray[$key]['speed'] = $val['speed'];
                    $listArray[$key]['model'] = $val['model'];
                    $listArray[$key]['arofene'] = $val['甲醛'];
                    $listArray[$key]['tvoc'] = $val['TVOC'];
                    $listArray[$key]['temperature'] = $val['温度'];
                    $listArray[$key]['cotwo'] = $val['CO2'];
                    $listArray[$key]['humidity'] = $val['湿度'];
                    $listArray[$key]['ctime'] = time();
                    // 三个月后的时间
                    $listArray[$key]['validity'] = strtotime("-0 year +3 month -0 day");
                }
            }
            // redis,每个小时存到redis里面
            $keytwo = date('Y-m-d/H') . '_' . $l['h_id'];
            $data = array('time' => $keytwo, 'indoor_pm' => 0, 'outdoor_pm' => 0, 'num' => 1, 'hotel_id' => $l['h_id']);
            if ($redisChat->exists($keytwo)) {
                $redisChat->hIncrBy($keytwo, 'indoor_pm', $indoor_pm);
                $redisChat->hIncrBy($keytwo, 'outdoor_pm', $outdoor_pm);
                $redisChat->hIncrBy($keytwo, 'num', count($DeviceData['设备数据']));
            } else {
                $redisChat->hMset($keytwo, $data);
                // 设置过期时间(保存三个月零十秒)
                $redisChat->setTimeout($keytwo, 7689610);
                $redisChat->hIncrBy($keytwo, 'indoor_pm', $indoor_pm);
                $redisChat->hIncrBy($keytwo, 'outdoor_pm', $outdoor_pm);
                $redisChat->hIncrBy($keytwo, 'num', count($DeviceData['设备数据']));
            }

            foreach ($listArray as $keytwo => $valtwo) {
                // 放入redis里面
                $db->insert('yx_upload_device_info_' . $l['h_id'])->cols(array(
                    'h_id' => $l['h_id'],
                    'device_code' => $valtwo['device_code'],
                    'upload_times' => $valtwo['upload_times'],
                    'upload_time' => $valtwo['upload_time'],
                    'online' => $valtwo['online'],
                    'power' => $valtwo['power'],
                    'indoor_pm' => $valtwo['indoor_pm'],
                    'outdoor_pm' => $valtwo['outdoor_pm'],
                    'timed' => $valtwo['timed'],
                    'timeopen' => $valtwo['timeopen'],
                    'timeoff' => $valtwo['timeoff'],
                    'speed' => $valtwo['speed'],
                    'model' => $valtwo['model'],
                    'arofene' => $valtwo['arofene'],
                    'tvoc' => $valtwo['tvoc'],
                    'temperature' => $valtwo['temperature'],
                    'cotwo' => $valtwo['cotwo'],
                    'humidity' => $valtwo['humidity'],
                    'ctime' => time(),
                    'validity' => $valtwo['validity'],
                ))->query();

            }
            // 处理逻辑,将每个小时作为一个主键,外面空气和室内空气递增,插入次数递增
        }

        $this->alarmData();
    }

    /**
     * 创建表
     * @author ' Silent <1136359934@qq.com>
     */
    public function creataTable($tableFix)
    {
        // 创建数据表
        $sql = "CREATE TABLE IF NOT EXISTS `yx_upload_device_info_" . $tableFix . "` (
                      `id` int(10) NOT NULL AUTO_INCREMENT COMMENT '自增id',
                      `h_id` int(10) NOT NULL  COMMENT '酒店id',
                      `device_code` varchar(30) NOT NULL COMMENT '设备码',
                      `upload_times` varchar(30) NOT NULL COMMENT '上传时间-格式化时间',
                      `upload_time` int(11) NOT NULL COMMENT '上传时间',
                      `online` tinyint(1) NOT NULL COMMENT '在线状态 1=在线 0=离线',
                      `power` tinyint(1) NOT NULL COMMENT '开机状态 1=开机 0=关机',
                      `indoor_pm` char(10) NOT NULL COMMENT '室内pm2.5',
                      `outdoor_pm` char(10) NOT NULL COMMENT '室内pm2.5',
                      `timed` bigint(1) NOT NULL COMMENT '定时开关 1=启用 0=未启用',
                      `timeopen` char(10) NOT NULL COMMENT '定时开机时间 11:00',
                      `timeoff` char(10) NOT NULL COMMENT '定时关机时间 23:00',
                      `speed` tinyint(1) NOT NULL COMMENT '当前风速,0,1,2,3',
                      `model` tinyint(1) NOT NULL COMMENT '1 = 自动模式 0 = 手动模式',
                      `arofene` char(10) NOT NULL COMMENT '甲醛',
                      `tvoc` char(10) NOT NULL COMMENT 'tvoc',
                      `temperature` char(10) NOT NULL COMMENT '温度',
                      `cotwo` char(10) NOT NULL DEFAULT '' COMMENT 'CO2',
                      `humidity` char(10) NOT NULL COMMENT '湿度',
                      `ctime` int(11) NOT NULL COMMENT '创建时间',
                      `validity` int(11) NOT NULL COMMENT '过期时间',
                      PRIMARY KEY (`id`),
                      KEY `index_device_code` (`device_code`) USING HASH,
                      KEY `index_upload_time` (`upload_time`) USING HASH,
                      KEY `index_device_ctime` (`ctime`) USING HASH,
                      KEY `index_device_validity` (`validity`) USING HASH
                ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;SET FOREIGN_KEY_CHECKS=1";
        global $dbs;
        $dbs->exec($sql);
    }

    /**
     * 二维数组取交集
     * @author ' Silent <1136359934@qq.com>
     */
    public function listArrayKey($arr, $key)
    {
        $list = array();
        foreach ($arr as $k => $v) {
            if (in_array($v[$key], $list)) {
                unset($arr[$k]);
            } else {
                $list[$k] = $v[$key];
            }
        }
        return $arr;
    }

    /***********************************************告警信息数据开始****************************************************/
    /*
     * 告警数据写入数据库
     * ***/
    public function alarmData(){
        //file_put_contents('alarm.txt', "\r\n告警",FILE_APPEND);
        global $db;
        //获取告警等级设置数据
        $sql = 'select * from yx_alarm_pm25 order by type asc';
        $Sets = $db->query($sql);
        $alarmSets = array();
        foreach($Sets as $Set){
            $alarmSets[$Set['h_id']][] = $Set;
        }
        //获取酒店数据
        $h_id_sql = 'select id as h_id from yx_hotel where is_get=1 and is_sign=1 and status=1';
        $h_ids = $db->query($h_id_sql);

        //获取告警信息表中设备对应的最新告警信息
        //$sql = 'select * from yx_alarm_pm25_info where is_end<>2 group by equipment_sno order by id desc';
        $sql = 'select * from (select * from yx_alarm_pm25_info order by id desc)alarm_pm25_info where is_end<>2 group by equipment_sno';
        $alarmdatas = $db->query($sql);
        $alarms = array();
        foreach($alarmdatas as $alarmdata){
            $alarms[$alarmdata['equipment_sno']] = $alarmdata;
        }

        //当前时间 
        $now_time = time();
        foreach($h_ids as $value){
            $mark = 0;
            $row  = 0;
            //判断表是否存在
            $result = $db->query("select `TABLE_NAME` from `INFORMATION_SCHEMA`.`TABLES` where `TABLE_SCHEMA`='yxkj' and `TABLE_NAME`='yx_upload_device_info_".$value['h_id']."'");
            foreach ($result as $is_tvalue) {
                $row = 1;
                break;
            }
            if(1 != $row){
                continue;
            }
            /*if($value['h_id'] != 76){
                continue;
            }*/
            //获取酒店对应的所有设备
            //$sql = "select * from yx_upload_device_info_".$value['h_id']." group by device_code order by id desc";
            $sql = "select * from (select * from yx_upload_device_info_".$value['h_id']." order by id desc)upload_device_info_".$value['h_id']." group by device_code";
            $datas = $db->query($sql);
            foreach($datas as $data){
                //当前数据设备在线状态
                if($data['online'] == 1 && isset($alarms[$data['device_code']]) && $alarms[$data['device_code']]['is_end'] == 0) {    //有设备信息 未开始
                    $info = $alarms[$data['device_code']];
                    $ok_pm25 = 1;
                    foreach($alarmSets[$value['h_id']] as $alarmSet){
                        $end_time = $data['upload_time'];
                        $len_time = $end_time-$info['start_time'];
                        if($info['start_pm25'] >= $alarmSet['start'] && $data['indoor_pm'] >= $alarmSet['start'] && $len_time >= $alarmSet['time']){//PM2.5满足，持续时长也满足
                            $sql = "update yx_alarm_pm25_info set end_pm25=".$data['indoor_pm'].",end_time=".$end_time.",len_time=".$len_time.",ap_id=".$alarmSet['id'].",up_time=".$now_time.",is_end=1 where id=".$info['id'];
                            $db->query($sql);
                            $mark = 1;
                            $ok_pm25 = 0;
                            break;
                        }elseif($info['start_pm25'] >= $alarmSet['start'] && $data['indoor_pm'] >= $alarmSet['start']){//PM2.5满足，持续时长不满足
                            $sql = "update yx_alarm_pm25_info set end_pm25=".$data['indoor_pm'].",end_time=".$end_time.",len_time=".$len_time.",up_time=".$now_time." where id=".$info['id'];
                            $db->query($sql);
                            $ok_pm25 = 0;
                            break;
                        }elseif($info['start_pm25'] >= $alarmSet['start']){
                            //删除当前数据，新增一条数据，也可以直接修改这条数据
                            $sql = "update yx_alarm_pm25_info set start_pm25=".$data['indoor_pm'].",start_time=".$data['upload_time'].",c_time=".$now_time.",up_time=".$now_time.",ap_id=0 where id=".$info['id'];
                            $db->query($sql);
                            $ok_pm25 = 0;
                            break;
                        }
                    }

                    //设备PM2.5正常
                    if($ok_pm25 == 1){
                        $sql = "update yx_alarm_pm25_info set start_pm25=0,start_time=0,ap_id=0,is_end=4,c_time=".$now_time.",up_time=".$now_time." where id=".$info['id'];
                        $db->query($sql);
                        continue;
                    }
                }elseif($data['online'] == 1 && isset($alarms[$data['device_code']]) && $alarms[$data['device_code']]['is_end'] == 1) {    //有设备信息 正在进行
                    $info = $alarms[$data['device_code']];
                    $ok_pm25 = 1;
                    foreach($alarmSets[$value['h_id']] as $alarmSet){
                        if($data['indoor_pm'] >= $alarmSet['start'] && $alarmSet['id'] == $info['ap_id']){
                            //修改当前的数据的信息
                            $end_time = $data['upload_time'];
                            $len_time = $end_time-$info['start_time'];
                            $sql = "update yx_alarm_pm25_info set end_pm25=".$data['indoor_pm'].",end_time=".$end_time.",len_time=".$len_time.",up_time=".$now_time." where id=".$info['id'];
                            $db->query($sql);
                            $mark = 1;
                            $ok_pm25 = 0;
                            break;
                        }elseif($data['indoor_pm'] >= $alarmSet['start']){
                            //修改当前数据的信息状态为结束，并新插入一条数据
                            $sql = "update yx_alarm_pm25_info set is_end=2 where id=".$info['id'];
                            $db->query($sql);

                            $sql2 = "insert into yx_alarm_pm25_info (equipment_sno,start_pm25,start_time,len_time,is_end,ap_id,c_time,up_time) values('".$data['device_code']."',".$data['indoor_pm'].",".$data['upload_time'].",0,0,0,".$now_time.",".$now_time.")";
                            $db->query($sql2);
                            $mark = 1;
                            $ok_pm25 = 0;
                            break;
                        }
                    }

                    //设备PM2.5正常
                    if($ok_pm25 == 1){
                        //修改当前数据的信息状态为结束，并新插入一条数据
                        $sql = "update yx_alarm_pm25_info set is_end=2 where id=".$info['id'];
                        $db->query($sql);

                        $sql2 = "insert into yx_alarm_pm25_info (equipment_sno,start_pm25,start_time,len_time,is_end,ap_id,c_time,up_time) values('".$data['device_code']."',0,0,0,4,0,".$now_time.",".$now_time.")";
                        $db->query($sql2);
                        continue;
                    }
                }elseif($data['online'] == 1 && isset($alarms[$data['device_code']])){    //关闭状态
                    $info = $alarms[$data['device_code']];
                    $ok_pm25 = 1;
                    foreach($alarmSets[$value['h_id']] as $alarmSet){
                        if($data['indoor_pm'] >= $alarmSet['start']){
                            $sql = "update yx_alarm_pm25_info set start_pm25=".$data['indoor_pm'].",start_time=".$data['upload_time'].",c_time=".$now_time.",up_time=".$now_time.",is_end=0 where id=".$info['id'];
                            $db->query($sql);
                            $ok_pm25 = 0;
                            break;
                        }
                    }

                    //设备PM2.5正常
                    if($ok_pm25 == 1){
                        $sql = "update yx_alarm_pm25_info set start_pm25=0,start_time=0,c_time=".$now_time.",up_time=".$now_time.",is_end=4 where id=".$info['id'];
                        $db->query($sql);
                        continue;
                    }
                }elseif($data['online'] == 1){     //没有设备信息
                    $status = 0;
                    foreach($alarmSets[$value['h_id']] as $alarmSet){
                        if($data['indoor_pm'] >= $alarmSet['start']){
                            $status = 1;
                            break;
                        }
                    }
                    if($status == 1){
                        $sql = "insert into yx_alarm_pm25_info (equipment_sno,start_pm25,start_time,len_time,is_end,ap_id,c_time,up_time) values('".$data['device_code']."',".$data['indoor_pm'].",".$data['upload_time'].",0,0,0,".$now_time.",".$now_time.")";
                        $db->query($sql);
                        continue;
                    }else{
                        $sql = "insert into yx_alarm_pm25_info (equipment_sno,start_pm25,start_time,len_time,is_end,ap_id,c_time,up_time) values('".$data['device_code']."',0,0,0,4,0,".$now_time.",".$now_time.")";
                        $db->query($sql);
                        continue;
                    }
                }
               //当前设备关闭状态
                elseif($data['online'] == 0 && isset($alarms[$data['device_code']]) && $alarms[$data['device_code']]['is_end'] == 0){//有设备信息 未开始
                    $info = $alarms[$data['device_code']];
                    //修改数据为关闭状态  清空其他的字段
                    $sql = "update yx_alarm_pm25_info set start_pm25=0,start_time=0,len_time=0,is_end=3,ap_id=0,c_time=".$now_time.",up_time=".$now_time." where id=".$info['id'];
                    $db->query($sql);
                    continue;
                }elseif($data['online'] == 0 && isset($alarms[$data['device_code']]) && $alarms[$data['device_code']]['is_end'] == 1){//有设备信息 正在进行
                    $info = $alarms[$data['device_code']];
                    //数据库数据修改为结束状态，新插入一条数据
                    $sql = "update yx_alarm_pm25_info set is_end=2 where id=".$info['id'];
                    $db->query($sql);

                    $sql2 = "insert into yx_alarm_pm25_info (equipment_sno,start_pm25,len_time,is_end,ap_id,c_time,up_time) values('".$data['device_code']."',0,0,3,0,".$now_time.",".$now_time.")";
                    $db->query($sql2);
                    continue;
                }elseif($data['online'] == 0 && isset($alarms[$data['device_code']]) && $alarms[$data['device_code']]['is_end'] == 3){
                    continue;
                }elseif($data['online'] == 0){    //没有设备信息
                    $sql = "insert into yx_alarm_pm25_info (equipment_sno,start_pm25,len_time,is_end,ap_id,c_time,up_time) values('".$data['device_code']."',0,0,3,0,".$now_time.",".$now_time.")";
                    $db->query($sql);
                    continue;
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
        $sql = "select id from yx_role where FIND_IN_SET(".$module[0]['id'].",oper_module)";
        $roles = $db->query($sql);
        foreach($roles as $role){
            $role_ids .= ','.$role['id'];
        }

        if(!empty($role_ids)){
            $role_ids = substr($role_ids, 1);
            $sql = "select id from yx_user where status=1 and type=2 and hotel_id=".$h_id." and role_id in(".$role_ids.")";
            $users = $db->query($sql);
            foreach($users as $user){
                $uids .= ','.$user['id'];
            }
            $uids = strlen($uids)>0 ? substr($uids, 1) : '';
            if($uids){
                $this->has_oper($content='您有新的告警信息需查看',$uids,'Alarm/index','查看提醒','查看消息');
            }
        }
    }

    /**
     * 提示操作消息
     */
    public function has_oper($content='您有一条新工单待处理',$uid=null,$oper_url=null,$title='操作提醒',$type='操作消息'){
        /*if(empty($uid)){
            $user = M('User')->field('id')->where(array('status'=>1))->select();
            $mdata['get_ids'] = implode(',', i_array_column($user,'id'));
        }else{*/
            //$mdata['get_ids'] = $uid;
        /*}*/
        /*$mdata['get_ids'] = $uid;
        if(empty($uid)){
            return true;
        }
        $mdata['title'] = $title;
        $mdata['type'] = $type;
        $mdata['content'] = $content;
        $mdata['ctime'] = time();
        $mdata['time'] = time();
        if($oper_url){
          $mdata['oper_url'] = $oper_url;  
        }*/
        global $db;
        //警告消息上限条数
        $max_message_num = 99;

        $oper_url = $oper_url ? $oper_url : '';
        $sql2 = "insert into yx_message (get_ids,title,type,content,ctime,time,oper_url) values('".$uid."','".$title."','".$type."','".$content."',".time().",".time().",'".$oper_url."')";
        $res = $db->query($sql2);
        //$res = M('Message')->add($mdata);
        if($uid){
            $oper_url = $oper_url ? $oper_url : 'message';
            $uids=explode(',',$uid);
            for($i=0;$i<count($uids);$i++){
                $this->newMessage($uids[$i],$oper_url);

                //查看警告消息是否大于99条
                $alarm_message_num = 0;
                $alarm_message_num_sql = 'select count(*) as count_num from yx_message where oper_url="Alarm/index" and status=1 and FIND_IN_SET('.$uids[$i].',get_ids)';
                $alarm_message_num_resources = $db->query($alarm_message_num_sql);
                foreach ($alarm_message_num_resources as $amnval) {
                    $alarm_message_num = $amnval['count_num']; //总数
                    //去除多余条数
                    if($alarm_message_num > $max_message_num){
                        $more_num = $alarm_message_num-$max_message_num;
                        $more_message_num_sql = 'select id from yx_message where oper_url="Alarm/index" and status=1 and FIND_IN_SET('.$uids[$i].',get_ids) order by id limit '.$max_message_num.','.$more_num;
                        $more_message_num_resources = $db->query($more_message_num_sql);
                        $kill_more_message_str = '';
                        foreach ($more_message_num_resources as $value) {
                             $kill_more_message_str .= ','.$value['id'];
                        }
                        $kill_more_message_sql = 'update yx_message set status=-1 where FIND_IN_SET(id,"'.substr($kill_more_message_str, 1).'")';
                        $db->query($kill_more_message_sql);

                        for ($amn=0; $amn < $more_num; $amn++) {
                            $this->newMessage($uids[$i],'look');
                        }
                    }
                }
            }
        }
    }

    /**
     * websocket 推送消息
     */
    public function newMessage($to_uid = '', $identity = 'message'){
        // 推送的url地址，使用自己的服务器地址
        $push_api_url = "http://47.95.253.212:2121/";
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
    /***********************************************告警信息数据结束****************************************************/
        
}

$task = new Worker();
$task->count = 1;
$task->onWorkerStart = function ($task) {
    // Mysql链接
    global $db;
    $db = new \Workerman\MySQL\Connection('rm-2ze6g66l7pzx2kvej.mysql.rds.aliyuncs.com', '3306', 'yxkj', 'yxKJ2017$0904', 'yxkj');
    // Pdo
    global $dbs;
    $dbs = new PDO('mysql:host=rm-2ze6g66l7pzx2kvej.mysql.rds.aliyuncs.com;dbname=yxkj', 'yxkj', 'yxKJ2017$0904');
    // Redis连接
    global $redisChat;
    $redisChat = new \Redis();
    $redisChat->connect('127.0.0.1');
    if ($task->id === 0) {
        $beanstalkd = new updateData();
        $beanstalkd->getUpdate();
        $beanstalkd->elkData();
        //$beanstalkd->getalarmData();
    }
};
// 运行worker
Worker::runAll();

