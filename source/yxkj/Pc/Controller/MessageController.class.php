<?php
/**
 * Created by ${CONTROLLER_NAME}.
 * @auther: 刘小伟
 * Date: 2017/9/20
 * Time: 17:06
 */
namespace Pc\Controller;
use PC\Model\MessageModel;
class MessageController extends BaseController{
    public function _initialize(){
        $this->model = 'Message';
        parent::_initialize();
    }
    protected function _before_edit_view()
    {
        $userInfo=session('USERINFO');
        $this ->assign('role',$userInfo['role_id']);
        $data = M('Role') -> field( 'id , name') ->where(array('type' =>array('in',array(1,2))))->select();
        $this ->assign( 'data' ,$data);
    }
    //添加
    public function messgaeAdd(){
        $_POST['time'] =strtotime($_POST['stime']);
       if($_POST['get_ids'] != -1){
           $uwhere = array('role_id'=>$_POST['get_ids'],'status'=>1,'type'=>array('neq',3));
       }else{
            $uwhere = array('status'=>1,'type'=>array('neq',3));
       }
       $get_id = M('User') ->field('id')->where($uwhere)->select();
       $row = i_array_column($get_id,'id');
       $_POST['get_ids'] = implode(',',$row);

        if(empty($_POST['time'])){
            $_POST['time'] = time();
        }

        $_POST['ctime'] = time();
        $_POST['otype'] = 2;
        if($this->model->create()){
            $res =  M('Message')->add($_POST);
            if($res){
                /*if($_POST['get_ids'] == -1){
                    newMessage();
                }else{*/
                    $ids=explode(',',$_POST['get_ids']);
                    for($i=0;$i<count($ids);$i++){
                        newMessage($ids[$i]);
                    }
                /*}*/

            }
            $this->admin_ajax_return($res);
        }else{
            ajax_return(0, $this->model->getError());
        }
    }
    //删除选中
    public function delate(){
        $data = json_encode($_POST['name']);
        $_POST = json_decode($data);
        $arr = explode(',',$_POST);
        foreach ($arr as $val ){
            M('MessageLook') ->where(array('m_id'=>$val)) ->delete();
            $num =   M('Message') ->where(array('id'=>$val)) ->delete();
            //刪除对应关系表中的查看记录
        }
        if(is_numeric($num)){
            ajax_return(1,'刪除成功',U('index'));
        }else{
            ajax_return(0,'刪除失敗');
        }
    }
    /**
     * 提供排序方式
     * @param string $orders 排序方式
     */
    protected function _set_order(&$orders){
        $orders='time desc';
    }
    /**
     * 提供查询条件
     * @param  array  $wheres 查询条件
     */
    protected function _set_wheres(&$wheres){
        $userInfo=session('USERINFO');
        if($userInfo['role_id'] == 1){
            $wheres['_string'] = 'oper_url is null and otype=2';
        }else{
            $wheres['_string']= '(oper_url is not null and status<>-1 and find_in_set('.$userInfo['id'].',get_ids)) OR (find_in_set('.$userInfo['id'].',get_ids) and oper_url is null) OR get_ids = -1';
            $wheres['time']   = array('elt',time());
        }
    }

    public function _before_index_view(){
        $userInfo=session('USERINFO');
       $this ->assign('role',$userInfo['role_id']);
    }

    public function getInfo()
    {
        $id = I('get.id');
        $userInfo=session('USERINFO');
        $addData=array(
            'm_id'=>$id,
            'get_id'=>$userInfo['id'],
            'ctime'=>time()
        );
        $messageLookInfo=M(MessageModel::TABLENAMELOOK)->where(array('m_id'=>$id,'get_id'=>$userInfo['id']))->find();
        if(empty($messageLookInfo) && $userInfo['role_id'] != 1){
            $res=M(MessageModel::TABLENAMELOOK)->add($addData);
            if($res){
                newMessage($userInfo['id'],'look');
            }
        }
        $info = $this->model->getInfo($id);
        $this->assign($info);
        $this->display('info');
    }

}
