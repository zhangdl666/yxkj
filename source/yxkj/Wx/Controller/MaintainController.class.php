<?php
/**
 * Author: ' Silent
 * Time: 2017/9/20 9:50
 */

namespace Wx\Controller;

use Pc\Model\HotelModel;
use Pc\Model\RmainModel;
use Think\Controller;
class MaintainController extends BaseController
{
    public $model = 'User';

    /**
     * 保养统计
     */
    public function _initialize()
    {
        parent::_initialize();
        // TODO: Change the autogenerated stub
    }

    public function bindPercentage($data,$field){
        $num = array_sum(i_array_column($data,$field));
        foreach ($data as $key => &$val){
            $val['roportion'] = ($val[$field] / $num) * 100;
        }
        $newArray = array();
        foreach ($data as $keys => $vals){
            if($keys < 5){
                $newArray[] = $vals;
            }
        }
        return $newArray;
    }


    /**
     * 首页加载
     */
    public function index()
    {
        // 准备所需要的数据
        $hotelData = HotelModel::getHotelName();
        $this->assign('hotel', $hotelData);
        $this->display();
    }

    /**
     * 保养统计图加载
     */
    public function maintainCount()
    {
        $this->display();
    }

    /**
     * 保养统计图筛选
     */
    public function lifterRepair()
    {
        $quyu = I('get.quyu', '', 'trim');
        $type = I('get.type');
        $htId = I('get.ht_id');
        $startTime = I('get.startTime');
        $endTime = I('get.endTime');
        if($startTime){
            $startTime = sprintf('%s 00:00:00',$startTime);
        }
        if($endTime){
            $endTime = sprintf('%s 23:59:59',$endTime);
        }
        $onedata = RmainModel::lifterMaintainMake($quyu, $type, $htId, $startTime ,$endTime);
        $all_num = array_sum(i_array_column($onedata['list'], 'num'));
        foreach ($onedata['list'] as $key => &$val) {
            $val['rate'] = round(($val['num'] / $all_num) * 100);
        }
        $threedata = RmainModel::getzhanbis($quyu, $type, $htId, $startTime ,$endTime);
        $list = $this->bindPercentage($threedata['list'],'value');
        $this->assign('list',$list);
        $this->assign('typethree', json_encode(i_array_column($threedata['list'], 'name')));
        $this->assign('itemthree', $threedata['item']);
        $this->assign('threedata',$threedata);
        $this->assign('onedata', $onedata);
        $this->assign('type', json_encode(i_array_column($onedata['list'], 'oper_name')));
        $this->assign('item', $onedata['item']);
        $twodata = RmainModel::lifterMainCountTrend($quyu, $type, $htId, $startTime ,$endTime);
        $this->assign('types', $twodata['month']);
        $this->assign('items', json_encode($twodata['list']));
        $this->display(T('RepairCount/MaintainCounts'));
    }

    /**
     * 保养统计图加载
     */
    public function RepairCount()
    {
        $onedata = RmainModel::getMaintainMake();
        $all_num = array_sum(i_array_column($onedata['list'], 'num'));
        foreach ($onedata['list'] as $key => &$val) {
            $val['rate'] = round(($val['num'] / $all_num) * 100);
        }
        $threedata = RmainModel::getzhanbi();
        $list = $this->bindPercentage($threedata['list'],'value');
        $this->assign('list',$list);
        $this->assign('typethree', json_encode(i_array_column($threedata['list'], 'name')));
        $this->assign('itemthree', $threedata['item']);
        $this->assign('threedata',$threedata);

        $this->assign('onedata', $onedata);
        $this->assign('type', json_encode(i_array_column($onedata['list'], 'oper_name')));
        $this->assign('item', $onedata['item']);
        $twodata = RmainModel::getMainCountTrend();
        $this->assign('types', $twodata['month']);
        $this->assign('items', $twodata['list']);
        $this->display(T('RepairCount/MaintainCount'));
    }

    /**
     * 根据酒店获取合同
     */
    public function getContaract()
    {
        $id = I('post.id', 0, 'intval');
        $type = I('post.type', 0, 'intval');
        $data = HotelModel::getHotelConID($id);
        $this->ajaxReturn($data);
    }

    /**
     * 获取数据
     */
    public function getRep()
    {
        $data = RmainModel::getMainCountTrend();
        $result['code'] = 0;
        $result['data'] = $data['list'];
        $this->ajaxReturn($result);
    }

}