<?php
/**
 * AlarmController.class.php
 * 预警信息
 * @author
 * @date 2018-09-04 10:20
 */

namespace Pc\Controller;


use Pc\Model\HotelModel;
use Pc\Model\RunInfoDataModel;

class AlarmController extends BaseController{
	/**
     * 初始化
     */
    public function _initialize(){
        $this->model = 'AlarmPm25Info';
        parent::_initialize();
    }

    public function index(){
        $res = session('USERINFO');
        not_oper_alarm($res['id']);
        $wheres = array();
        //获取酒店数据
        $hotels = $this->getHotel();
        $this->assign("hotels",$hotels);
        $userinfo = session('USERINFO');

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


        //数据查询
        $re_datas = $this->model->getResultList($wheres);
//        echo '<pre>';
//        var_dump($re_datas);exit;
        $this->assign($re_datas['pagedata']);
        $this->assign($re_datas['rehistory']);

        $this->_before_index_view();
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        $this->assign('placeholder', $this->placeholder);
        $this->assign('server_ip',get_server_ip());

        $this->display();
    }

    /**
     * 获取已签约的酒店
     * @author ' Silent <1136359934@qq.com>
     */
    public function getHotel(){
        $hotelData = HotelModel::getHotelName();
        return $hotelData;
    }


    public function pieceslist(){
        $wheres = array();
        //获取酒店数据
        $hotels = $this->getHotel();
        $this->assign("hotels",$hotels);

        $h_id = I("post.h_id");
        if(empty($h_id)){
            $wheres['h_id'] = $hotels[0]['id'];
        }else{
            $wheres['h_id'] = $h_id;
            $this->assign('h_id',$h_id);
        }

        //数据查询
        $re_datas = $this->model->getResultList($wheres);
//        echo '<pre>';
//        var_dump($re_datas['rehistory']);exit;
        $this->assign($re_datas['pagedata']);
        $this->assign($re_datas['rehistory']);
        $this->display('table');
    }

}