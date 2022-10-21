<?php


namespace Kyzone\EsUtility\Common\Classes;


use EasySwoole\Component\Singleton;
use EasySwoole\Rpc\Config;

class Rpc extends \EasySwoole\Rpc\Rpc
{
    use Singleton;

    public function __construct(Config $config)
    {
        parent::__construct($config);
    }
}