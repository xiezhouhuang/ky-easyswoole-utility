<?php

namespace Kyzone\EsUtility\Notify\WeCom;

use EasySwoole\HttpClient\HttpClient;
use Kyzone\EsUtility\Notify\Interfaces\ConfigInterface;
use Kyzone\EsUtility\Notify\Interfaces\MessageInterface;
use Kyzone\EsUtility\Notify\Interfaces\NotifyInterface;

class Notify implements NotifyInterface
{
    /**
     * @var Config
     */
    protected $Config = null;

    public function __construct(ConfigInterface $Config)
    {
        $this->Config = $Config;
    }

    /**
     * 每个机器人每分钟最多发送20条消息到群里，如果超过20条，会限流10分钟
     * @param MessageInterface $message
     * @return void
     */
    public function does(MessageInterface $message)
    {
        $data = $message->fullData();
        $url = $this->Config->getUrl();
        $client = new HttpClient($url);

        // 当前自定义机器人支持文本（text）、markdown（markdown）、图片（image）、图文（news）四种消息类型。
        $response = $client->postJson(json_encode($data));
        $json = json_decode($response->getBody(), true);
        if ($json['errcode'] !== 0)
        {
            // todo 异常处理
        }
    }
}
