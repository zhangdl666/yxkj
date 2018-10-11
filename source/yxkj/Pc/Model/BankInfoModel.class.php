<?php
/**
 * Created by PhpStorm.
 * User: redsabre
 * Date: 2017/9/23
 * Time: 10:35
 */
namespace Pc\Model;
use Pc\Model\BaseModel;
use Pc\Server\PageModel;

class BankInfoModel extends BaseModel{
    protected $_validate = array(
        array('name','require','银行名不能为空!'),
    );

    public function getBankInfo(){
    	$count = $this->where($wheres)->count();
		$listRows = C('PAGE_SIZE');
        $page = new PageModel($count, $listRows);  //自己获取请求中的页面直接使用
        $page->lastSuffix = false;
        $pageHtml = $page->show();

        $bankinfo = D('BankInfo')->limit($page->firstRow,$page->listRows)->order('id desc')->select();
        
        return array('pageHtml'=>$pageHtml,'items'=>$bankinfo);
    }

}