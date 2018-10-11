<?php
/**
 * function.php
 * 项目公共方法文件
 * @author baddl
 * @date   2017-09-04 09:51
 */

/**
 * 文件上传(图片上传)
 * @return array|bool
 */
function upload($dir)
{
    //接收到上传插件上传上来的文件保存到指定的位置
    if (!is_dir('./Uploads/' . $dir . '/')) {  //如果Uploads下的目录不存在就创建
        mkdir('./Uploads/' . $dir . '/', 0777, true);
    }
    //>>2. 接收上传上来的文件保存到上面指定的目录中
    $config = array(
        'mimes' => array(), //允许上传的文件MiMe类型
        'maxSize' => 0, //上传的文件大小限制 (0-不做限制)
        'exts' => array('jpg', 'png', 'jpeg'), //允许上传的文件后缀
        'autoSub' => true, //自动子目录保存文件
        'subName' => array('date', 'Y-m-d'), //子目录创建方式，[0]-函数名，[1]-参数，多个参数使用数组
        'rootPath' => './Uploads/', //保存根路径
        'savePath' => $dir . '/', //保存路径
        'saveName' => array('uniqid', ''), //上传文件命名规则，[0]-函数名，[1]-参数，多个参数使用数组
        'saveExt' => '', //文件保存后缀，空则使用原后缀
        'replace' => false, //存在同名是否覆盖
        'hash' => true, //是否生成hash编码
        'callback' => false, //检测文件是否存在回调，如果存在返回文件信息数组
        'driver' => '', // 文件上传驱动
        'driverConfig' => array(), // 上传驱动配置
    );
    $uploader = new \Think\Upload($config);
    //>>3.将上传后的路径放到$_POST
    $info = $uploader->upload();
    if (!$info) {
        // 上传错误提示错误信息
        $result_arr = array('status' => 0, 'msg' => $uploader->getError());
    } else {
        /* 检测图片是否被旋转 */
        /*foreach ($info as &$value) {
            $ext = explode('/', $value['type']);
            if (in_array($ext[1], array('jpg', 'jpeg'))) {
                $filename = './Uploads/' . $value['savepath'] . $value['savename'];

                $exif = exif_read_data($filename);  //获取exif信息
                if (!empty($exif['Orientation'])) {
                    $source = imagecreatefromjpeg($filename);
                    switch ($exif['Orientation']) {
                        // 正90 度
                        case 8:
                            $file_source = imagerotate($source, 90, 0);
                            break;
                        // 180 度
                        case 3:
                            $file_source = imagerotate($source, 180, 0);
                            break;
                        // -90 度
                        case 6:
                            $file_source = imagerotate($source, -90, 0);
                            break;
                    }
                    // 保存图像为
                    imagejpeg($file_source, $filename);

                    // 释放内存
                    imagedestroy($file_source);
                }
            }
        }*/
        $result_arr = array('status' => 1, 'msg' => '上传成功', 'data' => '/Uploads/' . $info['file']['savepath'] . $info['file']['savename']);
//        $result_arr = array('status' => 1, 'msg' => '上传成功', 'data' => '/Uploads/' . $info['Filedata']['savepath'] . $info['Filedata']['savename']);
    }
    return $result_arr;

}


/**
 * AJAX返回数据
 * @param  int $status 状态
 * @param  string $info 提示信息
 * @param  string $data 数据
 * @return array
 */
function ajax_return($status, $info, $data = '')
{
    $returnInfo = array('status' => $status, 'info' => $info, 'data' => $data);
    echo json_encode($returnInfo);
    return;
}

/**
 *  返回输入数组中某个单一列的值
 * @param  array $arr
 * @param  string $columnkey
 * @param  int $indexkey 数组的索引/键的列
 * @return array
 */
function i_array_column($arr, $columnKey, $indexKey = null)
{
    if (!function_exists('array_column')) {
        $columnKeyIsNumber = (is_numeric($columnKey)) ? true : false;
        $indexKeyIsNull = (is_null($indexKey)) ? true : false;
        $indexKeyIsNumber = (is_numeric($indexKey)) ? true : false;
        $result = array();
        foreach ((array)$arr as $key => $row) {
            if ($columnKeyIsNumber) {
                $tmp = array_slice($row, $columnKey, 1);
                $tmp = (is_array($tmp) && !empty($tmp)) ? current($tmp) : null;
            } else {
                $tmp = isset($row[$columnKey]) ? $row[$columnKey] : null;
            }
            if (!$indexKeyIsNull) {
                if ($indexKeyIsNumber) {
                    $key = array_slice($row, $indexKey, 1);
                    $key = (is_array($key) && !empty($key)) ? current($key) : null;
                    $key = is_null($key) ? 0 : $key;
                } else {
                    $key = isset($row[$indexKey]) ? $row[$indexKey] : 0;
                }
            }
            $result[$key] = $tmp;
        }
        return $result;
    } else {
        return array_column($arr, $columnKey, $indexKey);
    }
}

/**
 * 判断是否为 ajax 请求
 */
function get_request_type()
{
    // php 判断是否为 ajax 请求
    if (isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) == "xmlhttprequest") {
        // ajax 请求的处理方式 
        return true;
    } else {
        // 正常请求的处理方式 
        return false;
    }
}

/**
 * 生成编号
 */
function get_reimbursement_sn()
{
    $numbers = rand(1, 99999999);
    $result = date('YmdHis') . substr($numbers, 0, 6);
    return $result;
}

/**
 * 二维数组根据字段进行排序
 * @params array $array 需要排序的数组
 * @params string $field 排序的字段
 * @params string $sort 排序顺序标志 SORT_DESC 降序；SORT_ASC 升序
 */
function arraySequence($array, $field, $sort = 'SORT_DESC')
{
    $arrSort = array();
    foreach ($array as $uniqid => $row) {
        foreach ($row as $key => $value) {
            $arrSort[$key][$uniqid] = $value;
        }
    }
    array_multisort($arrSort[$field], constant($sort), $array);
    return $array;
}

/**
 * 根据设配返回的pm值判断空气的优良
 * 1优 2良 3差
 * https://wenku.baidu.com/view/3792823752ea551810a687c3.html
 */
function testingPm($pm)
{
    switch ($pm) {
        case ($pm >= '0' && $pm <= '34'):
            $n = '1';
            break;
        case ($pm >= '35' && $pm <= '74'):
            $n = '2';
            break;
        default:
            $n = '3';
    }
    return $n;
}

/**
 * 生成字符串
 */
function getRandChar($length)
{
    $str = '';
    $strPol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
    $max = strlen($strPol) - 1;

    for ($i = 0; $i <= $length; $i++) {
        $str .= $strPol[rand(0, $max)];//rand($min,$max)生成介于min和max两个数之间的一个随机整数
    }

    return $str;
}

/**
 *取数组中的一个字段做key,一个字段做value
 *且key应该无重复
 */
function getKeyValueArray($arr, $k_field, $v_field)
{
    $res_arr = array();
    if ($v_field != '') {
        foreach ($arr as $value) {
            $res_arr[$value[$k_field]] = $value[$v_field];
        }
    } else {
        foreach ($arr as $value) {
            $res_arr[$value[$k_field]] = $value;
        }
    }
    return $res_arr;
}

/**
 * 截取中文字符串
 * @param $str
 * @param int $start
 * @param $length
 * @param string $charset
 * @param bool $suffix
 * @return string
 */
function msubstr($str, $start = 0, $length, $charset = "utf-8", $suffix = true)
{
    if (function_exists("mb_substr")) {
        $slice = mb_substr($str, $start, $length, $charset);
    } elseif (function_exists('iconv_substr')) {
        $slice = iconv_substr($str, $start, $length, $charset);
    } else {
        $re['utf-8'] = "/[x01-x7f]|[xc2-xdf][x80-xbf]|[xe0-xef][x80-xbf]{2}|[xf0-xff][x80-xbf]{3}/";
        $re['gb2312'] = "/[x01-x7f]|[xb0-xf7][xa0-xfe]/";
        $re['gbk'] = "/[x01-x7f]|[x81-xfe][x40-xfe]/";
        $re['big5'] = "/[x01-x7f]|[x81-xfe]([x40-x7e]|xa1-xfe])/";
        preg_match_all($re[$charset], $str, $match);
        $slice = join("", array_slice($match[0], $start, $length));
    }
    $fix = '';
    if (strlen($slice) < strlen($str)) {
        $fix = '...';
    }
    return $suffix ? $slice . $fix : $slice;
}

/**
 * 提示操作消息
 */
function has_oper($content='您有一条新工单待处理',$uid=null,$oper_url=null,$title='操作提醒',$type='操作消息'){
    /*if(empty($uid)){
        $user = M('User')->field('id')->where(array('status'=>1))->select();
        $mdata['get_ids'] = implode(',', i_array_column($user,'id'));
    }else{*/
        $mdata['get_ids'] = $uid;
    /*}*/
    if(empty($uid)){
        return true;
    }
    $mdata['title'] = $title;
    $mdata['type'] = $type;
    $mdata['content'] = $content;
    $mdata['title'] = $title;
    $mdata['ctime'] = time();
    $mdata['time'] = time();
    if($oper_url){
      $mdata['oper_url'] = $oper_url;  
    }
    //file_put_contents('./oper_message.txt', var_export($mdata,true),FILE_APPEND);
    $res = M('Message')->add($mdata);
    if($res && $uid){
        $oper_url = $oper_url ? $oper_url : 'message';
        $uids=explode(',',$uid);
        for ($i=0;$i<count($uids);$i++){
            newMessage($uids[$i],$oper_url);
        }
    }
}

/**
 * 操作消息消失
 * @param int  $sno  工单编号
 * @param int  $user_id 当前操作用户ID
 */
function not_oper($sno,$user_id){
    $mwhere['_string'] = 'FIND_IN_SET('.$user_id.', get_ids)';
    $mwhere['content'] = array('like','%'.$sno.'%');
    $mwhere['status']  = 1;
    $m_info = M('Message')->field('id,get_ids')->where($mwhere)->find();
    if(empty($m_info)){
        return;
    }

    M('Message')->save(array('id'=>$m_info['id'],'status'=>-1));
    $get_id_arr = explode(',', $m_info['get_ids']);
    for ($mi=0; $mi < count($get_id_arr); $mi++) { 
        /*$addData=array(
            'm_id'=>$m_info['id'],
            'get_id'=>$get_id_arr[$mi],
            'ctime'=>time()
        );
        $res=M('MessageLook')->add($addData);*/
        /*if($res){*/
            newMessage($get_id_arr[$mi],'look');
        /*}*/
    }    
}

/**
 * 获取存放项目的服务器IP
 */
function get_server_ip(){
    return '47.93.16.203';
    //return $_SERVER['SERVER_ADDR'];
}

function newMessage($to_uid = '', $identity = 'message'){
    // 推送的url地址，使用自己的服务器地址
    $push_api_url = "http://".get_server_ip().":2121/";   //47.95.253.212
    //$push_api_url = "http://47.95.253.212:2121/";
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
function filter_money($money,$accuracy=2)
{
    $str_ret = 0;
    if (empty($money) === false) {
        $str_ret = sprintf("%.".$accuracy."f", substr(sprintf("%.".($accuracy+1)."f", floatval($money)), 0, -1));
    }

    return floatval($str_ret);
}
function checkMobile($tel){
    $isMob="/^1[3-5,8]{1}[0-9]{9}$/";
    $isTel="/^([0-9]{3,4}-)?[0-9]{7,8}$/";
    if(!preg_match($isMob,$tel) && !preg_match($isTel,$tel)){
        return false;
    }else{
        return true;
    }
}


/**
 * 操作消息消失
 * @param int  $sno  工单编号
 * @param int  $user_id 当前操作用户ID
 */
function not_oper_alarm($user_id){
    $mwhere['_string'] = 'FIND_IN_SET('.$user_id.', get_ids)';
    $mwhere['oper_url']  = 'Alarm/index';
    $mwhere['status']  = 1;
    $m_info = M('Message')->field('id,get_ids')->where($mwhere)->select();
    if(empty($m_info)){
        return;
    }
    $m_ids = array();
    $user_ids = array();
    foreach($m_info as $value){
        $get_ids = explode(',', $value['get_ids']);
        $m_ids[] = $value['id'];
        $user_ids = array_merge($user_ids,$get_ids);
    }

    M('Message')->save(array('id'=>array('in',$m_ids),'status'=>-1));
var_dump($user_ids);exit;
    for ($mi=0; $mi < count($user_ids); $mi++) {
        newMessage($user_ids[$mi],'look');
    }
}
