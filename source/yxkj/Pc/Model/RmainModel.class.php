<?php
/**
 * Author: ' Silent
 * Time: 2017/9/12 18:12
 */

namespace Pc\Model;


class RmainModel extends BaseModel
{
    /**
     * 保养方式类型
     */
    const STATUS_OPER_ONE = 1; // 1保养
    const STATUS_OPER_TWO = 2; // 2维修

    /**
     * 订单类型类型
     */
    const STATUS_ORDER_ONE = 2; // 2保养
    const STATUS_ORDER_TWO = 3; // 3维修

    const TABLENAME_ORDER = 'oper_order';
    const TABLENAME_IFNO = 'oper_info';
    const TABLENAME_OPER = 'equipment_oper';

    /**
     * 维修数数据占比
     */
    public static function getRepCountMake()
    {
        $data = array();
        $where = array();
        $where['p.type'] = self::STATUS_ORDER_TWO;
        $where['p.status'] = 4;
        $where['l.status'] = 4;
        $where['l.eo_id'] = array('neq', '');
        $list = M(self::TABLENAME_IFNO)
            ->alias('l')
            ->join('left join ' . C('DB_PREFIX') . self::TABLENAME_ORDER . ' as p on p.id = l.oo_id')
            ->join('left join ' . C('DB_PREFIX') . self::TABLENAME_OPER . ' as s on s.id = l.eo_id')
            ->where($where)
            ->field('l.eo_id,s.name as oper_name,COUNT(l.id) as num')
            ->group('oper_name')
            ->select();
        $item = array();
        foreach ($list as $key => $val) {
            $item[$key]['value'] = $val['num'];
            $item[$key]['name'] = $val['oper_name'];
        }
        $data['list'] = $list;
        $data['item'] = json_encode($item);
        return $data;
    }

    public static function getzhanbi()
    {
        // 统计维修数据
        $where = array();
        $where['p.type'] = self::STATUS_ORDER_TWO;
        $where['p.status'] = 4;
        $where['l.status'] = 4;
        $where['l.eo_id'] = array('neq', '');
        $list = M(self::TABLENAME_IFNO)
            ->alias('l')
            ->join('left join ' . C('DB_PREFIX') . self::TABLENAME_ORDER . ' as p on p.id = l.oo_id')
            ->join('left join ' . C('DB_PREFIX') . self::TABLENAME_OPER . ' as s on s.id = l.eo_id')
            ->where($where)
            ->field('l.eo_id,s.name as oper_name,COUNT(l.id) as num')
            ->group('oper_name')
            ->select();
        $wnum = array_sum(i_array_column($list, 'num'));
        $wheres = array();
        $wheres['p.type'] = self::STATUS_ORDER_ONE;
        $wheres['p.status'] = 4;
        $wheres['l.status'] = 4;
        $wheres['l.eo_id'] = array('neq', '');
        $lists = M(self::TABLENAME_IFNO)
            ->alias('l')
            ->join('left join ' . C('DB_PREFIX') . self::TABLENAME_ORDER . ' as p on p.id = l.oo_id')
            ->join('left join ' . C('DB_PREFIX') . self::TABLENAME_OPER . ' as s on s.id = l.eo_id')
            ->where($wheres)
            ->field('l.eo_id,s.name as oper_name,COUNT(l.id) as num')
            ->group('oper_name')
            ->select();
        $bnum = array_sum(i_array_column($lists, 'num'));
        $wnumbai = $wnum;
        $bnumbai = $bnum;
        $data[0]['value'] = filter_money($wnumbai);
        $data[0]['name'] = '维修';
        $data[1]['value'] = filter_money($bnumbai);
        $data[1]['name'] = '保养';
        $datas['list'] = $data;
        $datas['item'] = json_encode($data);
        return $datas;
    }

    public static function getzhanbis($quyu, $type, $htId, $startTime, $endTime)
    {
        $where = array();
        $where['p.type'] = self::STATUS_ORDER_TWO;
        $where['l.eo_id'] = array('neq', '');
        if ($type) {
            $where['w.user_type'] = array('eq', $type);
        }
        if ($htId) {
            $where['p.hc_id'] = array('eq', $htId);
        }
        if ($quyu) {
            $where['y.provice'] = array('like', $quyu);
        }
        if ($startTime && $endTime) {
            $startTimes = strtotime($startTime);
            $endTimes = strtotime($endTime);
            $where['p.etime'] = array(
                array('elt', $endTimes),
                array('egt', $startTimes),
                'and'
            );
        }
        $where['l.status'] = 4;
        $where['p.status'] = 4;
        $list = M(self::TABLENAME_IFNO)
            ->alias('l')
            ->join('left join ' . C('DB_PREFIX') . self::TABLENAME_ORDER . ' as p on p.id = l.oo_id')
            ->join('left join ' . C('DB_PREFIX') . self::TABLENAME_OPER . ' as s on s.id = l.eo_id')
            ->join('left join ' . C('DB_PREFIX') . UserRoleModel::TABLENAME_USER . ' as w on w.id = p.u_id')
            ->join('left join ' . C('DB_PREFIX') . HotelModel::TABLENAME_HOTEL . ' as y on y.id = p.h_id')
            ->where($where)
            ->field('l.eo_id,s.name as oper_name,COUNT(l.id) as num')
            ->group('oper_name')
            ->select();
        $wheres = array();
        $wheres['p.type'] = self::STATUS_ORDER_ONE;
        $wheres['l.eo_id'] = array('neq', '');
        if ($type) {
            $wheres['w.user_type'] = array('eq', $type);
        }
        if ($htId) {
            $wheres['p.hc_id'] = array('eq', $htId);
        }
        if ($quyu) {
            $wheres['y.provice'] = array('like', $quyu);
        }
        if ($startTime && $endTime) {
            $startTimess = strtotime($startTime);
            $endTimess = strtotime($endTime);
            $wheres['p.etime'] = array(
                array('elt', $endTimess),
                array('egt', $startTimess),
                'and'
            );
        }
        $wheres['l.status'] = 4;
        $wheres['p.status'] = 4;
        $lists = M(self::TABLENAME_IFNO)
            ->alias('l')
            ->join('left join ' . C('DB_PREFIX') . self::TABLENAME_ORDER . ' as p on p.id = l.oo_id')
            ->join('left join ' . C('DB_PREFIX') . self::TABLENAME_OPER . ' as s on s.id = l.eo_id')
            ->join('left join ' . C('DB_PREFIX') . UserRoleModel::TABLENAME_USER . ' as w on w.id = p.u_id')
            ->join('left join ' . C('DB_PREFIX') . HotelModel::TABLENAME_HOTEL . ' as y on y.id = p.h_id')
            ->where($wheres)
            ->field('l.eo_id,s.name as oper_name,COUNT(l.id) as num')
            ->group('oper_name')
            ->select();
        $wnum = array_sum(i_array_column($list, 'num'));
        $bnum = array_sum(i_array_column($lists, 'num'));
        $wnumbai = $wnum;
        $bnumbai = $bnum;
        $data[0]['value'] = filter_money($wnumbai);
        $data[0]['name'] = '维修';
        $data[1]['value'] = filter_money($bnumbai);
        $data[1]['name'] = '保养';
        $datas['list'] = $data;
        $datas['item'] = json_encode($data);
        return $datas;
    }

    public static function liftergetRepCountMake($quyu, $type, $htId, $startTime, $endTime)
    {
        $data = array();
        $where = array();
        $where['p.type'] = self::STATUS_ORDER_TWO;
        $where['l.eo_id'] = array('neq', '');
        if ($type) {
            $where['w.user_type'] = array('eq', $type);
        }
        if ($htId) {
            $where['p.hc_id'] = array('eq', $htId);
        }
        if ($quyu) {
            $where['y.provice'] = array('like', $quyu);
        }
        if ($startTime && $endTime) {
            $startTime = strtotime($startTime);
            $endTime = strtotime($endTime);
            $where['p.etime'] = array(
                array('elt', $endTime),
                array('egt', $startTime),
                'and'
            );
        }
        $where['l.status'] = 4;
        $where['p.status'] = 4;
        $list = M(self::TABLENAME_IFNO)
            ->alias('l')
            ->join('left join ' . C('DB_PREFIX') . self::TABLENAME_ORDER . ' as p on p.id = l.oo_id')
            ->join('left join ' . C('DB_PREFIX') . self::TABLENAME_OPER . ' as s on s.id = l.eo_id')
            ->join('left join ' . C('DB_PREFIX') . UserRoleModel::TABLENAME_USER . ' as w on w.id = p.u_id')
            ->join('left join ' . C('DB_PREFIX') . HotelModel::TABLENAME_HOTEL . ' as y on y.id = p.h_id')
            ->where($where)
            ->field('l.eo_id,s.name as oper_name,COUNT(l.id) as num')
            ->group('oper_name')
            ->select();
        $item = array();
        foreach ($list as $key => $val) {
            $item[$key]['value'] = $val['num'];
            $item[$key]['name'] = $val['oper_name'];
        }
        $data['list'] = $list;
        $data['item'] = json_encode($item);
        return $data;
    }

    /**
     * 保养数据占比
     */
    public static function getMaintainMake()
    {
        $data = array();
        $where = array();
        $where['p.type'] = self::STATUS_ORDER_ONE;
        // 订单已完成
        $where['p.status'] = 4;
        // 房间已完成
        $where['l.status'] = 4;
        $where['l.eo_id'] = array('neq', '');
        $list = M(self::TABLENAME_IFNO)
            ->alias('l')
            ->join('left join ' . C('DB_PREFIX') . self::TABLENAME_ORDER . ' as p on p.id = l.oo_id')
            ->join('left join ' . C('DB_PREFIX') . self::TABLENAME_OPER . ' as s on s.id = l.eo_id')
            ->where($where)
            ->field('l.eo_id,s.name as oper_name,COUNT(l.id) as num')
            ->group('oper_name')
            ->select();
        $item = array();
        foreach ($list as $key => $val) {
            $item[$key]['value'] = $val['num'];
            $item[$key]['name'] = $val['oper_name'];
        }
        $data['list'] = $list;
        $data['item'] = json_encode($item);
        return $data;
    }

    /**
     * 保养数据占比筛选
     */
    public static function lifterMaintainMake($quyu, $type, $htId, $startTime, $endTime)
    {
        $data = array();
        $where = array();
        $where['p.type'] = self::STATUS_ORDER_ONE;
        $where['l.eo_id'] = array('neq', '');
        if ($type) {
            $where['w.user_type'] = array('eq', $type);
        }
        if ($htId) {
            $where['p.hc_id'] = array('eq', $htId);
        }
        if ($quyu) {
            $where['y.provice'] = array('like', $quyu);
        }
        if ($startTime && $endTime) {
            $startTime = strtotime($startTime);
            $endTime = strtotime($endTime);
            $where['p.etime'] = array(
                array('elt', $endTime),
                array('egt', $startTime),
                'and'
            );
        }
        $where['l.status'] = 4;
        $where['p.status'] = 4;
        $list = M(self::TABLENAME_IFNO)
            ->alias('l')
            ->join('left join ' . C('DB_PREFIX') . self::TABLENAME_ORDER . ' as p on p.id = l.oo_id')
            ->join('left join ' . C('DB_PREFIX') . self::TABLENAME_OPER . ' as s on s.id = l.eo_id')
            ->join('left join ' . C('DB_PREFIX') . UserRoleModel::TABLENAME_USER . ' as w on w.id = p.u_id')
            ->join('left join ' . C('DB_PREFIX') . HotelModel::TABLENAME_HOTEL . ' as y on y.id = p.h_id')
            ->where($where)
            ->field('l.eo_id,s.name as oper_name,COUNT(l.id) as num')
            ->group('oper_name')
            ->select();
        $item = array();
        foreach ($list as $key => $val) {
            $item[$key]['value'] = $val['num'];
            $item[$key]['name'] = $val['oper_name'];
        }
        $data['list'] = $list;
        $data['item'] = json_encode($item);
        return $data;
    }

    /**
     * 维修数据趋势图
     */
    public static function getRepCountTrend()
    {
        $data = array();
        $where = array();
        $nowtime = strtotime(date("Y-m", strtotime("+1 months", strtotime(date("Y-m", time())))));
        $where['p.type'] = self::STATUS_ORDER_TWO;
        $where['l.eo_id'] = array('neq', '');
        $where['l.ctime'] = array('lt', $nowtime);
        $where['l.status'] = 4;
        $where['p.status'] = 4;
        $list = M(self::TABLENAME_IFNO)
            ->alias('l')
            ->join('left join ' . C('DB_PREFIX') . self::TABLENAME_ORDER . ' as p on p.id = l.oo_id')
            ->join('left join ' . C('DB_PREFIX') . self::TABLENAME_OPER . ' as s on s.id = l.eo_id')
            ->where($where)
            ->field("l.eo_id,s.name as oper_name,COUNT(l.id) as num,FROM_UNIXTIME(p.etime,'%Y-%m') as months")
            ->group('oper_name,months')
            ->select();
        $item = array();
        foreach ($list as $key => $val) {
            $item[$val['eo_id']][] = $val;
        }
        // 循环组装数据
        $countData = array();
        foreach ($item as $k => $v) {
            $countDatas = self::toArrayList($v, SalesModel::getMonth(), 'num', $v[0]['oper_name']);
            $countData[] = arraySequence($countDatas, 'months', 'SORT_ASC');
        }
        $newArray = array();
        foreach ($countData as $f => $n) {
            foreach ($n as $l => $v) {
                $newArray[$f][$l]['value'] = $v['num'];
                $newArray[$f][$l]['name'] = $v['oper_name'];
            }
        }
        $months = SalesModel::getMonth();
        sort($months);
        $data['month'] = json_encode($months);
        $data['list'] = $newArray;
        return $data;
    }

    public static function liftergetRepCountTrend($quyu, $type, $htId, $startTime, $endTime)
    {
        $data = array();
        $where = array();
        $nowtime = strtotime(date("Y-m", strtotime("+1 months", strtotime(date("Y-m", time())))));
        $where['p.type'] = self::STATUS_ORDER_TWO;
        $where['l.eo_id'] = array('neq', '');
        $where['l.ctime'] = array('lt', $nowtime);
        if ($htId) {
            $where['p.hc_id'] = array('eq', $htId);
        }
        if ($quyu) {
            $where['y.provice'] = array('like', $quyu);
        }
        if ($type) {
            $where['w.user_type'] = array('eq', $type);
        }
        if ($startTime && $endTime) {
            $startTime = strtotime($startTime);
            $endTime = strtotime($endTime);
            $where['p.etime'] = array(
                array('elt', $endTime),
                array('egt', $startTime),
                'and'
            );
        }
        $where['l.status'] = 4;
        $where['p.status'] = 4;
        $list = M(self::TABLENAME_IFNO)
            ->alias('l')
            ->join('left join ' . C('DB_PREFIX') . self::TABLENAME_ORDER . ' as p on p.id = l.oo_id')
            ->join('left join ' . C('DB_PREFIX') . self::TABLENAME_OPER . ' as s on s.id = l.eo_id')
            ->join('left join ' . C('DB_PREFIX') . UserRoleModel::TABLENAME_USER . ' as w on w.id = p.u_id')
            ->join('left join ' . C('DB_PREFIX') . HotelModel::TABLENAME_HOTEL . ' as y on y.id = p.h_id')
            ->where($where)
            ->field("l.eo_id,s.name as oper_name,COUNT(l.id) as num,FROM_UNIXTIME(p.etime,'%Y-%m') as months")
            ->group('oper_name,months')
            ->select();
        $item = array();
        foreach ($list as $key => $val) {
            $item[$val['eo_id']][] = $val;
        }
        // 循环组装数据
        $countData = array();
        foreach ($item as $k => $v) {
            $countDatas = self::toArrayList($v, SalesModel::getMonth(), 'num', $v[0]['oper_name']);
            $countData[] = arraySequence($countDatas, 'months', 'SORT_ASC');
        }
        $newArray = array();
        foreach ($countData as $f => $n) {
            foreach ($n as $l => $v) {
                $newArray[$f][$l]['value'] = $v['num'];
                $newArray[$f][$l]['name'] = $v['oper_name'];
            }
        }
        $month = SalesModel::getMonth();
        sort($month);
        $data['month'] = json_encode($month);
        $data['list'] = $newArray;
        return $data;
    }

    /**
     * 保养统计趋势图
     */
    public static function getMainCountTrend()
    {
        $data = array();
        $where = array();
        $nowtime = strtotime(date("Y-m", strtotime("+1 months", strtotime(date("Y-m", time())))));
        $where['p.type'] = self::STATUS_ORDER_ONE;
        $where['l.eo_id'] = array('neq', '');
        $where['l.ctime'] = array('lt', $nowtime);
        $where['l.status'] = 4;
        $where['p.status'] = 4;
        $list = M(self::TABLENAME_IFNO)
            ->alias('l')
            ->join('left join ' . C('DB_PREFIX') . self::TABLENAME_ORDER . ' as p on p.id = l.oo_id')
            ->join('left join ' . C('DB_PREFIX') . self::TABLENAME_OPER . ' as s on s.id = l.eo_id')
            ->where($where)
            ->field("l.eo_id,s.name as oper_name,COUNT(l.id) as num,FROM_UNIXTIME(p.etime,'%Y-%m') as months")
            ->group('oper_name,months')
            ->select();
        $item = array();
        foreach ($list as $key => $val) {
            $item[$val['eo_id']][] = $val;
        }
        // 循环组装数据
        $countData = array();
        foreach ($item as $k => $v) {
            $countDatas = self::toArrayList($v, SalesModel::getMonth(), 'num', $v[0]['oper_name']);
            $countData[] = arraySequence($countDatas, 'months', 'SORT_ASC');
        }

        // 排序
        $newArray = array();
        foreach ($countData as $f => $n) {
            foreach ($n as $l => $v) {
                $newArray[$f][$l]['value'] = $v['num'];
                $newArray[$f][$l]['name'] = $v['oper_name'];
            }
        }
        $months = SalesModel::getMonth();
        sort($months);
        $data['month'] = json_encode($months);
        $data['list'] = $newArray;
        return $data;
    }

    /**
     * 保养统计筛选图
     */
    public static function lifterMainCountTrend($quyu, $type, $htId, $startTime, $endTime)
    {
        $data = array();
        $where = array();
        $nowtime = strtotime(date("Y-m", strtotime("+1 months", strtotime(date("Y-m", time())))));
        $where['p.type'] = self::STATUS_ORDER_ONE;
        $where['l.eo_id'] = array('neq', '');
        $where['l.ctime'] = array('lt', $nowtime);
        if ($htId) {
            $where['p.hc_id'] = array('eq', $htId);
        }
        if ($type) {
            $where['w.user_type'] = array('eq', $type);
        }
        if ($quyu) {
            $where['y.provice'] = array('like', $quyu);
        }
        if ($startTime && $endTime) {
            $startTime = strtotime($startTime);
            $endTime = strtotime($endTime);
            $where['p.etime'] = array(
                array('elt', $endTime),
                array('egt', $startTime),
                'and'
            );
        }
        $where['l.status'] = 4;
        $where['p.status'] = 4;
        $list = M(self::TABLENAME_IFNO)
            ->alias('l')
            ->join('left join ' . C('DB_PREFIX') . self::TABLENAME_ORDER . ' as p on p.id = l.oo_id')
            ->join('left join ' . C('DB_PREFIX') . self::TABLENAME_OPER . ' as s on s.id = l.eo_id')
            ->join('left join ' . C('DB_PREFIX') . UserRoleModel::TABLENAME_USER . ' as w on w.id = p.u_id')
            ->join('left join ' . C('DB_PREFIX') . HotelModel::TABLENAME_HOTEL . ' as y on y.id = p.h_id')
            ->where($where)
            ->field("l.eo_id,s.name as oper_name,COUNT(l.id) as num,FROM_UNIXTIME(p.etime,'%Y-%m') as months")
            ->group('oper_name,months')
            ->select();
        $item = array();
        foreach ($list as $key => $val) {
            $item[$val['eo_id']][] = $val;
        }
        // 循环组装数据
        $countData = array();
        foreach ($item as $k => $v) {
            $countDatas = self::toArrayList($v, SalesModel::getMonth(), 'num', $v[0]['oper_name']);
            $countData[] = arraySequence($countDatas, 'months', 'SORT_ASC');
        }
        $newArray = array();
        foreach ($countData as $f => $n) {
            foreach ($n as $l => $v) {
                $newArray[$f][$l]['value'] = $v['num'];
                $newArray[$f][$l]['name'] = $v['oper_name'];
            }
        }
        $months = SalesModel::getMonth();
        sort($months);
        $data['month'] = json_encode($months);
        $data['list'] = $newArray;
        return $data;
    }

    /**
     * 整合数组
     */
    public static function toArrayList($data, $month, $field, $efield)
    {
        $item = array();
        foreach ($month as $key => $val) {
            $item[$key][$field] = 0;
            $item[$key]['months'] = $val;
            $item[$key]['eo_id'] = 0;
            $item[$key]['oper_name'] = $efield;
        }
        // 合并数组
        $List = array_merge_recursive((array)$data, (array)$item);
        $newArray = self::listArrayKey($List, 'months');
        return $newArray;
    }

    /**
     * 比对两个数组,存在两个的,则保留有值得数据
     */
    static public function listArrayKey($arr, $key)
    {
        $list = array();
        foreach ($arr as $k => $v) {
            if (in_array($v[$key], $list)) {
                unset($arr[$k]);
            } else {
                $list[$k] = $v[$key];
            }
        }
        $data = arraySequence($arr, $key);
        return $data;
    }

}