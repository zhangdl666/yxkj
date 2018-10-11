<?php
/**
 * Created by PhpStorm.
 * User: rzhd
 * Date: 2018/9/4
 * Time: 14:55
 */

namespace Pc\Model;
use Pc\Server\PageModel;

class AlarmPm25InfoModel extends BaseModel{
    public function getResultList($wheres){
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

        if(!empty($equipment_snos)){
            $where = array('obj.equipment_sno'=>array('in',$equipment_snos),'obj.is_end'=>2);
            $count = $this->alias("obj")->where($where)->count();
            $listRows = C('PAGE_SIZE');
            $page = new PageModel($count, $listRows);  //自己获取请求中的页面直接使用
            $page->lastSuffix = false;
            $pageHtml = $page->show();
            //历史告警列表
            $historydatas = $this->alias("obj")
                ->field("obj.*,b.name")
                ->join("yx_alarm_pm25 as b on b.id=obj.ap_id")
                ->where($where)
                ->limit($page->firstRow,$page->listRows)
                ->order('obj.up_time desc,obj.c_time desc')
                ->select();
            foreach($historydatas as &$historydata){
                $historydata['room_sno'] = $rooms[$historydata['equipment_sno']];
            }

            $rehistory =  array('pageHtml'=>$pageHtml,'items'=>$historydatas);


            $datas = $this->alias("obj")
                ->field("obj.*,b.name")
                ->join("left join yx_alarm_pm25 as b on b.id=obj.ap_id")
                ->where(array('obj.equipment_sno'=>array('in',$equipment_snos),'obj.is_end'=>array('in',array(1,3,4))))
                ->order('obj.up_time desc,obj.c_time desc')
                ->select();

            foreach($datas as &$data){
                if($data['is_end'] == 1){
                    if($data['ap_id'] != 0){
                        $data['room_sno'] = $rooms[$data['equipment_sno']];
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
//        echo '<pre>';
//        var_dump($roomdata);exit;

        $pagedata = array('roomdata'=>$roomdata,'alarmtotal'=>$alarmtotal,'firstnum'=>$firstnum,'secondnum'=>$secondnum,'thirdnum'=>$thirdnum,'alarmdatas'=>$alarmdatas);

        return array('pagedata'=>$pagedata,'rehistory'=>$rehistory);

    }


    /**
     * 查出该酒店的签约合同和房间数据
     */
    public function getHotelRoom($h_id)
    {
        $where = array();

        $where['p.h_id'] = $h_id;
        $where['l.status'] = array('eq', 1);
        $list = M(RmainModel::TABLENAME_IFNO)
            ->alias('l')
            ->join('left join ' . C('DB_PREFIX') . RmainModel::TABLENAME_ORDER . ' as p on p.id = l.oo_id')
            ->where($where)
            ->field('l.room_sno,l.equipment_sno,l.ctime')
            ->select();
        foreach ($list as $key => &$val) {
            $val['sort'] = preg_replace('/([\x80-\xff]*)/i', '', $val['room_sno']);
        }
        return arraySequence($list, 'sort', 'SORT_ASC');
    }
}