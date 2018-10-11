<?php
/**
 * MessageMLViewModel.class.php
 * 消息 已读消息 视图
 * @author  baddl
 * @date 2017-12-03 10:41
 */
namespace Pc\Model;
use Think\Model\ViewModel;

class MessageMLViewModel extends ViewModel{
    public $viewFields = array(
        'message'=> array('id','title','type','content','oper_url','time','_type'=>'left'),
        'ml' 	=> array('_table'=>'yx_message_look','_on'=>'message.id=ml.m_id'),
    );
}