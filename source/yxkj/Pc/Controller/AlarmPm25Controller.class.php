<?php
/**
 * Created by PhpStorm.
 * User: rzhd
 * Date: 2018/9/4
 * Time: 13:37
 */

namespace Pc\Controller;


class AlarmPm25Controller extends BaseController{
    protected function _set_wheres(&$wheres){
        unset($wheres['status']);
        //非超级管理员
        $hotel_id = session('USERINFO.hotel_id');
        $wheres['h_id'] = $hotel_id ? $hotel_id : 0;
    }

    /**
     * 对数据进行操作
     */
    public function operation(){
        if ($this->model->create() !== false) {
            /*$data = I("post.");
            if($data['start'] >= $data['end']){
                ajax_return(0, '告警PM2.5开始值不能大于等于结束值');
                return;
            }*/
            $re_status = $this->model->operation();
            $this->admin_ajax_return($re_status);
        } else {
            ajax_return(0, $this->model->getError());
        }
    }

    /**
     * 循环添加酒店告警设置
     * @author baddl
     * @date 2018-09-06 17:06
     */
    public function all_hotel_alarm(){
        ob_clean();
        $hotel_list = M('Hotel')->field('id')->select();
        $alarm_list = M('AlarmPm25')->where(array('h_id'=>0))->select();
        foreach ($hotel_list as $hkey => $hvalue) {
            foreach ($alarm_list as $akey => $avalue) {
                unset($avalue['id']);
                $avalue['h_id'] = $hvalue['id'];
                $alarm_data[] = $avalue;
            }
            M('AlarmPm25')->addAll($alarm_data);
            unset($alarm_data);
        }
        echo '酒店告警设置完成^_^';
    }

    /**
     * 测试取消消息
     */
    public function no_message(){
        echo $this->msectime();
        not_oper_alarm(35);
        echo "<br/>";
        echo $this->msectime();
    }

    //返回当前的毫秒时间戳
    protected function msectime() {
       list($msec, $sec) = explode(' ', microtime());
       $msectime =  (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
       return $msectime;
    }

}