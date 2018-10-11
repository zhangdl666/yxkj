<?php
/**
 * Author: ' Silent
 * Time: 2017/11/9 11:35
 */

namespace Pc\Server;

require_once 'D:\phpStudy\WWW\yxkj1\ThinkPHP\Library\Vendor\PHPSocket\vendor\autoload.php';
use Workerman\Worker;
use PHPSocketIO\SocketIO;

class PHPSockets
{
    public static function start()
    {

    }

    /**
     * 创建一个SocketIO服务端
     */
    public static function CreateIo()
    {
        // 创建socket.io服务端，监听2021端口
        $io = new SocketIO(3120);
        // 当有客户端连接时打印一行文字
        $io->on('connection', function($socket)use($io){
            echo "new connection coming\n";
        });

       dump(Worker::runAll()) ;
    }
}