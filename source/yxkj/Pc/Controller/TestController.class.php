<?php
/**
 * Author: ' Silent
 * Time: 2017/9/15 9:22
 */

namespace Pc\Controller;


use Pc\Model\DeviceRunModel;
use Pc\Model\HotelModel;
use Pc\Model\RunInfoDataModel;
use Pc\Model\RunInfoModel;
use Pc\Model\SalesModel;
use Pc\Model\UserObjModel;
use Pc\Server\PurifyData;
use Pc\Server\DateType;
use Pc\Server\Timing;
use Workerman\Worker;
use PHPSocketIO\SocketIO;
use PHPExcel_IOFactory;
use Workerman\MySQL;

class TestController extends BaseController
{
    private $user_id;

    public function _initialize()
    {
        $this->user_id = $this->identity()->getUserId();
    }


    // 最后数据获取测试
    public function test1()
    {
        dump(RunInfoModel::getNewDevicesInfo('D0BAE41B4C81'));
    }

    // 最近一小时更新测试
    public function test2()
    {
        dump(RunInfoModel::getDevicesDay('D0BAE41B4C81', '0'));
    }

    // 以时间端进行获取
    public function test3()
    {
        dump(RunInfoModel::getDevicesalldata('D0BAE41B4C81', '1505145600', '1505231999'));
    }

    // 获取设备信息
    public function test4()
    {
        dump(RunInfoModel::getDevicesDay('D0BAE41B4C81'));
    }

    // 获取设备信息开机与关机状态
    public function test5()
    {
        dump(RunInfoModel::getDevicesTime('D0BAE41B4C81', '2017-10-1'));
    }

    public function test6()
    {
        dump(HotelModel::claimHotelDate(195));
    }

    public function create_upkeep($sno)
    {
        $re_data = Timing::create_upkeep($sno);
        if ($re_data) {
            echo '保养工单号：' . $re_data;
        } else {
            echo '保养工单生成失败';
        }
    }

    public function ceshi1()
    {
        file_put_contents('./ceshi1.txt', "ceshi1\r\n", FILE_APPEND);
    }

    public function ceshi2()
    {
        file_put_contents('./ceshi2.txt', "ceshi2\r\n", FILE_APPEND);
    }

    public function test7()
    {
        vendor('PHPSocket.vendor.autoload');
        // 创建socket.io服务端，监听2021端口
        $io = new SocketIO(3120);
        // 当有客户端连接时打印一行文字
        $io->on('connection', function ($socket) use ($io) {
            echo "new connection coming\n";
        });
        if (!defined('GLOBAL_START')) {
            Worker::runAll();
        }
    }

    public function test8()
    {
        $data = M(HotelModel::TABLENAME_HOTEL)->alias('l')
            ->join('left join ' . C('DB_PREFIX') . SalesModel::TABLENAME_ORDER . ' as s on s.h_id = l.id')
            ->where(array('s.type' => array('eq', 1), 's.status' => array('eq', 4)))
            ->field('l.id,l.name')->select();
        $datas = SalesModel::listArrayKey($data, 'name');
        dump($datas);
    }

    public function iget_ip()
    {
        echo get_server_ip();
    }

    public function spo()
    {
        dump(M('hc_room_equipment')->sum('r_num'));
    }

    /* 生成5000设备 */
    public function create_device()
    {
        for ($i = 0; $i < 5000; $i++) {
            $data1['mac'] = 'D0BAE42B' . str_pad($i, 4, "0", STR_PAD_LEFT);
            $data1['device_id'] = 'gh_73d4995abdb8_bb7fb9666f1f' . str_pad($i, 4, "0", STR_PAD_LEFT);
            $data1['qr'] = 'http://we.qq.com/d/' . $data1['mac'];
            $data[] = $data1;
            if (count($data) == 100) {
                $resut = M('Device')->addAll($data);
                if ($resut) {
                    echo '生成成功！';
                    unset($data);
                    continue;
                } else {
                    echo '生成失败！';
                    unset($data);
                    break;
                }
            }
        }
        if ($data) {
            $resut = M('Device')->addAll($data);
            if ($resut) {
                echo '生成成功！';
            } else {
                echo '生成失败！';
            }
        }
    }

    /* 设备运行 */
    public function device_run()
    {
        $arr = array('东城区', '西城区', '南城区', '北城区', '朝阳区');
        $device_list = M('Device')->field('mac,device_id')->select();
        foreach ($device_list as $key => $value) {
            $data1['deviceid'] = $value['device_id'];
            $data1['mac'] = $value['mac'];
            $data1['model'] = rand(1, 2);
            $data1['prov'] = '';
            $data1['city'] = '';
            $data1['area'] = '';
            $data1['provname'] = '北京';
            $data1['cityname'] = '北京市';
            $data1['areaname'] = $arr[rand(0, 4)];
            $data1['online'] = rand(0, 1);
            $data1['power'] = rand(0, 1);
            $data1['speed'] = rand(1, 3);
            $data1['timed'] = rand(0, 1);
            if ($data1['timed'] > 0) {
                $stime = rand(0, 22);
                $stime1 = rand(0, 59);
                $etime = rand($stime, 23);
                $etime1 = rand(0, 59);
                $timeopen = str_pad($stime, 2, "0", STR_PAD_LEFT) . ':' . str_pad($stime1, 2, "0", STR_PAD_LEFT);
                $timeoff = str_pad($etime, 2, "0", STR_PAD_LEFT) . ':' . str_pad($etime1, 2, "0", STR_PAD_LEFT);
                $timeline = ($etime * 3600 + $etime1 * 60) - ($stime * 3600 + $stime1 * 60);

            } else {
                $timeopen = 0;
                $timeoff = 0;
                $timeline = 0;
            }
            $data1['timeopen'] = $timeopen ? $timeopen : 0;
            $data1['timeoff'] = $timeoff ? $timeoff : 0;
            $data1['timeline'] = $timeline ? $timeline : 0;
            $data1['powertime'] = date('Y-m-d H:i:s', strtotime('+' . rand(1, 5) . ' hour'));
            $data1['offtime'] = time();
            $data[] = $data1;
            if (count($data) == 50) {
                $result = M('DeviceRunInfo1')->addAll($data);
                $result1 = M('DeviceRunInfo')->addAll($data);
                if ($result && $result1) {
                    echo '成功<br/>';
                    unset($data);
                    continue;
                } else {
                    echo '失败<br/>';
                    unset($data);
                    break;
                }
            }
        }

        if ($data) {
            $result = M('DeviceRunInfo1')->addAll($data);
            $result1 = M('DeviceRunInfo')->addAll($data);
            if ($result && $result1) {
                echo '成功<br/>';
                unset($data);
            } else {
                echo '失败<br/>';
                unset($data);
            }
        }
    }

    /* 设备运行 */
    public function device_run1()
    {
        $arr = array('东城区', '西城区', '南城区', '北城区', '朝阳区');
        $device_list = M('Device')->field('mac,device_id')->select();
        foreach ($device_list as $key => $value) {
            $data1['deviceid'] = $value['device_id'];
            $data1['mac'] = $value['mac'];
            $data1['model'] = rand(1, 2);
            $data1['prov'] = '';
            $data1['city'] = '';
            $data1['area'] = '';
            $data1['provname'] = '北京';
            $data1['cityname'] = '北京市';
            $data1['areaname'] = $arr[rand(0, 4)];
            $data1['online'] = rand(0, 1);
            $data1['power'] = rand(0, 1);
            $data1['speed'] = rand(1, 3);
            $data1['timed'] = rand(0, 1);
            if ($data1['timed'] > 0) {
                $stime = rand(0, 22);
                $stime1 = rand(0, 59);
                $etime = rand($stime, 23);
                $etime1 = rand(0, 59);
                $timeopen = str_pad($stime, 2, "0", STR_PAD_LEFT) . ':' . str_pad($stime1, 2, "0", STR_PAD_LEFT);
                $timeoff = str_pad($etime, 2, "0", STR_PAD_LEFT) . ':' . str_pad($etime1, 2, "0", STR_PAD_LEFT);
                $timeline = ($etime * 3600 + $etime1 * 60) - ($stime * 3600 + $stime1 * 60);

            } else {
                $timeopen = 0;
                $timeoff = 0;
                $timeline = 0;
            }
            $data1['timeopen'] = $timeopen ? $timeopen : 0;
            $data1['timeoff'] = $timeoff ? $timeoff : 0;
            $data1['timeline'] = $timeline ? $timeline : 0;
            $data1['powertime'] = date('Y-m-d H:i:s', strtotime('+' . rand(1, 5) . ' hour'));
            $data1['offtime'] = time();
            $data[] = $data1;
            if (count($data) == 50) {
                $result = M('DeviceRunInfo1')->addAll($data);
                if ($result) {
                    echo '成功<br/>';
                    unset($data);
                    continue;
                } else {
                    echo '失败<br/>';
                    unset($data);
                    break;
                }
            }
        }

        if ($data) {
            $result = M('DeviceRunInfo1')->addAll($data);
            if ($result) {
                echo '成功<br/>';
                unset($data);
            } else {
                echo '失败<br/>';
                unset($data);
            }
        }
    }

    /* 设备监控情况 */
    public function device_run_history()
    {
        $device_list = M('Device')->field('mac,device_id')->select();
        foreach ($device_list as $key => $value) {
            $data1['deviceid'] = $value['device_id'];
            $data1['mac'] = $value['mac'];
            $data1['date'] = time();
            $data1['pm25'] = rand(1, 999);
            $data1['apm25'] = rand(1, 999);
            $data1['temp'] = rand(-30, 30);
            $data1['humi'] = rand(-30, 30);
            $data1['fulldate'] = date('Y-m-d/H', time());
            $data[] = $data1;
            if (count($data) == 100) {
                $result = M('DeviceRunHistory')->addAll($data);
                if ($result) {
                    echo '运行历史成功<br/>';
                    unset($data);
                    continue;
                } else {
                    echo '运行历史失败<br/>';
                    unset($data);
                    break;
                }
            }
        }

        if ($data) {
            $result = M('DeviceRunHistory')->addAll($data);
            if ($result) {
                echo '运行历史成功<br/>';
                unset($data);
            } else {
                echo '运行历史失败<br/>';
                unset($data);
            }
        }
    }

    /**
     * 设备开关机
     */
    public function device_offopen()
    {
        $num = rand(1, 4999);
        for ($i = 0; $i < $num; $i++) {
            $str_arr[] = $num;
        }
        $str = implode(',', $str_arr);

        $device_list = M('Device')->field('mac,device_id')->where(array('id' => array('in', $str)))->select();
        foreach ($device_list as $value) {
            $data1['deviceid'] = $value['device_id'];
            $data1['mac'] = $value['mac'];
            $data1['time'] = time();
            $data1['type'] = rand(0, 1);
            $data[] = $data1;
            if (count($data) == 100) {
                $result = M('DeviceOffon')->addAll($data);
                if ($result) {
                    echo '设备开关机成功<br/>';
                    unset($data);
                    continue;
                } else {
                    echo '设备开关机失败<br/>';
                    unset($data);
                    break;
                }
            }
        }

        if ($data) {
            $result = M('DeviceOffon')->addAll($data);
            if ($result) {
                echo '设备开关机成功<br/>';
                unset($data);
            } else {
                echo '设备开关机失败<br/>';
                unset($data);
            }
        }
    }


    /**
     * 根据安装工单生成安装详细单
     */
    public function create_oo()
    {
        //$id = 20017;
        $device_list = M('Device')->field('mac')->select();
        $hcre_list = M('HcRoomEquipment')->where(array('id' => array('egt', 20017)))->select();
        $oo_list = M('OperOrder')->field('id,h_id,hc_id')->where(array('id' => array('egt', 20000)))->select();
        $es_num = 0;
        foreach ($hcre_list as $value) {
            foreach ($oo_list as $val) {
                if ($val['h_id'] == $value['h_id'] && $val['hc_id'] == $value['hc_id']) {
                    for ($i = 0; $i < ($value['r_num'] * 1 - 1); $i++) {
                        $data1['oo_id'] = $val['id'];
                        $data1['equipment_sno'] = $device_list[$es_num]['mac'];
                        $data1['rt_id'] = $value['rt_id'];
                        $data1['floor'] = rand(1, 20);
                        $data1['room_sno'] = rand(1000, 9999);
                        $data1['is_window'] = rand(0, 1);
                        $data1['orientation'] = rand(1, 4);
                        $data1['place'] = rand(1, 3);
                        $data1['e_id'] = rand(5, 6);
                        $data1['ctime'] = time();
                        $data[] = $data1;
                        ++$es_num;
                        if (count($data) == 20) {
                            M('OperInfo')->addAll($data);
                            unset($data);
                        }
                    }
                    if ($data) {
                        M('OperInfo')->addAll($data);
                        unset($data);
                    }
                    break;
                }
            }
        }
        echo '成功！';
    }

    public function import_excel($file = '/Uploads/Import/2017-12-13/5a30ddd2f3ddd.xlsx')
    {
        // 判断文件是什么格式
        $file = dirname($_SERVER['SCRIPT_FILENAME']) . $file;
        $type = pathinfo($file);
        $type = strtolower($type["extension"]);
        $type = $type === 'csv' ? $type : 'Excel5';
        ini_set('max_execution_time', '0');
        Vendor('PHPExcel.PHPExcel');
        // 判断使用哪种格式
        $objReader = PHPExcel_IOFactory::identify($file);
        $objPHPExcel = $objReader->load($file);
        $sheet = $objPHPExcel->getSheet(0);
        // 取得总行数
        $highestRow = $sheet->getHighestRow();
        // 取得总列数
        $highestColumn = $sheet->getHighestColumn();
        //循环读取excel文件,读取一条,插入一条
        $data = array();
        //从第一行开始读取数据
        for ($j = 1; $j <= $highestRow; $j++) {
            //从A列读取数据
            for ($k = 'A'; $k <= $highestColumn; $k++) {
                // 读取单元格
                $data[$j][] = $objPHPExcel->getActiveSheet()->getCell("$k$j")->getValue();
            }
        }
        return $data;
    }

    public function times()
    {
        $e1_name = M(SalesModel::TABLENAME_OPER)->alias('l')
            ->join('left join ' . C('DB_PREFIX') . SalesModel::TABLENAME_EMENT . ' as h on h.rt_id = l.rt_id')
            ->join('left join ' . C('DB_PREFIX') . SalesModel::TABLENAME_TYPE . ' as s on s.id = h.e1_id')
            ->field('s.name')->find();
        dump($e1_name);
    }

    /**
     * 获取登录用户对象
     * @return type
     */
    public function identity()
    {
        if (!$this->userObj) {
            $this->userObj = new UserObjModel();
        }
        return $this->userObj;
    }

    public function test12()
    {
        $data = M('OperInfo')->where(array('status' => 1))->field('equipment_sno')->select();
        dump(count($data));
        $str = '';
        foreach ($data as $key => $val) {
            if ($key >= 1) {
                $str .= '|' . $val['equipment_sno'];
            } else {
                $str .= $val['equipment_sno'];
            }
        }
        $datas = RunInfoModel::getMultipleInfo($str);
        dump($datas);
        exit;
        $listArray = array();
        foreach ($datas['设备数据'] as $key => $val) {
            $devtime = strtotime(strtr($val['上传时间'], array(".0" => '')));
            $time = date('Y-m-d', $devtime);
            if ($time == date('Y-m-d', time())) {
                if ($val['online'] == 1 && $val['power'] == 1) {
                    $listArray[$key]['device_code'] = $val['设备地址'];
                    $listArray[$key]['upload_times'] = strtr($val['上传时间'], array(".0" => ''));
                    $listArray[$key]['upload_time'] = strtotime($time);
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
                } else {
                    // 判断数据库是否存在,存在则不添加
                    $datas = M('UploadDeviceInfo')->where(array('device_code' => $val['设备地址'], 'upload_time' => strtotime($time)))->find();
                    if (!$datas) {
                        $listArray[$key]['device_code'] = $val['设备地址'];
                        $listArray[$key]['upload_times'] = strtr($val['上传时间'], array(".0" => ''));
                        $listArray[$key]['upload_time'] = strtotime($time);
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
                    }
                }
            } else {
                // 判断数据库是否存在,存在则不添加
                $datas = M('UploadDeviceInfo')->where(array('device_code' => $val['设备地址'], 'upload_time' => strtotime($time)))->find();
                if (!$datas) {
                    $listArray[$key]['device_code'] = $val['设备地址'];
                    $listArray[$key]['upload_times'] = strtr($val['上传时间'], array(".0" => ''));
                    $listArray[$key]['upload_time'] = strtotime($time);
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
                }
            }
        }
        $result = M('UploadDeviceInfo')->addAll(array_values($listArray));
        dump($result);
    }

    public function test13()
    {
        $data = M('OperInfo')->where(array('status' => 1))->field('equipment_sno')->select();
        dump(ceil(count($data) / 50));
    }

    public function test14()
    {
        $sql = "ALTER TABLE " . C('DB_PREFIX') . "content ADD " . $fieldName . " VARCHAR(100) CHARACTER SET utf8;";
        $re = M()->execute($sql);
    }

    public function test15()
    {
        $sql = "DELETE FROM `yx_upload_device_info_1` WHERE `validity` < '".time()."'";
        dump(M()->execute($sql));
    }

    public function test16(){
        $data = M('OperInfo')->where(array('status' => 1))->field('equipment_sno')->select();
        dump(DeviceRunModel::DeviceRemInfo($data));
    }
}

