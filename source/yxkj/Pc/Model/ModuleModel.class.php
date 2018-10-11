<?php
/**
 * ModuleController.class.php
 * 模块管理
 * @author: wy901216
 * @date: 2017/9/4  16:32
 */
namespace Pc\Model;


class ModuleModel extends BaseModel{
    // 自动验证定义
    protected $_validate = array(
        array('name','require','菜单名不能为空!'),
        array('name','','该菜单名已经存在！',0,'unique',1)
    );


    /**
     * 对添加/编辑数据进行操作
     */
    public function operation(){
        $id = I('post.id');
        $ass = explode('/',$this->data['method']);
        $this->data['module'] = $ass[0];
        //添加
        if(empty($id)){
            $this->data['ctime'] = time();
            $re_status = $this->add();
        }
        //编辑
        else{
            $this->data['utime'] = time();
            $re_status = $this->save();
        }

        return $re_status;
    }

    /**
     * 根据条件获取模块
     * @param  array  $field
     * @param  array  $where
     * @return  array
     */
    public function getModule($field='*',$where=array()){
        return $this->field($field)->where($where)->where(array('status'=>1))->order('sort asc')->select();
    }


    /**
     * 获取导航菜单数据
     * @param  array  $where
     * @return  array
     */
    public function getNavModule(){
        $pmodule=$this->getModule('id,parent_id,name,module',array('parent_id'=>0,'is_show'=>1));
        $module=$this->getModule('id,parent_id,name,module',array('parent_id'=>array('neq',0),'is_show'=>1));
        $new=array();
        $i=0;
        foreach($pmodule as $value){
            $new[$i]=$value;
            foreach($module as $val){
                if($val['parent_id']==$value['id']){
                    $new[$i]['child'][]=$val;
                }
            }
            $i++;
        }
        return $new;
    }
}