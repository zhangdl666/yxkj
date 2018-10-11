<?php
/**
 * Created by PhpStorm.
 * User: hjw
 * Date: 2017/9/9
 * Time: 14:27
 */

namespace Pc\Controller;


class AccountsTypeController extends BaseController{
    //查看
    public function sel(){
        $id=$_GET['id'];
        $row=M('AccountsType')->where(['id'=>$id])->find();
        $this->assign('row', $row);
        $this->display();
    }
    //新增，编辑
    public function operationer(){
        $where=array();
        $where['type'] = I('post.type');
        $price = I('post.price');
        if($where['type'] < 3){
            if(empty($price)){
                ajax_return(0,'结算价格不能为空!');
                return;
            }elseif($price < 0){
                ajax_return(0,'价格不能为负数!');
                return;
            }
            $where['price'] = $price;
            /*if(!empty(I('post.id'))){
                $where['id'] = array('neq',I('post.id'));
            }*/
            $count=M('AccountsType')->where($where)->count();
            if($count){
                ajax_return(0,'该结算模式已经存在不能重复添加!');
                return;
            }
        }elseif(empty($price)){
            $_POST['price'] = 0;
        }
        $this->operation();
    }
}