<?php

namespace Kyzone\EsUtility;

use EasySwoole\Command\Color;
use EasySwoole\EasySwoole\Command\Utility;
use EasySwoole\EasySwoole\ServerManager;
use EasySwoole\EasySwoole\Swoole\EventRegister;
use EasySwoole\Rpc\Service\AbstractService;
use EasySwoole\Socket\AbstractInterface\ParserInterface;
use EasySwoole\Spl\SplBean;
use EasySwoole\Socket\Bean\Response;
use Kyzone\EsUtility\Common\Classes\RpcUtils;
use Kyzone\EsUtility\Notify\EsNotify;
use Kyzone\EsUtility\Notify\Interfaces\ConfigInterface;

class EventMainServerCreate extends SplBean
{
    /**
     * 必传，MainServerCreate EventRegister对象
     * @var null | EventRegister
     */
    protected $EventRegister = null;

    /**
     * WebSocket事件， [EventRegister::onOpen => [Events::class, 'onOpen']]
     * @var null
     */
    protected $webSocketEvents = null;

    /**
     * WebSocket解释器
     * @var null
     */
    protected $WebSocketParser = WebSocket\Parser::class;

    /**
     * Tcp解释器
     * @var null
     */
    protected $TcpSocketParser = Tcp\Parser::class;

    /**
     *
     * @var null
     */
    protected $crontabClass = Crontab\Crontab::class;
    protected $crontabRunEnv = ['dev', 'test', 'produce'];


    protected $hotReloadWatchDirs = [EASYSWOOLE_ROOT . '/App', EASYSWOOLE_ROOT . '/vendor/kyzone'];
    protected $hotReloadFunc = [
        'on_change' => null, // callback Change事件
        'on_exception' => null, // callback 异常
        'reload_before' => null, // callback worker process reload 前
        'reload_after' => null, // callback worker process reload 后
    ];
    protected $consumerJobs = null;

    protected $notifyConfig = null;
    protected $rpcConfig = null;

    public function run()
    {
        // 仅在开启的是WebSocket服务时
        if (config('MAIN_SERVER.SERVER_TYPE') === EASYSWOOLE_WEB_SOCKET_SERVER) {
            $this->registerWebSocketServer();
        }
        // 是否开启子服务TCP
        if (config('SUB_SERVER.SERVER_TYPE') === EASYSWOOLE_SERVER) {
            $this->registerTcpServer();
        }
        $this->registerCrontab();
        $this->registerConsumer();
        $this->watchHotReload();
        $this->registerNotify();
        $this->registerRpc();
    }

    protected function registerWebSocketServer()
    {
        $register = $this->EventRegister;
        if (!$register instanceof EventRegister) {
            throw new \Exception('EventRegister Error');
        }

        $config = new \EasySwoole\Socket\Config();
        $config->setType(\EasySwoole\Socket\Config::WEB_SOCKET);
        if ($this->WebSocketParser) {
            $parserClassName = $this->WebSocketParser;
            $ParserClass = new $parserClassName();
            if ($ParserClass instanceof ParserInterface) {
                $config->setParser($ParserClass);
            }
        }

        $dispatch = new \EasySwoole\Socket\Dispatcher($config);
        $register->set(
            $register::onMessage,
            function (\Swoole\Websocket\Server $server, \Swoole\WebSocket\Frame $frame) use ($dispatch) {
                $dispatch->dispatch($server, $frame->data, $frame);
            }
        );
        $events = $this->webSocketEvents;
        if (is_array($events)) {
            foreach ($events as $event => $item) {
                $register->add($event, $item);
            }
        } else if (is_string($events) && class_exists($events)) {
            $allowNames = (new \ReflectionClass(EventRegister::class))->getConstants();
            $Ref = new \ReflectionClass($events);
            $public = $Ref->getMethods(\ReflectionMethod::IS_PUBLIC);

            foreach ($public as $item) {
                $name = $item->name;
                if ($item->isStatic() && isset($allowNames[$name])) {
                    $register->add($allowNames[$name], [$item->class, $name]);
                }
            }
        }
    }

    protected function registerTcpServer()
    {
        $register = $this->EventRegister;
        if (!$register instanceof EventRegister) {
            throw new \Exception('EventRegister Error');
        }
        $server = ServerManager::getInstance()->getSwooleServer();
        $tcpPort = $server->addlistener(config('SUB_SERVER.LISTEN_ADDRESS'), config('SUB_SERVER.PORT'), SWOOLE_TCP);
        $setting = config('SUB_SERVER.SETTING') ?? [];
        $tcpPort->set($setting);

        $config = new \EasySwoole\Socket\Config();
        $config->setType(\EasySwoole\Socket\Config::TCP);
        if ($this->TcpSocketParser) {
            $parserClassName = $this->TcpSocketParser;
            $ParserClass = new $parserClassName();
            if ($ParserClass instanceof ParserInterface) {
                $config->setParser($ParserClass);
            }
        }

        $dispatch = new \EasySwoole\Socket\Dispatcher($config);
        $config->setOnExceptionHandler(function (\Swoole\Server $server, \Throwable $throwable, string $raw, \EasySwoole\Socket\Client\Tcp $client, Response $response) {
            trace($throwable->getMessage(), 'error');
            $response->setStatus($response::STATUS_RESPONSE_AND_CLOSE);
        });
        $tcpPort->on($register::onReceive, function (\Swoole\Server $server, int $fd, int $reactorId, string $data) use ($dispatch) {
            $msgData = json_encode([
                "controller" => 'Index',
                "action" => 'onReceive',
                'message' => $data
            ]);
            $dispatch->dispatch($server, $msgData, $fd, $reactorId);
        });
        $tcpPort->on($register::onConnect, function (\Swoole\Server $server, int $fd, int $reactorId) use ($dispatch) {
            $data = json_encode([
                "controller" => 'Index',
                "action" => 'onConnect'
            ]);
            $dispatch->dispatch($server, $data, $fd, $reactorId);
        });
        $tcpPort->on($register::onClose, function (\Swoole\Server $server, int $fd, int $reactorId) use ($dispatch) {
            $data = json_encode([
                "controller" => 'Index',
                "action" => 'onClose'
            ]);
            $dispatch->dispatch($server, $data, $fd, $reactorId);
        });

    }

    /**
     * 注册Crontab
     * @return void
     */
    protected function registerCrontab()
    {
        $envMode = \EasySwoole\EasySwoole\Core::getInstance()->runMode();
        if (
            // 运行环境
            is_array($this->crontabRunEnv) && in_array($envMode, $this->crontabRunEnv)
            // 运行类
            && $this->crontabClass && class_exists($this->crontabClass)
        ) {
            $Crontab = \EasySwoole\EasySwoole\Crontab\Crontab::getInstance();
            $Crontab->addTask($this->crontabClass);
        }
    }

    /**
     * 注册自定义进程
     * @return void
     */
    protected function registerConsumer()
    {
        $jobs = $this->consumerJobs;
        if (!is_array($jobs)) {
            return;
        }
        $group = config('SERVER_NAME') . '.my';
        foreach ($jobs as $value) {

            $proName = $group . '.' . $value['name'];

            $class = $value['class'];
            if (empty($class) || !class_exists($class)) {
                continue;
            }
            $psnum = intval($value['psnum'] ?? 1);
            $proCfg = [];
            if (isset($value['process_config']) && is_array($value['process_config'])) {
                $proCfg = $value['process_config'];
                unset($value['process_config']);
            }

            for ($i = 0; $i < $psnum; ++$i) {
                $cfg = array_merge([
                    'processName' => $proName . '.' . $i,
                    'processGroup' => $group,
                    'arg' => $value,
                    'enableCoroutine' => true,
                ], $proCfg);
                $processConfig = new \EasySwoole\Component\Process\Config($cfg);
                \EasySwoole\Component\Process\Manager::getInstance()->addProcess(new $class($processConfig));
            }
        }
    }

    protected function watchHotReload()
    {
        $watchConfig = $this->hotReloadWatchDirs;

        if (!is_env('dev') || !is_array($watchConfig) || empty($watchConfig)) {
            return;
        }

        $onChange = is_callable($this->hotReloadFunc['on_change'])
            ? $this->hotReloadFunc['on_change']
            : function (array $list, \EasySwoole\FileWatcher\WatchRule $rule) {
                echo PHP_EOL . PHP_EOL . Color::warning(' Worker进程重启，检测到以下文件变更: ') . PHP_EOL;

                foreach ($list as $item) {
                    $scanType = is_file($item) ? 'file' : (is_dir($item) ? 'dir' : '未知');
                    echo Utility::displayItem("[$scanType]", $item) . PHP_EOL;
                }
                $Server = ServerManager::getInstance()->getSwooleServer();

                // worker进程reload不会触发客户端的断线重连，但是原来的fd已经不可用了
                foreach ($Server->connections as $fd) {
                    // 不要在 close 之后写清理逻辑。应当放置到 onClose 回调中处理
                    $Server->close($fd);
                }

                if (is_callable($this->hotReloadFunc['reload_before'])) {
                    $this->hotReloadFunc['reload_before']($list, $rule);
                }

                $Server->reload();

                if (is_callable($this->hotReloadFunc['reload_after'])) {
                    $this->hotReloadFunc['reload_after']($list, $rule);
                }

                echo Color::success('Worker进程启动成功 ') . PHP_EOL;
                echo Color::red('请自行区分 Master 和 Worker 程序 !!!!!!!!!!') . PHP_EOL . PHP_EOL;
            };

        $onException = is_callable($this->hotReloadFunc['on_exception'])
            ? $this->hotReloadFunc['on_exception']
            : function (\Throwable $throwable) {

                echo PHP_EOL . Color::danger('Worker进程重启失败: ') . PHP_EOL;
                echo Utility::displayItem("[message]", $throwable->getMessage()) . PHP_EOL;
                echo Utility::displayItem("[file]", $throwable->getFile() . ', 第 ' . $throwable->getLine() . ' 行') . PHP_EOL;

                echo Color::warning('trace:') . PHP_EOL;
                if ($trace = $throwable->getTrace()) {
                    // 简单打印就行
                    var_dump($trace);
//                    foreach ($trace as $key => $item)
//                    {
//                        echo Utility::displayItem("$key-----------------------", $item) . PHP_EOL;
//                        foreach ($item as $ik => $iv)
//                        {
//                            echo Utility::displayItem("[$ik]", $iv) . PHP_EOL;
//                        }
//                        echo Utility::displayItem("$key-----------------------", $item) . PHP_EOL;
//                    }
                }
            };

        $watcher = new \EasySwoole\FileWatcher\FileWatcher();
        // 设置监控规则和监控目录
        foreach ($watchConfig as $dir) {
            if (is_dir($dir)) {
                $watcher->addRule(new \EasySwoole\FileWatcher\WatchRule($dir));
            }
        }

        $watcher->setOnChange($onChange);
        $watcher->setOnException($onException);
        $watcher->attachServer(ServerManager::getInstance()->getSwooleServer());
    }

    protected function registerNotify()
    {
        $config = $this->notifyConfig;
        if (!is_array($config)) {
            return;
        }
        foreach ($config as $name => $cfg) {
            if ($cfg instanceof ConfigInterface) {
                EsNotify::getInstance()->register($cfg, $name);
            } else {
                trace("EsNotify 注册失败: $name");
            }
        }
    }

    protected function registerRpc()
    {
        $config = $this->rpcConfig;
        if (!is_array($config)) {
            return;
        }
        ###### 注册 rpc 服务 ######
        /** rpc 服务端配置 */
        $rpcConfig = new \EasySwoole\Rpc\Config();
        $rpcConfig->setOnException(function (\Throwable $throwable) {
            trace("PRC 失败 :" . $throwable->getMessage(), 'error');
        });
        $serverConfig = $rpcConfig->getServer();
        // 单机部署内部调用时可指定为 127.0.0.1
        // 分布式部署时多台调用时请填 0.0.0.0
        $serverConfig->setServerIp($config['service_ip'] ?? '127.0.0.1');
        $serverConfig->setListenPort($config['listen_port'] ?? 9600);
        $serverConfig->setListenAddress($config['listen_address'] ?? '0.0.0.0');
        // 其他配置待定
        $serviceNode = new \EasySwoole\Rpc\Server\ServiceNode();
        $serviceNode->setIp($config['host']);
        $serviceNode->setPort($config['port']);
        // rpc 具体配置请看配置章节
        $rpc = new \EasySwoole\Rpc\Rpc($rpcConfig);
        foreach ($config['service'] as $service) {
            // 添加服务到服务管理器中
            $serviceName = $service['name'] ?? '';
            $moduleName = $service['module'] ?? '';
            if ($serviceName && $moduleName) {
                // 创建 服务
                $serviceClass = new $serviceName;
                if ($serviceClass instanceof AbstractService) {
                    // 添加 Module 模块到服务中
                    $moduleClass = new $moduleName;
                    $serviceClass->addModule($moduleClass);
                    $rpc->serviceManager()->addService($serviceClass);
                }
            }
        }

        // 此刻的rpc实例需要保存下来 或者采用单例模式继承整个Rpc类进行注册 或者使用Di
        \EasySwoole\Component\Di::getInstance()->set(RpcUtils::RPC_KEY, $rpc);
        \EasySwoole\Component\Di::getInstance()->set(RpcUtils::RPC_NODE, $serviceNode);

        // 注册 rpc 服务
        $rpc->attachServer(ServerManager::getInstance()->getSwooleServer());
    }

}
