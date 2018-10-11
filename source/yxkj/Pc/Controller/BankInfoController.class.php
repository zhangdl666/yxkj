<?php
/**
 * Created by PhpStorm.
 * User: redsabre
 * Date: 2017/9/23
 * Time: 10:22
 */

namespace Pc\Controller;

class BankInfoController extends BaseController
{
    private $listDataRows;

    /**
     * 初始化
     */
    public function _initialize()
    {
        $this->listDataRows = 7;
        parent::_initialize();

    }

    public function index()
    {
        //将当前列表的url保存到cookie中,为了 删除,添加,修改状态,编辑 之后跳转到该地址
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        $banks = $this->model->getBankInfo();
        $this->assign($banks);
        $this->display();
    }

    public function add()
    {
//        if($_POST){
//            if($_SESSION['token'] != $_POST['token']){
//                redirect('index',2,'请勿重复提交！');
//            }else{
//                $this->_before_edit_view();
//            }
//        }
        $token = $_SESSION['token'] = md5(1, 999);//每生成一次表单就修改一次$_SESSION['token']的值
        $this->assign('items', $token);
        $this->display('edit');
    }

//查看
    public function sel(){
        $id=$_GET['id'];
        $banks = D('BankInfo')->where(['id'=>$id])->find();
        $this->assign('items', $banks);
        $this->display();
    }
}

