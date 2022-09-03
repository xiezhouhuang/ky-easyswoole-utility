<?php


namespace Kyzone\EsUtility\HttpController\Api;


use Kyzone\EsUtility\Common\Http\Code;

/**
 * @extends \App\HttpController\BaseController
 */
trait ApiAuthTrait
{
    protected string $sessionKey = "API/TOKEN:";

    protected array $_basicAction = [
        '/api/user/login',
        '/api/user/wxLogin',
        '/api/user/aliLogin',
        '/api/user/wechatLogin'
    ];
    protected array $_noRequiredAction = [];

    protected array $_loginUser = [
        "user_id" => 0,
        "token" => ''
    ];
    protected string $tokenKey = "ky-token";
    protected int $tokeTimeout = 86400 * 7;

    public function onRequest(?string $action): ?bool
    {
        $this->setAuthTraitProtected();
        $return = parent::onRequest($action);
        if (!$return) {
            return false;
        }
        $path = $this->request()->getUri()->getPath();
        // 非必须验证
        if (in_array($path, $this->_noRequiredAction)) {
            $token = $this->request()->getHeader($this->tokenKey);
            if ($token) {
                $user_id = defer_redis()->get($this->sessionKey . $token[0]);
                if ($user_id) {
                    $this->_loginUser['user_id'] = $user_id;
                    $this->_loginUser['token'] = $token[0];
                }
            }
        } // basic列表里的不需要验证
        else if (!in_array($path, $this->_basicAction)) {
            // 必须有token

            $token = $this->request()->getHeader($this->tokenKey);
            if (empty($token)) {
                $this->error("认证失败", Code::CODE_UNAUTHORIZED);
                return false;
            }
            // 获取token
            $user_id = defer_redis()->get($this->sessionKey . $token[0]);
            if (empty($user_id)) {
                $this->error("认证失败", Code::CODE_UNAUTHORIZED);
                return false;
            }
            $this->_loginUser['user_id'] = $user_id;
            $this->_loginUser['token'] = $token[0];

        }
        return true;
    }

    protected function setAuthTraitProtected()
    {
    }

    /**
     * 生成token
     * @throws \EasySwoole\Redis\Exception\RedisException
     */
    protected function createToken(int $user_id): string
    {
        $token = base64_encode(md5(md5($user_id . $this->sessionKey . time()) . time()));
        defer_redis()->set($this->sessionKey . $token, $user_id, $this->tokeTimeout);
        return $token;
    }
}