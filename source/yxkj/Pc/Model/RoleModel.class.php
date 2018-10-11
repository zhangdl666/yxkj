<?php
/**
 * RoleModel.class.php
 * 后台公共模型
 * @author baddl
 * @date   2017-09-06 17:04
 */
namespace Pc\Model;
use Pc\Model\BaseModel;

class RoleModel extends BaseModel{
	/**
     * 添加/编辑处理
     * @return bool
     */
    public function operation(){
        $datas = I('post.');

        if(empty($datas['name'])){
            $this->error = '角色名不能为空!';
            return false;
        }
        //权限
        elseif(empty($datas['permission'])){
            $this->error = '请设置权限!';
            return false;
        }

        //编辑
        $this->startTrans();
        if($datas['id']){
            //该角色是否发生改变
            $where['id'] = $datas['id'];
            $where['oper_module'] = implode(',',$datas['permission']);
            $where['name'] = $datas['name'];
            $is_exsits = $this->where($where)->getField('id');
            if($is_exsits){
                $re_status = true;
            }else{
                $this->data['oper_module']=implode(',',$datas['permission']);
                $re_status = $this->save();
            }
        }else {
            $this->data['oper_module'] = implode(',', $datas['permission']);
            $this->data['ctime'] = time();
            $re_status = $this->add();
        }

        if($re_status){
            $this->commit();
            return true;
        }else{
            $this->rollback();
            return false;
        }
    }

    /**
     * 获取权限菜单
     * @return array
     */

    public function getRolePermission($is_super_master){
        $row = D('Role')->field('oper_module')->find($is_super_master);
        $module_id = explode(',',$row['oper_module']);
        $module = M('Module')->field('module,name')->where(array('id'=>array('in',$module_id)))->select();
        $arr['permission'] = i_array_column($module,'module');
        $arr['permission_id'] = $module_id;
        return $arr;
    }
}