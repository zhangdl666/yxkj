<?php
/**
 * @author: ' Silent
 * @time: 2018/1/16 13:43
 */
require __DIR__ . '/Push/autoload.php';

use JPush\Client as JPush;

class PushMessage
{
    protected $handler;

    /**
     * 初始化推送
     * PushMessage constructor.
     * @param array $options
     */
    public function __construct($options = array())
    {
        $options = array(
            'app_key' => 'e9462c2fd2f21fe0cd5eaf45',
            'master_secret' => '7bce5c3fb99fee49fbedb973',
        );
        $this->handler = new JPush($options['app_key'], $options['master_secret']);
    }

    /**
     * 指定用户推送("alias" : [ "4314", "892", "4531" ])
     * @author ' Silent <1136359934@qq.com>
     * @param $notAlert
     * @param $notTitle
     * @param $alias
     */
    public function aliasMessagePush($notAlert, $notContent, $alias, $type, $sno = '', $title = '')
    {
        if($type == 1){
            $params =  array(
                'types' => "$type",
            );
        }else if($type == 2){
            $params =  array(
                'types' => "$type",
                'sn' => $sno,
                'title' => $title,
            );
        }
        $response = $this->handler->push()
            // 推送平台设置
            ->setPlatform(array('ios'))
            // 推送设备指定(使用别名) 类型 : JSON Array
            ->addAlias($alias)
            ->iosNotification($notAlert, array(
                // 通知提示声音
                'sound' => 'sound.caf',
                // 自定义 Key/value 信息，以供业务使用。
                'extras' => $params
            ))
            // 消息内容体 msg_content(消息内容本身)
            ->message($notContent, array(
                // 	消息标题
                'title' => $notAlert,
            ))
            ->options(array(
                // sendno: 表示推送序号，纯粹用来作为 API 调用标识，
                // API 返回时被原样返回，以方便 API 调用方匹配请求与返回
                // 这里设置为 100 仅作为示例
                'sendno' => 100,

                // time_to_live: 表示离线消息保留时长(秒)，
                // 推送当前用户不在线时，为该用户保留多长时间的离线消息，以便其上线时再次推送。
                // 默认 86400 （1 天），最长 10 天。设置为 0 表示不保留离线消息，只有推送当前在线的用户可以收到
                // 这里设置为 1 仅作为示例
                'time_to_live' => 1,

                // apns_production: 表示APNs是否生产环境，
                // True 表示推送生产环境，False 表示要推送开发环境；如果不指定则默认为推送生产环境
                'apns_production' => true,

                // big_push_duration: 表示定速推送时长(分钟)，又名缓慢推送，把原本尽可能快的推送速度，降低下来，
                // 给定的 n 分钟内，均匀地向这次推送的目标用户推送。最大值为1400.未设置则不是定速推送
                // 这里设置为 1 仅作为示例
                //'big_push_duration' => 1
            ))->send();
        $result = array('code' => 1, 'message' => '发送失败');
        if ($response) {
            $result['code'] = 0;
            $result['message'] = '发送成功';
        }
        return $result;
    }


    /**
     * 全体用户推送("alias" : [ "4314", "892", "4531" ])
     * @author ' Silent <1136359934@qq.com>
     * @param $notAlert
     * @param $notTitle
     * @param $alias
     */
    public function aliasMessagePushs($notAlert, $notContent, $type)
    {
        $response = $this->handler->push()
            // 推送平台设置
            ->setPlatform(array('ios'))
            // 推送设备指定(使用别名) 类型 : JSON Array
            ->setAudience('all')
            ->iosNotification($notAlert, array(
                // 通知提示声音
                'sound' => 'sound.caf',
                // 自定义 Key/value 信息，以供业务使用。
                'extras' => array(
                    'type' => $type,
                ),
            ))
            // 消息内容体 msg_content(消息内容本身)
            ->message($notContent, array(
                // 	消息标题
                'title' => $notAlert,
            ))
            ->options(array(
                // sendno: 表示推送序号，纯粹用来作为 API 调用标识，
                // API 返回时被原样返回，以方便 API 调用方匹配请求与返回
                // 这里设置为 100 仅作为示例
                'sendno' => 100,

                // time_to_live: 表示离线消息保留时长(秒)，
                // 推送当前用户不在线时，为该用户保留多长时间的离线消息，以便其上线时再次推送。
                // 默认 86400 （1 天），最长 10 天。设置为 0 表示不保留离线消息，只有推送当前在线的用户可以收到
                // 这里设置为 1 仅作为示例
                'time_to_live' => 1,

                // apns_production: 表示APNs是否生产环境，
                // True 表示推送生产环境，False 表示要推送开发环境；如果不指定则默认为推送生产环境
                'apns_production' => false,

                // big_push_duration: 表示定速推送时长(分钟)，又名缓慢推送，把原本尽可能快的推送速度，降低下来，
                // 给定的 n 分钟内，均匀地向这次推送的目标用户推送。最大值为1400.未设置则不是定速推送
                // 这里设置为 1 仅作为示例
                //'big_push_duration' => 1
            ))->send();
        $result = array('code' => 1, 'message' => '发送失败');
        if ($response) {
            $result['code'] = 0;
            $result['message'] = '发送成功';
        }
        return $result;
    }


}