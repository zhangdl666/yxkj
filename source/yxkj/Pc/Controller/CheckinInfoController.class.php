<?php
/**
 * CheckinInfoController.class.php
 * 结算
 * @author 刘小伟
 * @date   2017-09-18 18:02
 */
namespace Pc\Controller;
class CheckinInfoController extends BaseController{
    /**
	 * 列表数据
	 */
    public function index()
    {
        $p = I('get.p', 1, 'intval');
        $data =D('CheckinInfo') ->hostory($p);
        $this->assign('items', $data['items']);
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        $this->assign('pageHtml', $data['pageHtml']);
       $this ->display();
    }
    /*
     * 历史详情
     * */
    public function looked(){
        $id = I('get.id');
        $p = I('get.p', 1, 'intval');
        $data =D('CheckinInfo') ->getmore($id,$p);
        $this->assign('items', $data['items']);
        $this->assign('pageHtml', $data['pageHtml']);
        $this ->display('hostory');
    }
    //删除
    public function indelate(){
        if(I('get.id') != ''){
            $id = I('get.id');
            $arr = array('id' =>$id);
        }else{
            $id = I('get.u_id');
            $arr = array('u_id' =>$id);
        }
        $num = M('CheckinInfo') -> where($arr) ->delete();
       if(is_numeric($num)){
           ajax_return(1,'操作成功！',cookie('__forward__'));
       }else{
           $this->admin_ajax_return(false);
       }
    }
}
