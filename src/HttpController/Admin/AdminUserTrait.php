<?php

namespace Kyzone\EsUtility\HttpController\Admin;

use Kyzone\EsUtility\Common\Classes\Tree;
use Kyzone\EsUtility\Common\Exception\HttpParamException;

/**
 * @property \App\Model\AdminUser $Model
 */
trait AdminUserTrait
{

    protected function __search()
    {
        $where = [];
        if (!empty($this->get['rid'])) {
            $where['rid'] = $this->get['rid'];
        }
        foreach (['username', 'realname'] as $val) {
            if (!empty($this->get[$val])) {
                $where[$val] = ["%{$this->get[$val]}%", 'like'];
            }
        }
        return $where;
    }

    protected function __after_index($items, $total)
    {

        foreach ($items as &$value) {
            unset($value['password']);
            $value->relation;
        }
        return parent::__after_index($items, $total);
    }

    public function getUserInfo()
    {
        $avatar = $this->operinfo['avatar'] ?? '';
        // 客户端进入页,应存id
        if (!empty($this->operinfo['extension']['homePath']))
        {
            $Tree = new Tree();
            $homePage = $Tree->originData(['type' => [[0, 1], 'in']])->getHomePath($this->operinfo['extension']['homePath']);
        }
        $result = [
            'id' => $this->operinfo['id'],
            'username' => $this->operinfo['username'],
            'realname' => $this->operinfo['realname'],
            'avatar' => $avatar,
            'desc' => $this->operinfo['desc'] ?? '',
            'homePath' => $homePage ?? '',
            'roles' => [
                [
                    'roleName' => $this->operinfo['role']['name'] ?? '',
                    'value' => $this->operinfo['role']['value'] ?? ''
                ]
            ],
            "sysinfo" => sysinfo()
        ];

        $this->success($result);
    }

    public function edit()
    {
        if ($this->isHttpGet()) {
            $data = $this->_edit(true);
            unset($data['password'], $data['create_time']);
            $this->success($data);
        } else {
            // 留空，不修改密码
            if (empty($this->post['password'])) {
                unset($this->post['password']);
            }
            $this->_edit();
        }
    }
    /**
     * 用户权限码
     */
    public function _getPermCode($return = false)
    {
        /** @var \App\Model\Admin\AdminMenu $model */
        $model = model('admin_menu');
        $code = $model->permCode($this->operinfo['rid']);
        return $return ? $code : $this->success($code);
    }

    public function _modify($return = false)
    {
        $userInfo = $this->operinfo;

        if ($this->isHttpGet()) {
            // role的关联数据也可以不用理会，ORM会处理
            unset($userInfo['password'], $userInfo['role']);

            $data = ['result' => $userInfo];
            return $return ? $data : $this->success($data);
        } elseif ($this->isHttpPost()) {
            $id = $this->post['id'];
            if (empty($id) || $userInfo['id'] != $id) {
                // 仅允许管理员编辑自己的信息
                throw new HttpParamException("id错误");
            }

            if ($this->post['__password'] && !password_verify($this->post['__password'], $userInfo['password'])) {
                throw new HttpParamException("旧密码不正确");
            }

            if (empty($this->post['__password']) || empty($this->post['password'])) {
                unset($this->post['password']);
            }

            return $this->_edit($return);
        }
    }

    public function _getToken($return = false)
    {
        // 此接口比较重要，只允许超级管理员调用
        if (!$this->isSuper()) {
            throw new HttpParamException("对不起，没有权限");
        }
        if (!isset($this->get['id'])) {
            throw new HttpParamException("id为空");
        }
        $id = $this->get['id'];
        $isExtsis = $this->Model->where(['id' => $id, 'status' => 1])->count();
        if (!$isExtsis) {
            throw new HttpParamException("id为空");
        }
        $token = get_login_token($id, 3600);
        return $return ? $token : $this->success($token);
    }
}
