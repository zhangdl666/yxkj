<?php
/**
 * Author: ' Silent
 * Time: 2017/9/7 17:59
 */

namespace Pc\Controller;

use Pc\Model\HotelGetModel;
use Pc\Model\HotelModel;
use Pc\Model\SalesModel;
use Pc\Model\UserRoleModel;
use Wx\Model\SalesCountModel;

class SalesController extends BaseController
{
    public $model = 'Hotel';

    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 统计首页
     */
    public function index()
    {
        $salenum = SalesCountModel::getSalesVolume();
        $hotelnum = SalesCountModel::getHotelNum();
        $roomnum = SalesCountModel::getHotelRoom();
        $pronum = SalesCountModel::getProductNum();
        $contnum = SalesCountModel::getContractNum();
        $typesnum = SalesCountModel::getTypesNum();
        $data = M('AccountsType')->order('type')->select();
        foreach ($data as $key => &$val) {
            if ($val['type'] == 1) {
                $val['biaoshi'] = '共享' . $val['price'] . '/元次';
            } else {
                $val['biaoshi'] = '租赁' . $val['price'] . '/元次';
            }
        }
        // 统计所有
        $this->assign('salenum', $salenum);
        $this->assign('hotelnum', $hotelnum);
        $this->assign('roomnum', $roomnum);
        $this->assign('pronum', $pronum);
        $this->assign('contnum', $contnum);
        $this->assign('data', $data);
        $this->assign('typenum', $typesnum);
        $this->assign('data', $data);
        $this->display();
    }

    public function bindPercentage($data,$field){
        $num = array_sum(i_array_column($data,$field));
        foreach ($data as $key => &$val){
            $val['roportion'] = ($val[$field] / $num) * 100;
        }
        return $data;
    }

    /**
     * 统计展示
     */
    public function count()
    {
        $saleid = I('get.saleid', 0, 'intval');
        $provice = I('get.provice', '', 'trim');
        $qdid = I('get.qdid');
        $starttime = I('get.starttime', '', 'trim');
        $endtime = I('get.endtime', '', 'trim');
        $htid = I('get.htId', 0, 'intval');
        $jsId = I('get.jid');
        // 组装条件
        if ($saleid == SalesModel::STATUS_SALES_ONE) {
            $where = array();
            if ($provice) {
                $where['h.provice'] = array('like','%'.$provice.'%');
                $this->assign('provice', $provice);
            }
            if ($qdid) {
                $where['p.channel_type'] = array('eq', $qdid);
                if ($qdid == 1) {
                    $qdid = '内聘';
                } else if ($qdid == 2) {
                    $qdid = '个人';
                } else if ($qdid == 3) {
                    $qdid = '团队';
                }
                $this->assign('qdid',$qdid);
            }
            if ($starttime && $endtime) {
                $this->assign('stime',$starttime);
                $this->assign('etime',$endtime);
                $endtime = strtotime($endtime);
                $starttime = strtotime($starttime);
                $where['l.ctime'] = array(
                    array('elt', $endtime),
                    array('egt', $starttime),
                    'and'
                );

            }
            // 获取第一笔汇款金额的时间
            $rctime = M(SalesModel::TABLENAME_MONY)->where(array('status'=>3))->field('time')->order('time desc')->select();
            if(!$rctime){
                $rctime[0]['time'] = strtotime(date('Y-01-01'));
                $this->assign('rctime', $rctime);
            }else{
                $this->assign('rctime',$rctime);
            }


            $data = SalesModel::countHotelSales($where);
            $pro_num = M(SalesModel::TABLENAME_MONY)
                ->alias('l')
                ->join('left join ' . C('DB_PREFIX') . HotelModel::TABLENAME_HOTEL . ' as h on h.id = l.h_id')
                ->join('left join ' . C('DB_PREFIX') . HotelGetModel::TABLENAME . ' as s on s.h_id = h.id')
                ->join('left join ' . C('DB_PREFIX') . HotelModel::TABLENAME_TYPE . ' as j on j.id = h.ht_id')
                ->join('left join ' . C('DB_PREFIX') . UserRoleModel::TABLENAME_USER . ' as x on x.id = s.sale_id')
                ->join('left join ' . C('DB_PREFIX') . SalesModel::TANLENAME_EXT . ' as p on p.u_id = x.id')
                ->field('SUM(l.price) as price,h.city')
                ->where($where)
                ->where(array('s.is_default' => 1, 'l.price' => array('neq', 0),'l.status'=>3,'s.sale_id'=>array('neq',0)))
                ->group('city')->select();

            // 统计图线条展示
            // 默认排序五个,各省占比数据
            $tongjidata = SalesModel::countHotelSales($where);
            if($where['h.provice']){
                foreach ($pro_num as $key=>&$val){
                    $val['provice'] = $val['city'];
                }
                $proviceAll = arraySequence($pro_num, 'price', 'SORT_DESC');
                $proviceAlls = $this->bindPercentage($proviceAll, 'price');
            }else{
                $proviceAll = arraySequence($tongjidata['pro_num'], 'price', 'SORT_DESC');
                $proviceAlls = $this->bindPercentage($proviceAll, 'price');
            }
            // 默认排序五个,酒店类型占比量及金额
            $typeAll = arraySequence($tongjidata['type_num'], 'price', 'SORT_DESC');
            $typeAlls = $this->bindPercentage($typeAll, 'price');
            // 展示类型
            $channelAll = arraySequence($tongjidata['qd_num'], 'price', 'SORT_DESC');
            $channelAlls = $this->bindPercentage($channelAll, 'price');
            $this->assign('proviceAlls', $proviceAlls);
            $this->assign('typeAlls', $typeAlls);
            $this->assign('channelAlls', $channelAlls);

//            dump($data);
//            dump()
            $this->assign('city_name',json_encode(i_array_column($data['bj'], 'city')));
            $this->assign('city_sale',$data['bj_nums']);

            $this->assign('all_price', $data['bj'][0]['price'] ? $data['bj'][0]['price'] : 0);
            if($where['h.provice']) {
                $this->assign('type_name_one', json_encode(i_array_column($pro_num, 'provice')));
            }else{
                $this->assign('type_name_one', json_encode(i_array_column($data['pro_num'], 'provice')));
            }
            $this->assign('hotel_value_one', $data['pro_nums']);
            $this->assign('type_name_two', json_encode(i_array_column($data['type_num'], 'type_name')));
            $this->assign('hotel_value_two', $data['type_nums']);
            $this->assign('type_name_three', json_encode(i_array_column($data['qd_num'], 'user_type_name')));
            $this->assign('hotel_value_three', $data['qd_nums']);
            // 排序
            $months = SalesModel::getMonth();
            sort($months);
            $this->assign('month', json_encode($months));
            $this->assign('monthdata', json_encode(i_array_column($data['all_num'], 'price')));
            $this->assign('data', $data);
            $this->display('SalesCount');
        } else if ($saleid == SalesModel::STATUS_SALES_TWO) {
            $where = array();
            if ($provice) {
                $where['provice'] =array('like','%'.$provice.'%');
                $maps['l.provice'] = array('like','%'.$provice.'%');
                $doker['s.provice'] = array('like','%'.$provice.'%');
            }
            if ($qdid) {
                $doker['p.channel_type'] = array('eq', $qdid);
            }
            if ($starttime && $endtime) {
                $endtime = strtotime($endtime);
                $starttime = strtotime($starttime);
                $doker['s.get_time'] = array(
                    array('elt', $endtime),
                    array('egt', $starttime),
                    'and'
                );
            }
            $data = SalesModel::countHotelNum($where, $maps, $doker);

            // 统计图线条展示
            // 默认排序五个,各省占比数据
            $tongjidata = SalesModel::countHotelNum($where,$maps, $doker);
            $proviceAll = arraySequence($tongjidata['pro_num'], 'num', 'SORT_DESC');
            $proviceAlls = $this->bindPercentage($proviceAll, 'num');
            // 默认排序五个,酒店类型占比量及金额
            $typeAll = arraySequence($tongjidata['type_num'], 'num', 'SORT_DESC');
            $typeAlls = $this->bindPercentage($typeAll, 'num');
            // 展示类型
            $channelAll = arraySequence($tongjidata['qd_num'], 'count', 'SORT_DESC');
            $channelAlls = $this->bindPercentage($channelAll, 'count');
            $this->assign('proviceAlls', $proviceAlls);
            $this->assign('typeAlls', $typeAlls);
            $this->assign('channelAlls', $channelAlls);


            $this->assign('type_name_one', json_encode(i_array_column($data['pro_num'], 'provice')));
            $this->assign('hotel_value_one', $data['pro_nums']);
            $this->assign('type_name_two', json_encode(i_array_column($data['type_num'], 'type_name')));
            $this->assign('hotel_value_two', $data['type_nums']);
            $this->assign('type_name_three', json_encode(i_array_column($data['qd_num'], 'user_type_name')));
            $this->assign('hotel_value_three', $data['qd_nums']);
            $months = SalesModel::getMonth();
            sort($months);
            $this->assign('month', json_encode($months));
            $this->assign('monthdata', json_encode(i_array_column($data['all_num'], 'num')));
            $this->assign('data', $data);
            $this->display('HotelCount');
        } else if ($saleid == SalesModel::STATUS_SALES_THREE) {
            $where = array();
            if ($provice) {
                $where['j.provice'] = array('like','%'.$provice.'%');
            }
            if ($qdid) {
                $where['p.channel_type'] = array('eq', $qdid);
            }
            $data = SalesModel::countHotelRoom($where);
            // 统计图线条展示
            // 默认排序五个,各省占比数据
            $tongjidata = SalesModel::countHotelRoom($where);

            $roomtypeAll = arraySequence($tongjidata['type_room_num'], 'num', 'SORT_DESC');
            $roomtypeAlls = $this->bindPercentage($roomtypeAll, 'num');
            $this->assign('roomtypeAlls', $roomtypeAlls);


            $this->assign('type_name', json_encode(i_array_column($data['type_room_num'], 'type_name')));
            $months = SalesModel::getMonth();
            sort($months);
            $this->assign('month', json_encode($months));
            $this->assign('monthdata', json_encode(i_array_column($data['hotel_room'], 'num')));
            $this->assign('room_value', $data['room_nums']);
            $this->display('roomCount');
        } else if ($saleid == SalesModel::STATUS_SALES_FOUR) {
            $where = array();
            if ($provice) {
                $where['s.provice'] = array('like','%'.$provice.'%');
            }
            if ($qdid) {
                $where['p.channel_type'] = array('eq', $qdid);
            }
            if ($starttime && $endtime) {
                $endtime = strtotime($endtime);
                $starttime = strtotime($starttime);
                $where['l.ctime'] = array(
                    array('elt', $endtime),
                    array('egt', $starttime),
                    'and'
                );
            }
            $data = SalesModel::countGoodsNum($where);


            // 统计图线条展示
            // 默认排序五个,各省占比数据
            $tongjidata = SalesModel::countGoodsNum($where);
            $goodstypeAll = arraySequence($tongjidata['goods_num'], 'num', 'SORT_DESC');
            $goodstypeAlls = $this->bindPercentage($goodstypeAll, 'num');
            $this->assign('roomtypeAlls', $goodstypeAlls);

            $months = SalesModel::getMonth();
            sort($months);
            $this->assign('datas', json_encode(i_array_column($data['all_num'], 'num')));
            $this->assign('type_name', json_encode(i_array_column($data['goods_nums'], 'name')));
            $this->assign('month', json_encode($months));
            $this->assign('goods_value', $data['goods_nume']);
            $this->display('goodsCount');
        } else if ($saleid == SalesModel::STATUS_SALES_FIVE) {
            $where = array();
            if ($provice) {
                $where['h.provice'] = array('like','%'.$provice.'%');
            }
            if ($htid) {
                $where['l.at_id'] = array('eq', $htid);
            }
            if ($jsId) {
                $where['l.at_id'] = array('eq', $jsId);
            }
            if ($qdid) {
                $where['p.channel_type'] = array('eq', $qdid);
            }
            if ($starttime && $endtime) {
                $endtime = strtotime($endtime);
                $starttime = strtotime($starttime);
                $where['l.ctime'] = array(
                    array('elt', $endtime),
                    array('egt', $starttime),
                    'and'
                );
            }
            $data = SalesModel::countContract($where);

            // 统计图线条展示
            // 默认排序五个,各省占比数据
            $tongjidata = SalesModel::countContract($where);
            $counttypeAll = arraySequence($tongjidata['canal'], 'num', 'SORT_DESC');
            $counttypeAlls = $this->bindPercentage($counttypeAll, 'num');
            $this->assign('counttypeAll', $counttypeAlls);

            $this->assign('type_name', json_encode(i_array_column($data['canal'], 'user_type_name')));
            $this->assign('monthdata', json_encode(i_array_column($data['all_num'], 'num')));
            $months = SalesModel::getMonth();
            sort($months);
            $this->assign('month', json_encode($months));
            $this->assign('cont_value', $data['canals']);
            $this->assign('data', $data);
            $this->display('contractCount');
        } else if ($saleid == SalesModel::STATUS_SALES_SIX) {
            if ($starttime && $endtime) {
                $endtime = strtotime($endtime);
                $starttime = strtotime($starttime);
                $where['l.ctime'] = array(
                    array('elt', $endtime),
                    array('egt', $starttime),
                    'and'
                );
            }
            if ($qdid) {
                $where['p.channel_type'] = array('eq', $qdid);
            }
            $data = SalesModel::countChannel($where);

            // 统计图线条展示
            // 默认排序五个,各省占比数据
            $tongjidata = SalesModel::countChannel($where);
            $qdtypeAll = arraySequence($tongjidata['qd_num'], 'num', 'SORT_DESC');
            $qdtypeAlls = $this->bindPercentage($qdtypeAll, 'num');
            $this->assign('qdtypeAlls', $qdtypeAlls);


            if ($qdid != 0) {
                if ($qdid == 1) {
                    $qdid = '内聘';
                } else if ($qdid == 2) {
                    $qdid = '个人';
                } else if ($qdid == 3) {
                    $qdid = '团队';
                }
                $this->assign('qdid', $qdid);
            }
            $this->assign('type_name', json_encode(i_array_column($data['qd_num'], 'user_type_name')));
            $this->assign('qd_value', $data['qd_nums']);
            $months = SalesModel::getMonth();
            sort($months);
            $this->assign('month', json_encode($months));
            $this->assign('monthdataone', json_encode(i_array_column($data['all_num_one'], 'num')));
            $this->assign('monthdatatwo', json_encode(i_array_column($data['all_num_two'], 'num')));
            $this->assign('monthdatathe', json_encode(i_array_column($data['all_num_the'], 'num')));
            $this->display('qdCount');
        }
    }

    public function test()
    {
        dump(SalesModel::countChannel());
    }
}