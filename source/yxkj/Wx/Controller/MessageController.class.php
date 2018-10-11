<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/10/26
 * Time: 14:07
 */
namespace Wx\Controller;
use Wx\Controller\BaseController;
use Wx\Model\MessageModel;
class MessageController extends BaseController{
    public function _initialize(){
        $this->model='Message';
        parent::_initialize();
    }
    /*public function index(){
        $omodel = $this->omodel;
        $this->_set_wheres($where);
        $this->_set_order($orders);
        $data_list = $this->model->getList($omodel,$where,$orders);
        foreach ($data_list['datas'] as &$val){
            $val['content'] = msubstr($val['content'],0,150);
        }
        $this->assign($data_list);
        $this->display();
    }*/
    /**
     * 提供查询条件
     * @param  array  $wheres 查询条件
     */
    protected function _set_wheres(&$where){
        $userInfo=session('USERINFO');
        $where['status'] = 1;
        $where['_string']= '(oper_url is not null and status<>-1 and find_in_set('.$userInfo['id'].',get_ids)) OR (find_in_set('.$userInfo['id'].',get_ids) and oper_url is null)';
        $where['time'] = array('elt',time());
    }

    /**
     * 提供排序方式
     * @param string $orders 排序方式
     */
    protected function _set_order(&$orders){
        $orders='ctime desc';
    }
    public function getInfo(){
        $id = I('get.id');
        $userInfo=session('USERINFO');
        $addData=array(
            'm_id'=>$id,
            'get_id'=>$userInfo['id'],
            'ctime'=>time()
        );
        $messageLookInfo=M(MessageModel::TABLENAMELOOK)->where(['m_id'=>$id,'get_id'=>$userInfo['id']])->find();
        if(empty($messageLookInfo) && $userInfo['role_id'] != 1){
            $res=M(MessageModel::TABLENAMELOOK)->add($addData);
            if($res){
                newMessage($userInfo['id'],'look');
            }
        }
        $info = $this->model->where(['id'=>$id])->find();
        $this->assign($info);
        $this->display('info');
    }
}