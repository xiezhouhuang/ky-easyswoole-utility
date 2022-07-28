<?php

namespace Kyzone\EsUtility\Tcp;

use EasySwoole\Socket\AbstractInterface\ParserInterface;
use EasySwoole\Socket\Bean\Caller;
use EasySwoole\Socket\Bean\Response;

class Parser implements ParserInterface
{
    public function decode($raw, $client): ?Caller
    {
        $data = json_decode($raw, true);
        if ( ! is_array($data)) {
            return null;
        }
        $caller = new Caller();
        $controller = !empty($data['controller']) ? $data['controller'] : 'Index';
        $action = !empty($data['action']) ? $data['action'] : 'index';
        $controller = "App\\Tcp\\Controller\\{$controller}";
        $caller->setControllerClass($controller);
        $caller->setAction($action);
        unset($data['controller'], $data['action']);
        $caller->setArgs($data);
        return $caller;
    }

    public function encode(Response $response, $client): ?string
    {
        return $response->getMessage();
    }
}