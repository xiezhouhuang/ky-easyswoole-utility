<?php

namespace Kyzone\EsUtility\Notify\WeCom\Message;

use EasySwoole\Spl\SplBean;
use Kyzone\EsUtility\Notify\Interfaces\MessageInterface;

abstract class Base extends SplBean implements MessageInterface
{
    /**
     * 手机号
     * @var array
     */
    protected $atMobiles = [];
    /**
     * Userid
     * @var array
     */
    protected $atUserIds = [];

    protected $isAtAll = false;


}
