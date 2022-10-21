<?php


namespace Kyzone\EsUtility\Common\Classes;


use EasySwoole\Component\Di;
use EasySwoole\Rpc\Protocol\Response;

class RpcUtils
{
    const RPC_KEY = 'RPC_KEY';
    const RPC_NODE = 'RPC_NODE';

    public static function exec(string $requestPath, array $args = [], $serviceVersion = null)
    {
        /** @var \EasySwoole\Rpc\Server\ServiceNode $serviceNode */
        $serviceNode = Di::getInstance()->get(self::RPC_NODE);
        $config = new \EasySwoole\Rpc\Config();
        // rpc 具体配置请看配置章节
        $rpc = new \EasySwoole\Rpc\Rpc($config);
        $client = $rpc->client();
        // client 全局参数
        $client->setClientArg($args);
        /**
         * 调用商品列表
         */
        $ctx1 = $client->addRequest($requestPath, $serviceVersion);
        $ctx1->setServiceNode($serviceNode);
        // 设置请求参数
        $ctx1->setArg(['a', 'b', 'c']);
        // 设置调用成功执行回调
        $res = [];
        $ctx1->setOnSuccess(function (Response $response) use (&$res) {
            $res = $response;
        });
        $ctx1->setOnFail(function (Response $response) use (&$res) {
            $res = $response;
        });
        $client->exec();
        return $res;
    }
}
