<?php
/**
 * Created by PhpStorm.
 * User: hjw
 * Date: 2017/9/15
 * Time: 10:33
 */

namespace Pc\Controller;


class ChannelLevelController extends BaseController
{
    /**
     * 提供排序方式
     * @param string $orders 排序方式
     */
    protected function _set_order(&$orders){
        $orders = 'room_num asc';
    }

    //添加渠道
    public function add(){
        $rows=M('ChannelLevel')->select();
        foreach ($rows as $row){
            //判断有没有基础比例
            if($row['room_num']==0){
               $room_num=true;
               break;
            }
        }
        if($room_num==true){
            //$room_num=true 就新增渠道等级
            $this->display('edit');
        }else{
            //$room_num=false 就新增基础渠道
            $this->display('edit1');
        }
    }


//编辑基础比例
    public function edit1(){
        //获取详情
        $id = I('get.id');
        $info = $this->model->getInfo($id);
        $this->assign($info);
        $this->display();
    }

    public function operation(){
        $postData=I('post.');
        //开启事物
        M('')->startTrans();
        if(!$this->model->create()){
            M('')->rollback();
            ajax_return(0, $this->model->getError());
            exit;
        }else{
            if(empty($postData['id'])){
                $postData['ctime'] = time();
                $res=$this->model->add($postData);
                $info='新增渠道等级';
            }else{
                $res=$this->model->save($postData);
                $info='编辑渠道等级';
            }
            if(!$res){
                M('')->rollback();
                ajax_return(1,$info.'失败!');
                exit;
            }
            $saleUser=M('SaleExt')->field('u_id,room_num,cl_id')->select();
            $level =M('ChannelLevel')->order('room_num')->select();
            foreach ($level as $key=>$val ){
                foreach ($saleUser as $item){
                    $flag=false;
                    if(!empty($level[$key+1]['id'])){
                        if($item['room_num']>=$val['room_num'] && $item['room_num']<=$level[$key+1]['room_num'] && $item['cl_id'] != $val['id']){
                            $flag = true;
                        }
                    }else{
                        if($item['room_num']>=$val['room_num'] && $item['cl_id'] != $val['id']){
                            $flag = true;
                        }
                    }
                    if($flag === true){
                        $saleUserRes=M('SaleExt')->where(array('u_id'=>$item['u_id']))->setField('cl_id',$val['id']);
                        if(!$saleUserRes){
                            M('')->rollback();
                            ajax_return(0, '修改销售人员的渠道等级失败!');
                            exit;
                        }else{
                            continue;
                        }
                    }else{
                        continue;
                    }
                }
            }
            M('')->commit();
            ajax_return(1,$info.'成功！',cookie('__forward__'));
        }
    }
    /**
     * 删除
     * @param  string $ids IDs号
     * @return array
     */
    public function del()
    {
        $id=I('get.ids');
        $users=M('sale_ext')->where(['cl_id'=>$id])->select();
        if($users){
            ajax_return(0,'该渠道等级已经在使用不能删除');
        }else{
            $re_status = $this->model->delete_data($id);

            $this->admin_ajax_return($re_status);
        }

    }

}