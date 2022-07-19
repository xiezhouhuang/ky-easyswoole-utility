<?php


namespace Kyzone\EsUtility\HttpController\Admin;

use Kyzone\EsUtility\Common\Classes\CtxRequest;
use Kyzone\EsUtility\Common\Exception\HttpParamException;

/**
 * @property \App\Model\AdminUser $Model
 */
trait PublicTrait
{
    protected function instanceModel()
    {
        $this->Model = model('admin_user');
        return true;
    }


    public function index()
    {
        return $this->_login();
    }

    public function _login($return = false)
    {
        $array = $this->post;
        if (!isset($array['username'])) {
            throw new HttpParamException("请输入用户名");
        }

        // 查询记录
        $data = $this->Model->where('username', $array['username'])->get();

        if (empty($data) || !password_verify($array['password'], $data['password'])) {
            throw new HttpParamException("用户名或密码错误");
        }

        $data = $data->toArray();

        // 被锁定
        if (empty($data['status']) && (!is_super($data['rid']))) {
            throw new HttpParamException("您的账户已被锁定，请联系管理员");
        }

        $request = CtxRequest::getInstance()->request;
        $this->Model->signInLog([
            'uid' => $data['id'],
            'name' => $data['realname'] ?: $data['username'],
            'ip' => ip($request),
        ]);

        $token = get_login_token($data['id']);
        $result = ['token' => $token];
        return $return ? $result : $this->success($result, "登录成功");
    }

    public function logout()
    {
        $this->success('success');
    }
}
