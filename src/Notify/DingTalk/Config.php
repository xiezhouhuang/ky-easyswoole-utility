<?php

namespace Kyzone\EsUtility\Notify\DingTalk;

use Kyzone\EsUtility\Notify\Interfaces\ConfigInterface;
use Kyzone\EsUtility\Notify\Interfaces\NotifyInterface;
use EasySwoole\Spl\SplBean;

class Config extends SplBean implements ConfigInterface
{
    /**
     * WebHook
     * @var string
     */
    protected $url = '';

    /**
     * 密钥
     * @var string
     */
    protected $signKey = '';

    public function setUrl($url)
    {
        $this->url = $url;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function setSignKey($signKey)
    {
        $this->signKey = $signKey;
    }

    public function getSignKey()
    {
        return $this->signKey;
    }

    public function getNotifyClass(): NotifyInterface
    {
        return new Notify($this);
    }
}
