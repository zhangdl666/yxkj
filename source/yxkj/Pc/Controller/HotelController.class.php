<?php
/**
 * Author: ' Silent
 * Time: 2017/9/4 10:48
 */

namespace Pc\Controller;
use Org\Net\Http;
use Pc\Model\HotelGetModel;
use Pc\Model\HotelModel;
use Pc\Model\HotelUserModel;
use Pc\Model\RoomTypeModel;
use Pc\Model\UserObjModel;
use Pc\Server\PageModel;

class HotelController extends BaseController
{
    private $listDataRows;

    /**
     * 初始化
     */
    public function _initialize()
    {
        $this->listDataRows = C('PAGE_SIZE');
        parent::_initialize();
        // 权限作用域
        $userData = $this->identity()->getUser();
        if ($userData['role_id'] == 9) {
            $this->assign('role', 1);
        } else if ($userData['role_id'] == 8) {
            $this->assign('role', 2);
        }
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


    /**
     * 酒店首页
     */
    public function index()
    {
        $p = I('get.p', 1, 'intval');
        $filter = array();
        $filter['name'] = I('get.name', '', 'trim');
        if ($filter['name']) {
            $where['l.name'] = array('like', "%" . $filter['name'] . "%");
        }
        $filter['is_get'] = I('get.is_get', -1);
        $filter['is_name'] = I('get.is_name', '', 'trim');
        if ($filter['is_get'] > -1 && $filter['is_get'] !== '') {
            $where['l.is_get'] = $filter['is_get'];
        }
        $filter['ht_id'] = I('get.ht_id', -1, 'intval');
        if ($filter['ht_id'] > -1 && $filter['ht_id'] != 0) {
            $filter['type_name'] = I('get.type_name', '', 'trim');
            $where['l.ht_id'] = $filter['ht_id'];
        }
        $countData = HotelModel::toDayCount();
        $count = HotelModel::listCount($where);
        $PcPage = new PageModel($count, $this->listDataRows);
        $data = HotelModel::listData($p, $PcPage->listRows, $where);
        foreach ($data as &$val){
            if(!empty($val['img']) && !file_exists($_SERVER['DOCUMENT_ROOT'].$val['img'])){
                $val['img'] = '';
            }
        }
        $HotelType = HotelModel::getHotelType();
        $this->assign('HotelType', $HotelType);
        $this->assign('filter', $filter);
        $this->assign('page', $PcPage->show());
        $this->assign('data', $data);
        $this->assign('count', $countData);
        $this->display();
    }

    /**
     * 查看酒店信息
     */
    public function seeHotel()
    {
        $id = I('get.id', 0, 'intval');
        $data = HotelModel::findById($id);
        for ($i=0;$i<count($data['img']);$i++){
            if(!empty($data['img'][$i])){
                if(!file_exists($_SERVER['DOCUMENT_ROOT'].$data['img'][$i])){
                    $data['img'][$i] = '';
                }
            }
        }

        $date = HotelModel::claimHotelDate($id);
        $RoomType = HotelModel::getList($id,$type=2);
        $this->assign('RoomType', $RoomType);
        $this->assign('date', $date);
        $this->assign('data', $data);
        $this->display();
    }


    /**
     * 编辑和新增酒店
     */
    public function logged()
    {
        if (IS_AJAX || IS_POST) {
            //file_put_contents('./Uploads/hotel.txt', "\r\nhotel_第一步",FILE_APPEND);
            $hotel = array();
            $hotel['name'] = I('post.name', '', 'trim');
            $hotel['ht_id'] = I('post.ht_id', 0, 'intval');
            $hotel['room_num'] = I('post.room_num', '', 'trim');
            $hotel['provice'] = I('post.provice', '', 'trim');
            $hotel['city'] = I('post.city', '', 'trim');
            $hotel['county'] = I('post.county', '', 'trim');
            $hotel['provice_id'] = I('post.provice_id', 0, 'intval');
            $hotel['city_id'] = I('post.city_id', 0, 'intval');
            $hotel['county_id'] = I('post.county_id', 0, 'intval');
            $hotel['area'] = I('post.area', '', 'trim');
            $hotel['tell'] = I('post.tell', '', 'trim');
            $hotel['img'] = I('post.img', '', 'trim');
            // 酒店缩略图地址(200*150)
            $hotel['thumb_img'] = I('post.thumb_img', '', 'trim');
            $hotel['shang_name'] = I('post.shangname', '', 'trim');
            $hotel['shang_tell'] = I('post.shang_tell', '', 'trim');
            $hotel['all_name'] = I('post.all_name', '', 'trim');
            $hotel['all_tell'] = I('post.all_tell', '', 'trim');
            $hotel['money_name'] = I('post.money_name', '', 'trim');
            $hotel['money_tell'] = I('post.money_tell', '', 'trim');
            $hotel['project_name'] = I('post.project_name', '', 'trim');
            $hotel['project_tell'] = I('post.project_tell', '', 'trim');
            $hotel['hotel_user_id'] = I('post.hotel_user_id', 0, 'intval');
            // 酒店客房类型
            $hotel['status'] = I('post.status');
            $id = I('post.id', 0, 'intval');
            $HotelGetId = I('post.HotelGetId', 0, 'intval');
            $hotel['data_room'] = I('post.dataroom');
            if ($hotel['img']) {
                // 转换字符串
                $hotel['img'] = implode(',', $hotel['img']);
            }
            if ($id) {
                //file_put_contents('./Uploads/hotel.txt', "\r\nhotel_第二步",FILE_APPEND);
                // 编辑酒店
                $message = HotelModel::editHotel($id, $hotel, $HotelGetId);
            } else {
                //file_put_contents('./Uploads/hotel.txt', "\r\nhotel_第三步",FILE_APPEND);
                // 新增酒店
                $message = HotelModel::addHotel($hotel);
                //file_put_contents('./Uploads/hotel.txt', "\r\nhotel_第四步".$message,FILE_APPEND);
            }
            $this->ajaxReturn($message);
        } else {
            $id = I('get.id', 0, 'intval');
            $HotelType = HotelModel::getHotelType();
            $HotelUser = HotelUserModel::getUserList();
            //房间类型房间数
            $RoomType = HotelModel::getList($id);
            $Hotel = HotelModel::findById($id);
            $HotelGteId = HotelGetModel::getId($Hotel['sale_id'], $id);
            $this->assign('id', $id);
            $this->assign('Hotel', $Hotel);
            $this->assign('HotelGetId', $HotelGteId);
            $this->assign('HotelUser', $HotelUser);
            $this->assign('RoomType', $RoomType);
            $this->assign('HotelType', $HotelType);
            $this->display();
        }
    }

    public function compileIn()
    {
        $data = I('post.');
        $data['img'] = implode(',', $data['img']);
        $where['h_id'] = $data['id'];
        if (empty($data['hrtname'])) {
            ajax_return(0, '请选择客房类型');
            exit;
        } else {
            for ($j = 0; $j < count($data['hrtname']); $j++) {
                if (empty($data['room_nums'][$j]) || $data['room_nums'][$j] == 0) {
                    ajax_return(0, '请完善客房类型信息');
                    exit;
                }
                /* 酒店客房类型数量 */
                $ht_arr[] = array('h_id' => $data['id'], 'rt_id' => $data['hrtname'][$j], 'room_num' => $data['room_nums'][$j]);
            }
            unset($data['hrtname'], $data['room_nums']);
            M('HotelRoomType')->where($where)->delete();
            M('HotelRoomType')->addAll($ht_arr);
        }
        /* 修改酒店信息 */
        $this->operation();

    }

    /**
     * 下载模板文件
     * @param string $filename
     * @param string $showname
     */
    public function loadtempxlsx()
    {
        $url = dirname($_SERVER['SCRIPT_FILENAME']) . "/Public/xlsx/import.xlsx";
        $Agent = $_SERVER['HTTP_USER_AGENT'];
        if (ereg('Firefox', $Agent)) {
            // 解决火狐浏览器文件名被编码
            self::download($url, '优享空间酒店导入模板.xlsx');
        } else {
            // urlencode解决linux下不显示文件名
            self::download($url, urlencode('优享空间酒店导入模板.xlsx'));
        }
    }

    /**
     * 下载模板文件
     * @param string $filename
     * @param string $showname
     */
    public function loadtempxlsxs()
    {
        $url = dirname($_SERVER['SCRIPT_FILENAME']) . "/Public/xlsx/import1.xlsx";
        $Agent = $_SERVER['HTTP_USER_AGENT'];
        if (ereg('Firefox', $Agent)) {
            // 解决火狐浏览器文件名被编码
            self::download($url, '优享空间酒店导入模板.xlsx');
        } else {
            // urlencode解决linux下不显示文件名
            self::download($url, urlencode('优享空间酒店导入模板.xlsx'));
        }
    }


    static public function download ($filename, $showname='',$content='',$expire=180) {
        if(is_file($filename)) {
            $length = filesize($filename);
        }elseif(is_file(UPLOAD_PATH.$filename)) {
            $filename = UPLOAD_PATH.$filename;
            $length = filesize($filename);
        }elseif($content != '') {
            $length = strlen($content);
        }else {
            E($filename.L('下载文件不存在！'));
        }
        if(empty($showname)) {
            $showname = $filename;
        }
        $showname = self::sbasename($showname);
        if(!empty($filename)) {
            /*$finfo 	= 	new \finfo(FILEINFO_MIME);
            $type 	= 	$finfo->file($filename);*/
            $type = 'multipart/form-data';
        }else{
            $type	=	"application/octet-stream";
        }
        //发送Http Header信息 开始下载
        header("Pragma: public");
        header("Cache-control: max-age=".$expire);
        //header('Cache-Control: no-store, no-cache, must-revalidate');
        header("Expires: " . gmdate("D, d M Y H:i:s",time()+$expire) . "GMT");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s",time()) . "GMT");
        header("Content-Disposition: attachment; filename=".$showname);
        header("Content-Length: ".$length);
        header("Content-type: ".$type);
        header('Content-Encoding: none');
        header("Content-Transfer-Encoding: binary" );
        if($content == '' ) {
            readfile($filename);
        }else {
            echo($content);
        }
        exit();
    }

    /**
     * 替代basename,因为basename不支持中文
     * @param $filename
     * @return mixed
     */
    static public function sbasename($filename) {
        return preg_replace('/^.+[\\\\\\/]/', '', $filename);
    }
}