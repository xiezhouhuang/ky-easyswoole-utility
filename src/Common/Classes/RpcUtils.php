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
        $client = Rpc::getInstance($config)->client();
        $ctx1 = $client->addRequest($requestPath, $serviceVersion);
        $ctx1->setArg($args);
        $ctx1->setServiceNode($serviceNode);
        // 设置调用成功执行回调
        $res = [];
        $ctx1->setOnSuccess(function (Response $response) use (&$res) {
            $res = $response;
        });
        $ctx1->setOnFail(function (Response $response) use (&$res) {
            $res = $response;
            trace("RPC 错误代码:" . $response->getStatus(), 'error');
        });
        $client->exec();
        return $res;
    }
}
