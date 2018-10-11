<?php
/**
 * ModuleController.class.php
 * 模块管理
 * @author: wy901216
 * @date: 2017/9/4  15:28
 */

namespace Pc\Controller;
use Pc\Controller\BaseController;


class ModuleController extends BaseController{
    protected $meta_title = '模块';
    protected $placeholder = '模块名';

    /**
     * 获取列表
     */
    public function index(){
        //条件
        $wheres = array();
        $keyword = I('get.keyword');
        $kstatus = I('get.kstatus');
        //关键词
        if(!empty($keyword)){
            $wheres[$this->query] = array('like','%'.$keyword.'%');
            $this->assign('keyword',$keyword);
        }

        //状态
        if($kstatus == '0' || !empty($kstatus)){
            $wheres['status'] = $kstatus;
            $this->assign('kstatus',$kstatus);
        }else{
            $wheres['status'] = array('neq','-1');
        }
        //查询条件
        $this->_set_wheres($wheres);
        //数据查询
        $re_datas = $this->model->where($wheres)->select();
        $arr1 = array();
        foreach($re_datas as $key=>&$val){
            if($val['parent_id']==0){
                $val['level']='1-'.$val['id'];
            }else{
                $val['level']='1-'.$val['parent_id']."-".$val['sort'];
            }
            $arr1[$key]=$val['level'];
        }
//        foreach($re_datas as $key => $item){
//            $arr1[$key]=$item['level'];
//        }
        //dump($arr1);
        array_multisort($arr1,SORT_ASC,$re_datas);
        $this->assign('items',$re_datas);

        //列表页面展示之前准备数据
        $this->_before_index_view();
        //将当前列表的url保存到cookie中,为了 删除,添加,修改状态,编辑 之后跳转到该地址
        cookie('__forward__', $_SERVER['REQUEST_URI']);

        $this->assign('placeholder',$this->placeholder);
        $this->assign('meta_title',$this->meta_title);
        $this->display();
    }
    /**
     * 编辑数据
     */
    public function edit(){
        //添加/编辑展示之前准备数据
        $this->_before_edit_view();

        //获取详情
        $id = I('get.id');
        $info = $this->model->getInfo($id);
        $pinfo=$this->model->getInfo($info['parent_id']);
        if(!empty($pinfo)){
            $info['parent_name']=$pinfo['name'];
        }
        $this->assign($info);

        $redonly = I('get.redonly');
        if($redonly){
            $this->assign('redonly',$redonly);
        }

        $this->display();
    }


    /**
     * 添加/编辑展示之前准备数据
     */
    protected function _before_edit_view(){
        $menu=$this->model->getModule("id,parent_id,name");
        $this->assign('menu',json_encode($menu));
    }



}