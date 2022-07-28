<?php

namespace Kyzone\EsUtility\Tcp\Controller;

/**
 * @extends Controller
 */
trait BaseControllerTrait
{

    protected function onException(\Throwable $throwable): void
    {
        \EasySwoole\EasySwoole\Trigger::getInstance()->throwable($throwable);
    }

    abstract public function onConnect();

    abstract public function onClose();

    abstract public function onReceive();
}
