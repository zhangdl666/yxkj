<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/9/25
 * Time: 10:06
 */

namespace Wx\Controller;


use Pc\Model\UserRoleModel;
use Wx\Model\SalesCountModel;
use Wx\Model\SalesModel;
use Think\Controller;

class SalesController extends BaseController
{
    private $user_id;
    public $model = 'User';

    public function _initialize()
    {
        // TODO: Change the autogenerated stub
        $this->user_id = 1;
        parent::_initialize();
    }

    public function bindPercentage($data, $field)
    {
        $num = array_sum(i_array_column($data, $field));
        foreach ($data as $key => &$val) {
            $val['roportion'] = ($val[$field] / $num) * 100;
        }
        $newArray = array();
        foreach ($data as $keys => $vals) {
            if ($keys < 5) {
                $newArray[] = $vals;
            }
        }
        return $newArray;
    }

    /**
     * 统计数据
     */
    public function index()
    {
        $data = M('AccountsType')->order('type')->select();

        foreach ($data as $key => &$val) {
            if ($val['type'] == 1) {
                $val['biaoshi'] = '共享' . $val['price'];
            } else {
                $val['biaoshi'] = '租赁' . $val['price'];
            }
        }
        $salenum = SalesCountModel::getSalesVolume();
        $hotelnum = SalesCountModel::getHotelNum();
        $roomnum = SalesCountModel::getHotelRoom();
        $pronum = SalesCountModel::getProductNum();
        $contnum = SalesCountModel::getContractNum();
        $typesnum = SalesCountModel::getTypesNum();
        $saleDatas = SalesModel::countHotelSales();
        $this->assign('saleDatas', $saleDatas);
        $this->assign('months', json_encode(i_array_column($saleDatas['all_num'], 'months')));
        $this->assign('num', json_encode(i_array_column($saleDatas['all_num'], 'price')));
        $this->assign('salenum', $salenum);
        $this->assign('hotelnum', $hotelnum);
        $this->assign('roomnum', $roomnum);
        $this->assign('pronum', $pronum);
        $this->assign('contnum', $contnum);
        $this->assign('data', $data);
        $this->assign('typenum', $typesnum);
        $this->display('index');
    }

    /**
     * 异步加载统计页面
     */
    public function Count()
    {
        $ids = I('get.ids', 0, 'intval');
        $stime = I('get.stime');
        $etime = I('get.etime');
        $provice = I('get.provice');
        $qdid = I('get.qdid');
        $jid = I('get.jid');
        if ($ids == 1) {
            // 销售额统计数据
            if ($stime && $etime) {
                $this->assign('stime', $stime);
                $this->assign('etime', $etime);
                $starttime = strtotime(sprintf('%s 00:00:00',$stime));
                $endtime = strtotime(sprintf('%s 23:59:59',$etime));
                $where['l.ctime'] = array(
                    array('elt', $endtime),
                    array('egt', $starttime),
                    'and'
                );
            }
            if ($provice) {
                $where['h.provice'] = array('like', '%' . $provice . '%');
                $this->assign('provice', $provice);
            }
            if ($qdid) {
                if ($qdid == 1) {
                    $qd_name = '内聘';
                } else if ($qdid == 2) {
                    $qd_name = '个人';
                } else if ($qdid == 3) {
                    $qd_name = '团队';
                } else {
                    $qd_name = '';
                }
                $this->assign('qds_name', $qd_name);
                $where['p.channel_type'] = array('eq', $qdid);
            }
            $saleDatas = SalesModel::countHotelSales($where);

            $this->assign('city_name', json_encode(i_array_column($saleDatas['bj'], 'city')));
            $this->assign('city_sale', $saleDatas['bj_nums']);

            $rctime = M(SalesModel::TABLENAME_MONY)->where(array('status'=>3))->field('time')->order('time desc')->select();
            if(!$rctime){
                $rctime[0]['time'] = strtotime(date('Y-01-01'));
                $this->assign('rctime', $rctime);
            }else{
                $this->assign('rctime',$rctime);
            }

            $pro_num = M(SalesModel::TABLENAME_MONY)
                ->alias('l')
                ->join('left join ' . C('DB_PREFIX') . SalesModel::TABLENAME_HOTEL . ' as h on h.id = l.h_id')
                ->join('left join ' . C('DB_PREFIX') . \Pc\Model\HotelGetModel::TABLENAME . ' as s on s.h_id = h.id')
                ->join('left join ' . C('DB_PREFIX') . \Pc\Model\HotelModel::TABLENAME_TYPE . ' as j on j.id = h.ht_id')
                ->join('left join ' . C('DB_PREFIX') . UserRoleModel::TABLENAME_USER . ' as x on x.id = s.sale_id')
                ->join('left join ' . C('DB_PREFIX') . \Pc\Model\SalesModel::TANLENAME_EXT . ' as p on p.u_id = x.id')
                ->field('SUM(l.price) as price,h.city')
                ->where($where)
                ->where(array('s.is_default' => 1, 'l.price' => array('neq', 0)))
                ->where(array('l.status' => array('eq', 3)))
                ->where(array('s.sale_id' => array('neq', 0)))
                ->group('city')->select();

            // 统计图线条展示
            // 默认排序五个,各省占比数据
            $tongjidata = SalesModel::countHotelSales($where);
            if ($where['h.provice']) {
                foreach ($pro_num as $key => &$val) {
                    $val['provice'] = $val['city'];
                }
//                dump($pro_num);
                $proviceAll = arraySequence($pro_num, 'price', 'SORT_DESC');
                $proviceAlls = $this->bindPercentage($proviceAll, 'price');
            } else {
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


//            dump($saleDatas);
            $salenum = SalesCountModel::getSalesVolume();
            $this->assign('months', json_encode(i_array_column($saleDatas['all_num'], 'months')));
            $this->assign('num', json_encode(i_array_column($saleDatas['all_num'], 'price')));
            $this->assign('bj_nums_name', json_encode(i_array_column($saleDatas['bj'], 'all_price')));
            if ($where['h.provice']) {
                $this->assign('provice_name', json_encode(i_array_column($pro_num, 'provice')));
            } else {
                $this->assign('provice_name', json_encode(i_array_column($saleDatas['pro_num'], 'provice')));
            }

            $this->assign('type_name', json_encode(i_array_column($saleDatas['type_num'], 'type_name')));
            $this->assign('qd_name', json_encode(i_array_column($saleDatas['qd_num'], 'user_type_name')));
            $this->assign('salenum', $salenum);
            $this->assign('bj_nums', $saleDatas['bj_nums']);
            $this->assign('pro_nums', $saleDatas['pro_nums']);
            $this->assign('type_nums', $saleDatas['type_nums']);
            $this->assign('qd_nums', $saleDatas['qd_nums']);
            $this->assign('bj', $saleDatas['bj'][0]['price']);
            $this->assign('saleDatas', $saleDatas);
            $this->display('SaleCount');
        } else if ($ids == 2) {
            // 统计酒店数据
            // 销售额统计数据
            if ($stime && $etime) {
                $starttime = strtotime(sprintf('%s 00:00:00',$stime));
                $endtime = strtotime(sprintf('%s 23:59:59',$etime));
                $where['s.ctime'] = array(
                    array('elt', $endtime),
                    array('egt', $starttime),
                    'and'
                );
            }
            if ($provice) {
                $where['s.provice'] = array('like', '%' . $provice . '%');

            }
            if ($qdid) {
                $where['p.channel_type'] = array('eq', $qdid);
            }
            $hotelDatas = SalesModel::countHotelNum($where);

            // 统计图线条展示
            // 默认排序五个,各省占比数据
            $tongjidata = SalesModel::countHotelNum($where);
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


            $this->assign('type_name_one', json_encode(i_array_column($hotelDatas['pro_num'], 'provice')));
            $this->assign('type_name_two', json_encode(i_array_column($hotelDatas['type_num'], 'type_name')));
            $this->assign('type_name_three', json_encode(i_array_column($hotelDatas['qd_num'], 'user_type_name')));
            $this->assign('hotel_value_one', $hotelDatas['pro_nums']);
            $this->assign('type_nums', $hotelDatas['type_nums']);
            $this->assign('qd_nums', $hotelDatas['qd_nums']);
            $this->assign('months', json_encode(i_array_column($hotelDatas['all_num'], 'months')));
            $this->assign('num', json_encode(i_array_column($hotelDatas['all_num'], 'num')));
            $this->assign('hotelDatas', $hotelDatas);
            $this->display('HotelCount');
        } else if ($ids == 3) {
            if ($provice) {
                $where['j.provice'] = array('like', '%' . $provice . '%');
            }
            if ($qdid) {
                $where['p.channel_type'] = array('eq', $qdid);
            }
            // 统计客房数
            $RoomsDatas = SalesModel::countHotelRoom($where);

            $tongjidata = SalesModel::countHotelRoom($where);

            $roomtypeAll = arraySequence($tongjidata['type_room_num'], 'num', 'SORT_DESC');
            $roomtypeAlls = $this->bindPercentage($roomtypeAll, 'num');
            $this->assign('roomtypeAlls', $roomtypeAlls);

            $this->assign('type_name_one', json_encode(i_array_column($RoomsDatas['type_room_num'], 'type_name')));
            $this->assign('room_nums', $RoomsDatas['room_nums']);
            $this->assign('months', json_encode(i_array_column($RoomsDatas['hotel_room'], 'months')));
            $this->assign('num', json_encode(i_array_column($RoomsDatas['hotel_room'], 'num')));
            $this->assign('RoomsDatas', $RoomsDatas);
            $this->display('RoomCount');
        } else if ($ids == 4) {
            // 销售额统计数据
            if ($stime && $etime) {
                $starttime = strtotime(sprintf('%s 00:00:00',$stime));
                $endtime = strtotime(sprintf('%s 23:59:59',$etime));
                $where['l.ctime'] = array(
                    array('elt', $endtime),
                    array('egt', $starttime),
                    'and'
                );
            }
            if ($provice) {
                $where['s.provice'] = array('like', '%' . $provice . '%');
            }
            if ($qdid) {
                $where['p.channel_type'] = array('eq', $qdid);
            }
            // 统计产品数
            $data = SalesModel::countGoodsNum($where);

            $tongjidata = SalesModel::countGoodsNum($where);
            $goodstypeAll = arraySequence($tongjidata['goods_num'], 'num', 'SORT_DESC');
            $goodstypeAlls = $this->bindPercentage($goodstypeAll, 'num');
            $this->assign('roomtypeAlls', $goodstypeAlls);

            $this->assign('datas', json_encode(i_array_column($data['all_num'], 'num')));
            $this->assign('type_name', json_encode(i_array_column($data['goods_nums'], 'name')));
            $this->assign('month', json_encode(i_array_column($data['all_num'], 'months')));
            $this->assign('goods_value', $data['goods_nume']);
            $this->assign('data', $data);
            $this->display('ProdCount');
        } else if ($ids == 5) {
            // 统计合同数
            // 销售额统计数据
            if ($stime && $etime) {
                $starttime = strtotime(sprintf('%s 00:00:00',$stime));
                $endtime = strtotime(sprintf('%s 23:59:59',$etime));
                $where['l.ctime'] = array(
                    array('elt', $endtime),
                    array('egt', $starttime),
                    'and'
                );
            }
            if ($provice) {
                $where['h.provice'] = array('like', '%' . $provice . '%');
            }
            if ($qdid) {
                $where['f.channel_type'] = array('eq', $qdid);
            }
            if ($jid) {
                $where['l.at_id'] = array('eq', $jid);
            }
            $ContsDatas = SalesModel::countContract($where);

            $tongjidata = SalesModel::countContract($where);
            $counttypeAll = arraySequence($tongjidata['canal'], 'num', 'SORT_DESC');
            $counttypeAlls = $this->bindPercentage($counttypeAll, 'num');
            $this->assign('counttypeAll', $counttypeAlls);

            $this->assign('type_name_one', json_encode(i_array_column($ContsDatas['canal'], 'user_type_name')));
            $this->assign('canals', $ContsDatas['canals']);
            $this->assign('months', json_encode(i_array_column($ContsDatas['all_num'], 'months')));
            $this->assign('num', json_encode(i_array_column($ContsDatas['all_num'], 'num')));
            $this->assign('ProdsDatas', $ContsDatas);
            $this->display('ContCount');
        } else if ($ids == 6) {
            if ($stime && $etime) {
                $starttime = strtotime(sprintf('%s 00:00:00',$stime));
                $endtime = strtotime(sprintf('%s 23:59:59',$etime));
                $where['l.ctime'] = array(
                    array('elt', $endtime),
                    array('egt', $starttime),
                    'and'
                );
            }
            if ($qdid) {
                $where['p.channel_type'] = array('eq', $qdid);
            }
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
            // 统计渠道数
            $qdDatas = SalesModel::countChannel($where);
            // 统计图线条展示
            // 默认排序五个,各省占比数据
            $tongjidata = SalesModel::countChannel($where);
            $qdtypeAll = arraySequence($tongjidata['qd_num'], 'num', 'SORT_DESC');
            $qdtypeAlls = $this->bindPercentage($qdtypeAll, 'num');
            $this->assign('qdtypeAlls', $qdtypeAlls);


            $this->assign('type_name_one', json_encode(i_array_column($qdDatas['qd_num'], 'user_type_name')));
            $this->assign('qd_nums', $qdDatas['qd_nums']);
            $this->assign('monthones', json_encode(i_array_column($qdDatas['all_num_one'], 'months')));
            $this->assign('numones', json_encode(i_array_column($qdDatas['all_num_one'], 'num')));
            $this->assign('monthtwos', json_encode(i_array_column($qdDatas['all_num_two'], 'months')));
            $this->assign('numtwos', json_encode(i_array_column($qdDatas['all_num_two'], 'num')));
            $this->assign('monththes', json_encode(i_array_column($qdDatas['all_num_the'], 'months')));
            $this->assign('numthes', json_encode(i_array_column($qdDatas['all_num_the'], 'num')));
            $this->assign('qdDatas', $qdDatas);
            $this->display('qdCount');
        }
    }
}