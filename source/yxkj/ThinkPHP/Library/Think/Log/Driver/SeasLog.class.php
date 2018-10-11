<?php
/**
 * Created by 'Silent.
 * Email: 1136359934@qq.com
 * Time: 13:15
 */

namespace Think\Log\Driver;


class SeasLog
{
    // 实例化并传入参数
    public function __construct(){
        \SeasLog::setBasePath(C('LOG_PATH'));
        \SeasLog::setLogger('Home');
    }

    /**
     * 日志写入接口
     * @access public
     * @param string $log 日志信息
     * @param string $destination  写入目标
     * @return void
     */
    public function write($log,$lever='DEBUG') {
        \SeasLog::debug($log);
    }

    public function l($message,$level='INFO') {
        //调用SeasLog快捷存储日志的方法
        \SeasLog::log($level,$message);
    }

}