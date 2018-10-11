<?php
/**
 * Author: ' Silent
 * Time: 2017/9/20 9:50
 */

namespace Wx\Model;


class RepairCountModel extends BaseModel
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
     * 保养数据占比
     */
    public static function getMaintainMake()
    {
        $data = array();
        $where = array();
        $where['p.type'] = self::STATUS_ORDER_ONE;
        $where['l.eo_id'] = array('neq', '');
        $list =  M(self::TABLENAME_IFNO)
                            ->alias('l')
                            ->join('left join ' . C('DB_PREFIX') . self::TABLENAME_ORDER . ' as p on p.id = l.oo_id')
                            ->join('left join ' . C('DB_PREFIX') . self::TABLENAME_OPER . ' as s on s.id = l.eo_id')
                            ->where($where)
                            ->field('l.eo_id,s.name as oper_name,COUNT(l.id) as num')
                            ->group('oper_name')
                            ->select();
        $item = array();
        foreach ($list as $key => $val){
            $item[$key]['value'] = $val['num'];
            $item[$key]['name'] = $val['oper_name'];
        }
        $data['list'] = $list;
        $data['item'] = json_encode($item);
        return $data;
    }


    /**
     * 保养统计趋势图
     */
    public static function getRepCountTrend()
    {
        $data = array();
        $where = array();
        $nowtime = strtotime(date("Y-m", strtotime("+1 months", strtotime(date("Y-m", time())))));
        $where['p.type'] = self::STATUS_ORDER_ONE;
        $where['l.eo_id'] = array('neq', '');
        $where['l.ctime'] = array('lt' , $nowtime);
        $list =  M(self::TABLENAME_IFNO)
                            ->alias('l')
                            ->join('left join ' . C('DB_PREFIX') . self::TABLENAME_ORDER . ' as p on p.id = l.oo_id')
                            ->join('left join ' . C('DB_PREFIX') . self::TABLENAME_OPER . ' as s on s.id = l.eo_id')
                            ->where($where)
                            ->field("l.eo_id,s.name as oper_name,COUNT(l.id) as num,FROM_UNIXTIME(l.ctime,'%Y-%m') as months")
                            ->group('oper_name,months')
                            ->select();
        $item = array();
        foreach ($list as $key => $val){
            $item[$val['eo_id']][] = $val;
        }
        // 循环组装数据
        $countData = array();
        foreach($item as $k => $v){
            $countData[] = self::toArrayList($v, SalesModel::getMonth(), 'num',$v[0]['oper_name']);
        }
        $newArray = array();
        foreach ($countData as $f =>$n){
            foreach ($n as $l=>$v){
                $newArray[$f][$l]['value'] = $v['num'];
                $newArray[$f][$l]['name'] = $v['oper_name'];
            }
        }
        $data['month'] = json_encode(SalesModel::getMonth());
        $data['list'] =  $newArray;
        return $data;
    }

    /**
     * 维修数数据占比
     */
    public static function getRepCountMake()
    {
        $data = array();
        $where = array();
        $where['p.type'] = self::STATUS_ORDER_TWO;
        $where['l.eo_id'] = array('neq', '');
        $list =  M(self::TABLENAME_IFNO)
                            ->alias('l')
                            ->join('left join ' . C('DB_PREFIX') . self::TABLENAME_ORDER . ' as p on p.id = l.oo_id')
                            ->join('left join ' . C('DB_PREFIX') . self::TABLENAME_OPER . ' as s on s.id = l.eo_id')
                            ->where($where)
                            ->field('l.eo_id,s.name as oper_name,COUNT(l.id) as num')
                            ->group('oper_name')
                            ->select();
        $item = array();
        foreach ($list as $key => $val){
            $item[$key]['value'] = $val['num'];
            $item[$key]['name'] = $val['oper_name'];
        }
        $data['list'] = $list;
        $data['item'] = json_encode($item);
        return $data;
    }

    public static function getRepCountTrends()
    {
        $data = array();
        $where = array();
        $nowtime = strtotime(date("Y-m", strtotime("+1 months", strtotime(date("Y-m", time())))));
        $where['p.type'] = self::STATUS_ORDER_TWO;
        $where['l.eo_id'] = array('neq', '');
        $where['l.ctime'] = array('lt' , $nowtime);
        $list =  M(self::TABLENAME_IFNO)
                            ->alias('l')
                            ->join('left join ' . C('DB_PREFIX') . self::TABLENAME_ORDER . ' as p on p.id = l.oo_id')
                            ->join('left join ' . C('DB_PREFIX') . self::TABLENAME_OPER . ' as s on s.id = l.eo_id')
                            ->where($where)
                            ->field("l.eo_id,s.name as oper_name,COUNT(l.id) as num,FROM_UNIXTIME(l.ctime,'%Y-%m') as months")
                            ->group('oper_name,months')
                            ->select();
        $item = array();
        foreach ($list as $key => $val){
            $item[$val['eo_id']][] = $val;
        }
        // 循环组装数据
        $countData = array();
        foreach($item as $k => $v){
            $countData[] = self::toArrayList($v, SalesModel::getMonth(), 'num',$v[0]['oper_name']);
        }
        $newArray = array();
        foreach ($countData as $f =>$n){
            foreach ($n as $l=>$v){
                $newArray[$f][$l]['value'] = $v['num'];
                $newArray[$f][$l]['name'] = $v['oper_name'];
            }
        }
        $data['month'] = json_encode(SalesModel::getMonth());
        $data['list'] =  $newArray;
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