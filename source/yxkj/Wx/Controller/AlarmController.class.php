<?php
/**
 * AlarmController.class.php
 * 预警信息
 * @author
 * @date 2018-09-04 10:30
 */
namespace Wx\Controller;
use Pc\Model\HotelModel;
use Wx\Controller\BaseController;
class AlarmController extends BaseController{

    public function _initialize(){
        $this->model = 'AlarmPm25Info';
        parent::_initialize();
    }

    public function index(){
        $userinfo = session('USERINFO');
        not_oper_alarm($userinfo['id']);
        $wheres = array();
        //获取酒店数据
        $hotels = $this->getHotel();
        $this->assign("hotels",$hotels);

        if($userinfo['hotel_id']){
            $wheres['h_id'] = $userinfo['hotel_id'];
        }else{
            $h_id = I("get.h_id");
            if(empty($h_id)){
                $wheres['h_id'] = $hotels[0]['id'];
            }else{
                $wheres['h_id'] = $h_id;
            }
        }


        $this->assign('h_id',$wheres['h_id']);
        $this->assign('userinfo',$userinfo);
        $this->assign('server_ip',get_server_ip());
        //数据查询
        $re_datas = $this->model->getList($this->model,$wheres);
//        echo '<pre>';
//        var_dump($re_datas);exit;
        $this->assign($re_datas['pagedata']);
        $this->assign($re_datas['rehistory']);

        cookie('__forwards__', $_SERVER['REQUEST_URI']);
        $this->assign('placeholder', $this->placeholder);

        $this->display();
    }

    /**
     * 获取已签约的酒店
     * @author ' Silent <1136359934@qq.com>
     */
    public function getHotel(){
        $data = HotelModel::getHotelName();

        return $data;
    }






}