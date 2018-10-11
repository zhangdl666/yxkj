<?php
/**
 * RmoneyAccountstViewModel.class.php
 * 回款 结算模式  视图
 * @author  baddl
 * @date 2017-09-05 19:52
 */
namespace Pc\Model;
use Think\Model\ViewModel;

class RmoneyAccountstViewModel extends ViewModel{
    public $viewFields = array(
        'rmoney' 	=> array('_table'=>'yx_return_money','etime','num','rprice','rtime','price','time','mtime','mprice','status'),
        'atype' 	=> array('_table'=>'yx_accounts_type','type','price'=>'at_price','_on'=>'rmoney.at_id=atype.id'),
        'hc'		=> array('_table'=>'yx_hotel_contract','month_price','_on'=>'rmoney.hc_id=hc.id'),
    );
}