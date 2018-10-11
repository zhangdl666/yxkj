<?php
/**
 * @author: ' Silent
 * @time: 2018/1/16 14:34
 */

namespace Pc\Server;

/**
 * 所有空气净化器数据
 * Class PurifyData
 * @package Pc\Purify
 */
class PurifyData
{
    /**
     * 表名
     * @var string
     */
    protected $table_prefix = 'upload_device_info_';
    const table_dev = 'oper_info';

    /**
     * 查询数据所在分表并获取数据
     * @author ' Silent <1136359934@qq.com>
     * @param $devCode
     */
    public function getDevTable($data)
    {
//        $HitID = M(self::table_dev)->where(array('equipment_sno' => $devCode))->getField('id');
        // 获取表名
        foreach ($this->devlist as $key => $val) {
            dump($val);
        }
    }
}