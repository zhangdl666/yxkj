<?php
/**
 * Created by PhpStorm.
 * User: rzhd
 * Date: 2018/9/7
 * Time: 9:27
 */

namespace Wx\Model;
//namespace Pc\Model;

class AlarmPm25InfoModel extends BaseModel{
    public function getList($model,$wheres){
        //酒店对应的房间号数据
        $roomdata = $this->getHotelRoom($wheres['h_id']);     //默认显示第一个酒店的房间数据

        $equipment_snos = array();
        foreach($roomdata as $key=>&$value){
            $value['h_id'] = $wheres['h_id'];
            $equipment_snos[] = $value['equipment_sno'];
        }

        $rooms = getKeyValueArray($roomdata, 'equipment_sno','room_sno');
        //告警等级
        $sets = M("AlarmPm25")->select();
        $sets = getKeyValueArray($sets, 'id','type');

        $alarmdatas = array();   //告警列表
        $i = 0;
        $rehistory = array();   //历史告警列表
        $alarmtotal = 0;
        $firstnum = 0;
        $secondnum = 0;
        $thirdnum =0;

        $historydatas = array();
        if(!empty($equipment_snos)){
            $where = array('obj.equipment_sno'=>array('in',$equipment_snos),'obj.is_end'=>2);

            //历史告警列表
            $historydatas = $this->alias("obj")
                ->field("obj.*,b.name,b.type")
                ->join("yx_alarm_pm25 as b on b.id=obj.ap_id")
                ->where($where)
                ->order('obj.up_time desc,obj.c_time desc')
                ->select();
            foreach($historydatas as &$historydata){
                $historydata['room_sno'] = $rooms[$historydata['equipment_sno']];
                $historydata['start_time'] = date('Y-m-d H:i:s',$historydata['start_time']);
                $historydata['end_time'] = date('Y-m-d H:i:s',$historydata['end_time']);

            }


            //分配数据
            $rehistory = $this->limitDatas($historydatas,1,1);
//            echo '<pre>';
//            var_dump($historydatas);exit;


            $datas = $this->alias("obj")
                ->field("obj.*,b.name,b.type")
                ->join("left join yx_alarm_pm25 as b on b.id=obj.ap_id")
                ->where(array('obj.equipment_sno'=>array('in',$equipment_snos),'obj.is_end'=>array('in',array(1,3,4))))
                ->order('obj.up_time desc,obj.c_time desc')
                ->select();

            foreach($datas as &$data){
                if($data['is_end'] == 1){
                    if($data['ap_id'] != 0){
                        $data['room_sno'] = $rooms[$data['equipment_sno']];
                        $data['start_time'] = date('Y-m-d H:i:s',$data['start_time']);
                        $data['end_time'] = date('Y-m-d H:i:s',$data['end_time']);
                        $alarmdatas[$i] = $data;
                        $i++;
                        $alarmtotal++;
                        if($sets[$data['ap_id']] == 1){    //一级告警
                            $firstnum++;
                        }elseif($sets[$data['ap_id']] == 2){    //二级告警
                            $secondnum++;
                        }else{    //三级告警
                            $thirdnum++;
                        }
                    }
                }
            }


            foreach($roomdata as $key=>&$value){
                $mark = 1;  //正常
                foreach($datas as $data2){
                    if($value['equipment_sno'] == $data2['equipment_sno']){
                        if($data2['is_end'] == 1){     //正在进行
                            if($data2['ap_id'] != 0){
                                if($sets[$data2['ap_id']] == 1){    //一级告警
                                    $mark = 2;
                                }elseif($sets[$data2['ap_id']] == 2){    //二级告警
                                    $mark = 3;
                                }else{    //三级告警
                                    $mark = 4;
                                }
                                break;
                            }
                        }elseif($data2['is_end'] == 3){    //未开启
                            $mark = 5;     //暂未开启
                            break;
                        }

                    }
                }
                $value['mark'] = $mark;
            }

        }

        //序列化保存在session里面
        $historydatas = serialize($historydatas);
        session('DATAS', $historydatas);

        $pagedata = array('roomdata'=>$roomdata,'alarmtotal'=>$alarmtotal,'firstnum'=>$firstnum,'secondnum'=>$secondnum,'thirdnum'=>$thirdnum,'alarmdatas'=>$alarmdatas);

        return array('pagedata'=>$pagedata,'rehistory'=>$rehistory);

    }


    /**
     * 查出该酒店的签约合同和房间数据
     */
    public function getHotelRoom($h_id){
        $where = array();

        $where['p.h_id'] = $h_id;
        $where['l.status'] = array('eq', 1);
        $list = M("OperInfo")
            ->alias('l')
            ->join('left join ' . C('DB_PREFIX') .'oper_order as p on p.id = l.oo_id')
            ->where($where)
            ->field('l.room_sno,l.equipment_sno,l.ctime')
            ->select();
        foreach ($list as $key => &$val) {
            $val['sort'] = preg_replace('/([\x80-\xff]*)/i', '', $val['room_sno']);
        }
        return arraySequence($list, 'sort', 'SORT_ASC');
    }


    /**
     * ajax获取加载数据
     * @param $key 页码
     * @param $num 加载个数
     * @return array
     */
    public function showMoreDatas($key, $num = 10){
        $data = session('DATAS');
        $data = unserialize($data);

        $page = I('get.page') > 1 ? I('get.page') : 1;
        $get_datas = $this->limitDatas($data, $page, $key, $num);

        //对数据进行处理
        if($get_datas){
            $this->_handleRows($get_datas);
        }
        return $get_datas;
    }


    public function limitDatas($data,$page,$key,$num = 10){
        $show_data = array();
        //>>1.数组的总条数
        $count = count($data);
        //>>2.开始取值的索引值
        $index_start = ($key - 1) * $num;
        //>>3.根据$num循环取值的个数
        for ($i = 0; $i <= ($num - 1); ++$i) {
            if ($index_start <= ($count - 1)) {
                $show_data[] = $data[$index_start];
                ++$index_start;
            } else {
                break;//跳出循环
            }
        }

        //如果之前查询的记录加载完
        if(empty($show_data)){
            return null;
        }

        return array('page'=>$page,'key'=>$key,'datas'=>$show_data);
    }

}