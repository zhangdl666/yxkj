<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/11/2
 * Time: 10:25
 */
namespace Pc\Controller;
class DeviceController extends BaseController{
    public function _initialize(){
        $this->model = 'Device';
        parent::_initialize();
    }
    public function index(){
        $mac=I('post.mac');
        $where=array();
        if(!empty($mac)){
            $where['mac']= $mac;
            $this->assign('mac',$mac);
        }
        $re_datas = $this->model->getResultList($where);
        $this->assign($re_datas);
        $this->display();
    }
    public function fileExcel(){
        $result=array('code'=>1,'message'=>'设备上传失败');
        $filename = $_FILES['file']['tmp_name'];
        $handle = fopen($filename, 'r');
        $result = $this->input_csv($handle); //解析csv
        $len_result = count($result);
        if($len_result==0) {
            $res['message'] = '上传文件内容为空';
            $this->ajaxReturn($result);
        }
        $data=array();
        for($i = 1; $i < $len_result; $i++) //循环获取各字段值
        {
            if(strtolower($result[$i][4]) == 'ok'){
                $mac = iconv('gb2312', 'utf-8', $result[$i][0]); //中文转码
                if( is_numeric($mac) && strlen($mac) == 11){
                    $mac='0'.$mac;
                }
                $qr = iconv('gb2312', 'utf-8', $result[$i][2]);
                $device_id = iconv('gb2312', 'utf-8', $result[$i][3]);
                $data[] = array(
                    'mac'=>$mac,
                    'qr'=>$qr,
                    'device_id'=>$device_id,
                );
            }
        }
        fclose($handle); //关闭指针
        $res=$this->model->addAllMac($data);
        if($res){
            $result['code'] = 0;
            $result['message'] = '设备上传保存成功';
        }else{
            $result['message'] = '上传成功，保存文件失败';
        }
        $this->ajaxReturn($result);
    }
    public function input_csv($handle){
        $out = array ();
        $n = 0;
        while ($data = fgetcsv($handle, 10000)) {
            $num = count($data);
            for ($i = 0; $i < $num; $i++) {
                $out[$n][$i] = $data[$i];
            }
            $n++;
        }
        return $out;
    }
}