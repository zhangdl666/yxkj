<?php
/**
 * OperOrderController.class.php
 * 设备
 * @author: wy901216
 * @date: 2017/9/6  17:22
 */
namespace Pc\Controller;


class OperOrderController extends BaseController{
    /*
     * 设备安装列表        1待分配 2待处理 3待确认 4已完成
     * 这个列表涉及四种角色的用户   平台总经理、平台工程经理、平台工程人员、酒店工程经理
     * 平台总经理对应的数据是全部待分配、待安装、待确认、已安装的操作单     只有查看的权限
     * 平台工程经理对应的数据是全部待分配、待安装、待确认、已安装的操作单    有查看、分配安装的操作权限
     * 平台工程人员对应的数据是自己待安装、待确认、已安装的操作单     有查看、上传安装单的操作权限
     * 酒店工程经理对应的数据是全部待安装、待确认、已安装的操作单     有查看、确认安装的操作权限
     ***/
    public function index(){
        //获取当前用户信息和角色
        $userinfo = array();

        $role = 1;
        switch($role){
            case 1 :             //平台工程人员
                $wheres['u_id'] = $userinfo['id'];    //操作的工程人员ID
                $wheres['status'] = array('in',array(2,3,4));
                break;
            case 2 :       //酒店工程经理
                $wheres['status'] = array('in',array(2,3,4));
                break;
        }

        //计算统计数据
        $statistics = $this->model->getStatisticsData($wheres);
        $this->assign($statistics);

        //条件
        $wheres = array();
        $keyword = trim(I('get.keyword'));     //酒店名称
        $kstatus = I('get.kstatus');
        //关键词
        if(!empty($keyword)){
            $wheres[$this->query] = array('like','%'.$keyword.'%');
            $this->assign('keyword',$keyword);
        }

        //状态
        if($kstatus != '0'){
            $wheres['status'] = $kstatus;
            $this->assign('kstatus',$kstatus);
        }

        $wheres['type'] = 1;     //安装
        //查询条件
        $this->_set_wheres($wheres);
        //查询列表数据
        $re_datas = $this->model->getResultList($wheres);
        $this->assign($re_datas);

        //列表页面展示之前准备数据
        $this->_before_index_view();

        //将当前列表的url保存到cookie中,为了 删除,添加,修改状态,编辑 之后跳转到该地址
        cookie('__forward__', $_SERVER['REQUEST_URI']);

        $this->assign('placeholder',$this->placeholder);

        $this->display();
    }





}