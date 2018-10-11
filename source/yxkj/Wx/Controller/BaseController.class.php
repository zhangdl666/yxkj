<?php
/**
 * BaseController.class.php
 * 后台公共控制器
 * @author baddl
 * @date   2017-09-04 09:48
 */

namespace Wx\Controller;

use Pc\Server\Http;
use Think\Controller;

class BaseController extends Controller
{
    protected $model;
    protected $omodel;
    //设置查询字段
    protected $query = 'name';
    protected $placeholder;

    /**
     * 初始化
     */

    public function _initialize()
    {
        //创建模型
        $this->omodel = $this->model ? $this->model : CONTROLLER_NAME;
        $this->model = D($this->omodel);
        $this->assign('group_name', '/Wx/');

        //登录微信
       $access_info = session('ACCESS_INFO');
      //微信时间是否过期
       if (empty($access_info['ACCESS_TIME']) || floor((time() - $access_info['ACCESS_TIME']) % 86400 % 60) >= 7200) {
           //$this->access_token();

           if (empty($access_info['OPENID'])) {
               //网页授权并获取access_token,openid
               $this->impower();
           }

           //获取access_token
           $access_data = $this->get_access_token();
           if($access_data != true){
          	    echo $access_data;exit;
           }
       } elseif (empty($access_info['OPENID'])) {
           $this->impower();
       }
       //是否已经授权
       $user_info = M('User')->field('id,parent_id,name,salt,real_name,mobile,img,age,sex,hotel_id,role_id')->where(array('uuid' => $access_info['OPENID']))->find();
       if (!empty($user_info)) {
           session('USERINFO', $user_info);
       } else {
           session('USERINFO', null);
       }

        //用户信息
        $userinfo = session('USERINFO');
        //用户未登录
        if (empty($userinfo)) {
            //去登录
            header('Location: ' . U('Login/index', array('openid' => $access_info['OPENID'])));
            exit;
        }
        $this->assign('username', $userinfo['name']);
        $this->assign('userimg', $userinfo['img']);

        $this->assign('now_module', CONTROLLER_NAME);

        $module = M("Role")->field('name,oper_module')->where(array('id' => session('USERINFO.role_id')))->find();
        $module_ids = explode(',', $module['oper_module']);
        //当前模块下可操作方法
        if (!empty($module_ids)) {
            $method = M("Module")->field('method')->where(array('module' => CONTROLLER_NAME, 'status' => 1, 'id' => array('in', $module_ids)))->select();
            $method_arr = i_array_column($method, 'method');
            $this->assign('method_arr', $method_arr);
        }

        /* 微信JS所需 */
       $wx_config = C('WEIXIN');
       $jssdk = new JSSDK($wx_config['AppID'], $wx_config['AppSecret']);
       $sign_package = $jssdk->getSignPackage();
       //file_put_contents('./Uploads/weixin_config.txt', var_export($sign_package,true),FILE_APPEND);
       $this->assign($sign_package);
    }


    /**
     * 计算待处理事项
     */
    public function getNoHaddleNum()
    {

    }

    /**
     * 网页授权
     */
    public function impower()
    {
        $redirect_uri = urlencode(WEB_URL . U('Weixin/get_token_openid'));
        $url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=' . C('WEIXIN.AppID') . '&redirect_uri=' . $redirect_uri . '&response_type=code&scope=snsapi_base&state=' . getRandChar(6) . '#wechat_redirect';
        header("Location:" . $url);
        exit;
    }

    /**
     * 获取ACCESS_TOKEN
     */
    public function get_access_token()
    {
        $wx_config = C('WEIXIN');
        $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' . $wx_config['AppID'] . '&secret=' . $wx_config['AppSecret'];
        $re_data = $this->http_curl($url);
        $re_data = json_decode($re_data);
        if (empty($re_data->errcode)) {
            $access_info = session('ACCESS_INFO');
            if (!empty($access_info)) {
                $access_info = array('OPENID' => $access_info['OPENID'], 'ACCESS_TOKEN' => $re_data->access_token, 'ACCESS_TIME' => time(), 'EXPIRES_IN' => $re_data->expires_in);
                session('ACCESS_INFO', $access_info);
                /*session('ACCESS_INFO.ACCESS_TOKEN',$re_data->access_token);
                session('ACCESS_INFO.ACCESS_TIME',time());
                session('ACCESS_INFO.EXPIRES_IN',$re_data->expires_in);*/
            } else {
                $access_info = array('ACCESS_TOKEN' => $re_data->access_token, 'ACCESS_TIME' => time(), 'EXPIRES_IN' => $re_data->expires_in);
                session('ACCESS_INFO', $access_info);
            }

            return true;
        } else {
            return $re_data->errmsg;
        }
    }

    /**
     * 获取用户openId
     */
    public function getOpenId()
    {
        $urlObj["appid"] = C('WEIXIN.AppID');
        $urlObj["secret"] = C('WEIXIN.AppSecret');
        $urlObj["code"] = getRandChar(6);
        $urlObj["grant_type"] = "authorization_code";
        $url = 'https://api.weixin.qq.com/sns/oauth2/access_token?' . http_build_query($urlObj);
        $re_data = $this->http_curl($url);
        $re_data = json_decode($re_data,true);
        if (empty($re_data->errcode)) {
            return array('status' => 1, 'openid' => $re_data->openid);
        } else {
            return $re_data['errmsg'];
        }
    }

    /**
     * @name   curl获取信息
     * @param  string $url 接收数据的api
     * @param  array /string $data 提交的数据
     */
    public function http_curl($url, $data = array())
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        //要求程序必须在30秒内完成,负责到30秒后放到后台执行
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        //设置获取的信息以文件流的形式返回，而不是直接输出
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        //是否检测服务器的证书是否由正规浏览器认证过的授权CA颁发的
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        //是否检测服务器的域名与证书上的是否一致
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

        if ($data) {
            // post数据
            curl_setopt($ch, CURLOPT_POST, 1);
            // post的变量
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        } else {
            //设置头文件的信息作为数据流输出
            curl_setopt($ch, CURLOPT_HEADER, 0);
        }

        $output = curl_exec($ch);
        curl_close($ch);
        //打印获得的数据
        return $output;
    }

    /**
     * 列表
     */
    public function index()
    {
        $omodel = $this->omodel;
        $this->_set_wheres($wheres);
        $this->_set_order($orders);
        $data_list = $this->model->getList($omodel, $wheres, $orders);
        $this->assign($data_list);
        //var_dump($data_list);exit;
        cookie('__forwards__', $_SERVER['REQUEST_URI']);
        $this->assign('page_title', '首页');
        $this->display();
    }

    /**
     * AJAX返回信息
     * @param  int $status 状态
     * @param  string $info 提示信息
     * @param  array $data 返回值
     */
    public function get_ajax_return($status, $info = null, $data = array())
    {
        if ($status == 1 && empty($info)) {
            $info = '操作成功！';
        } elseif ($status == 0) {
            if (!$info) {
                $info = $this->model->getError() ? $this->model->getError() : '操作失败！';
            }
        }

        ajax_return($status, $info, $data);
    }

    /**
     * 加载更多数据
     */
    public function show_more_data($key, $num = 10)
    {
        $get_datas = $this->model->showMoreDatas($key, $num);
        if ($get_datas) {
            $this->get_ajax_return(1, '加载成功！', $get_datas);
        } else {
            $this->get_ajax_return(0, '加载失败！');
        }
    }

    /**
     * 添加数据
     */
    public function add()
    {
        //添加/编辑展示之前准备数据
        $this->_before_edit_view();

        $this->display('edit');
    }

    /**
     * 编辑数据
     */
    public function edit()
    {
        //添加/编辑展示之前准备数据
        $this->_before_edit_view();

        //获取详情
        $id = I('get.id');
        $info = $this->model->getInfo($id);
        $this->assign($info);

        $redonly = I('get.redonly');
        if ($redonly) {
            $this->assign('redonly', $redonly);
        }

        $this->display();
    }

    /**
     * 对数据进行操作
     */
    public function operation()
    {
        if ($this->model->create() !== false) {
            $re_status = $this->model->operation();
            $this->admin_ajax_return($re_status);

        } else {
            ajax_return(0, $this->model->getError());
        }
    }

    /**
     * 删除
     * @param  string $ids IDs号
     * @return array
     */
    public function del($ids)
    {
        $re_status = $this->model->delete_data($ids);

        $this->admin_ajax_return($re_status);
    }

    /**
     * 改变状态
     * @param  string $ids 要操作的ID号
     * @param  int $status 状态
     */
    public function change_status($ids, $status = '-1')
    {
        $re_status = $this->model->changeStatus($ids, $status);

        $this->admin_ajax_return($re_status);
    }

    /**
     * AJAX 返回
     * @param  int $re_status
     */
    protected function admin_ajax_return($re_status)
    {
        if ($re_status != 0) {
            ajax_return(1, '操作成功！', cookie('__forwards__'));
        } else {
            $info = $this->model->getError() ? $this->model->getError() : '操作失败！';
            ajax_return(0, $info);
        }
    }

    /**
     * 提供查询条件
     * @param  array $wheres 查询条件
     */
    protected function _set_wheres(&$wheres)
    {

    }

    /**
     * 提供排序方式
     * @param string $orders 排序方式
     */
    protected function _set_order(&$orders)
    {

    }

    /**
     * 列表页面展示之前准备数据
     */
    protected function _before_index_view()
    {

    }

    /**
     * 添加/编辑展示之前准备数据
     */
    protected function _before_edit_view()
    {

    }

    /**
     * 文件上传
     */
    public function upload_file()
    {
        $img_dir = I('get.img_dir');
        $result = upload($img_dir);
        echo json_encode($result);
        exit;
    }

    /**
     * 文件下载
     * @param  string $filename 下载的文件名
     * @param  string $showname 显示的文件名
     */
    public function download_file($filename, $showname = '')
    {
        download($filename, $showname);
    }


    /**
     * 保存微信上传的图片
     */
    public function saveWXFile($img_dir = null, $media_id = null, $num = 0)
    {
        $img_dir = trim(I('post.img_dir')) ? trim(I('post.img_dir')) : $img_dir;
        //mid的获取
        $media_id = trim(I('post.media_id')) ? trim(I('post.media_id')) : $media_id;
        $urls = 'http://file.api.weixin.qq.com/cgi-bin/media/get?access_token=' . session('ACCESS_INFO.ACCESS_TOKEN') . '&media_id=' . $media_id;

        $curl_data = $this->http_curl($urls);
        //file_put_contents('./wxfile_acc_re.txt', $curl_data."\r\n",FILE_APPEND);
        $curl_data = json_decode($curl_data);
        if (isset($curl_data->errcode) && $num < 3) {
            ++$num;
            //file_put_contents('./wxfile_acc.txt', session('ACCESS_INFO.ACCESS_TOKEN')."\r\n",FILE_APPEND);
            $this->get_access_token();
            $this->saveWXFile($img_dir, $media_id, $num);
        }

        //file_put_contents('./wxfile.txt', $urls."\r\n",FILE_APPEND);
        if (!empty($img_dir)) {
            $img_dir = './Uploads/' . $img_dir . '/' . date('Y-m-d') . '/';
        } else {
            $img_dir = './Uploads/' . date('Y-m-d') . '/';
        }

        //接收到上传插件上传上来的文件保存到指定的位置
        if (!is_dir($img_dir)) {  //如果Uploads下的目录不存在就创建
            mkdir($img_dir, 0777, true);
        }
        //图片保存地址
        $file_path = $img_dir . getRandChar(13) . '.jpg';
        file_put_contents($file_path, file_get_contents($urls));

        echo json_encode(array('status' => 1, 'msg' => '上传成功', 'data' => substr($file_path, 1)));
        exit;
    }

    /**
     * 通过二维码链接获取设备MAC
     */
    public function get_mac()
    {
        $mac_url = urldecode(I('post.rishscan'));
        /*$mac_url_arr = explode(',', $mac_url);
        //file_put_contents('./mac.txt', "\r\n".$mac_url,FILE_APPEND);
        $mac = $mac_url_arr[0];
        if($mac){
            ajax_return(1, '获取成功', $mac);
            exit;
        }*/
        //file_put_contents('./mac.txt', "\r\n".var_export($mac_url_arr,true),FILE_APPEND);
        $get_mac_url = 'http://api.bjhike.com/api/v2_getDeviceMac/?qrticket=' . $mac_url;
        $re_data = $this->http_curl($get_mac_url);
        $re_data = json_decode($re_data);
        if (empty($re_data->errcode)) {
            ajax_return(1, '获取成功', $re_data->mac);
            exit;
        } else {
            ajax_return(0, $re_data['errmsg']);
            exit;
        }
    }

    /**
     * 验证二维码
     */
    public function verify_code($ticket)
    {
        $access_token = session('ACCESS_INFO.ACCESS_TOKEN');
        $url = 'https://api.weixin.qq.com/device/verify_qrcode?access_token=' . $access_token;
        $params = array('ticket' => $ticket);
        $codeData = json_decode(Http::post($url, $params),true);
        return $codeData;
    }
}