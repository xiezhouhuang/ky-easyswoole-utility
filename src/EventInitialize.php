<?php

namespace Kyzone\EsUtility;

use EasySwoole\Component\Di;
use EasySwoole\EasySwoole\Config;
use EasySwoole\EasySwoole\SysConst;
use EasySwoole\Http\Request;
use EasySwoole\Http\Response;
use EasySwoole\ORM\DbManager;
use EasySwoole\Spl\SplBean;
use EasySwoole\Trigger\TriggerInterface;
use Kyzone\EsUtility\Common\Classes\CtxRequest;
use Kyzone\EsUtility\Common\Classes\ExceptionTrigger;
use Kyzone\EsUtility\Common\Classes\LamUnit;
use Kyzone\EsUtility\HttpTracker\Index as HttpTracker;

class EventInitialize extends SplBean
{
    /**
     * @var TriggerInterface
     */
    protected $ExceptionTrigger = ExceptionTrigger::class;

    /**
     * @var string[]
     */
    protected $configDir = [EASYSWOOLE_ROOT . '/App/Common/Config'];

    /**
     * @var array
     */
    protected $mysqlConfig = null;

    protected $redisConfig = null;

    protected $mysqlOnQueryOpen = true;
    protected $mysqlOnQueryFunc = [
        '_before_func' => null, // 前置
        '_save_sql' => null, // 自定义保存
        '_after_func' => null, // 后置
    ];


    protected $httpOnRequestOpen = true;
    protected $httpOnRequestFunc = [
        '_before_func' => null, // 前置
        '_after_func' => null, // 后置
    ];

    protected $httpAfterRequestOpen = true;
    protected $httpAfterRequestFunc = [
        '_before_func' => null, // 前置
        '_after_func' => null, // 后置
    ];

    /**
     * 开启链路追踪，string-根节点名称, empty=false 不开启
     * @var null | string
     */
    protected $httpTracker = null;
    protected $httpTrackerConfig = [];

    /**
     * 设置属性默认值
     * @return void
     */
    protected function initialize(): void
    {
        if (is_null($this->mysqlConfig)) {
            $this->mysqlConfig = config('MYSQL');
        }
        if (is_null($this->redisConfig)) {
            $this->redisConfig = config('REDIS');
        }

    }

    public function run()
    {
        $this->registerConfig();
        $this->registerExceptionTrigger();
        $this->registerMysqlPool();
        $this->registerRedisPool();
        $this->registerMysqlOnQuery();
        $this->registerHttpOnRequest();
        $this->registerAfterRequest();
    }

    /**
     * 注册异常处理器
     * @return void
     */
    protected function registerExceptionTrigger()
    {
        if ($this->ExceptionTrigger && class_exists($this->ExceptionTrigger)) {
            $className = $this->ExceptionTrigger;
            $class = new $className();
            \EasySwoole\EasySwoole\Trigger::getInstance($class);
        }
    }

    /**
     * 加载项目配置
     * @return void
     */
    protected function registerConfig()
    {
        $dirs = $this->configDir;
        if ( ! is_array($dirs)) {
            return;
        }
        foreach ($dirs as $dir) {
            Config::getInstance()->loadDir($dir);
        }
    }

    /**
     * 注册MySQL连接池
     * @return void
     */
    protected function registerMysqlPool()
    {
        $config = $this->mysqlConfig;
        if ( ! is_array($config)) {
            return;
        }
        foreach ($config as $mname => $mvalue)
        {
            DbManager::getInstance()->addConnection(
                new \EasySwoole\ORM\Db\Connection(new \EasySwoole\ORM\Db\Config($mvalue)),
                $mname
            );
        }
    }

    /**
     * 注册Redis连接池
     * @return void
     * @throws \EasySwoole\RedisPool\Exception\Exception
     * @throws \EasySwoole\RedisPool\RedisPoolException
     */
    protected function registerRedisPool()
    {
        $config = $this->redisConfig;
        if ( ! is_array($config)) {
            return;
        }
        foreach ($config as $rname => $rvalue)
        {
            \EasySwoole\RedisPool\RedisPool::getInstance()->register(
                new \EasySwoole\Redis\Config\RedisConfig($rvalue),
                $rname
            );
        }
    }

    /**
     * 注册MySQL全局OnQuery回调
     * @return void
     */
    protected function registerMysqlOnQuery()
    {
        if ( ! $this->mysqlOnQueryOpen) {
            return;
        }
        DbManager::getInstance()->onQuery(
            function (\EasySwoole\ORM\Db\Result $result, \EasySwoole\Mysqli\QueryBuilder $builder, $start) {
                // 前置
                if (is_callable($this->mysqlOnQueryFunc['_before_func'])) {
                    // 返回false不继续运行
                    if ($this->mysqlOnQueryFunc['_before_func']($result, $builder, $start) === false) {
                        return;
                    }
                }
                $sql = $builder->getLastQuery();
                if (empty($sql)) {
                    return;
                }

                // 除非显示声明config save_log不记录日志
                if (! isset($this->mysqlConfig['save_log']) || $this->mysqlConfig['save_log'] !== false) {
                    trace($sql, 'info', 'sql');
                }

                if (is_callable($this->mysqlOnQueryFunc['_save_sql'])) {
                    $this->mysqlOnQueryFunc['_save_sql']($sql);
                }

                // 后置
                if (is_callable($this->mysqlOnQueryFunc['_after_func'])) {
                    $this->mysqlOnQueryFunc['_after_func']($result, $builder, $start);
                }
            }
        );
    }


    /**
     * 注册Http全局Request回调
     * @return void
     */
    protected function registerHttpOnRequest()
    {
        if ( ! $this->httpOnRequestOpen) {
            return;
        }
        Di::getInstance()->set(
            SysConst::HTTP_GLOBAL_ON_REQUEST,
            function (Request $request, Response $response) {
                // 前置
                if (is_callable($this->httpOnRequestFunc['_before_func'])) {
                    // 返回false终止本次Request
                    if ($this->httpOnRequestFunc['_before_func']($request, $response) === false) {
                        return false;
                    }
                }
                // 自定义协程单例Request
                CtxRequest::getInstance()->request = $request;


                if ( ! is_null($this->httpTracker)) {
                    $repeated = intval(stripos($request->getHeaderLine('user-agent'), ';HttpTracker') !== false);
                    // 开启链路追踪
                    $point = HttpTracker::getInstance($this->httpTrackerConfig)->createStart($this->httpTracker);
                    $point && $point->setStartArg(
                        HttpTracker::startArgsRequest($request, ['repeated' => $repeated])
                    );
                }

                // 后置
                if (is_callable($this->httpOnRequestFunc['_after_func'])) {
                    $return = $this->httpOnRequestFunc['_after_func']($request, $response);
                    // 如果返回bool，则直接使用
                    if (is_bool($return)) {
                        return $return;
                    }
                }
                return true;
            }
        );
    }

    protected function registerAfterRequest()
    {
        if ( ! ($this->httpAfterRequestOpen || ! is_null($this->httpTracker))) {
            return;
        }

        Di::getInstance()->set(
            SysConst::HTTP_GLOBAL_AFTER_REQUEST,
            function (Request $request, Response $response) {
                // 前置
                if (is_callable($this->httpAfterRequestFunc['_before_func'])) {
                    // 返回false结束运行
                    if ($this->httpAfterRequestFunc['_before_func']($request, $response) === false) {
                        return;
                    }
                }

                if ( ! is_null($this->httpTracker)) {
                    $point = HttpTracker::getInstance()->startPoint();
                    $point && $point->setEndArg(HttpTracker::endArgsResponse($response))->end();
                }

                // 后置
                if (is_callable($this->httpAfterRequestFunc['_after_func'])) {
                    $this->httpAfterRequestFunc['_after_func']($request, $response);
                }
            }
        );
    }
}
