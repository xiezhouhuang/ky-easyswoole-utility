<?php

namespace Kyzone\EsUtility\Model\Admin;

use EasySwoole\ORM\AbstractModel;
use Kyzone\EsUtility\Common\Classes\Tree;

/**
 * @extends AbstractModel
 */
trait AdminMenuModelTrait
{
    protected function setBaseTraitProtected()
    {
        $this->sort = ['sort' => 'asc', 'id' => 'desc'];
    }

    protected function setRedirectAttr($data, $alldata)
    {
        return $data ? '/' . ltrim($data, '/') : '';
    }

    protected function setNameAttr($data, $alldata)
    {
        return ucfirst(ltrim($data, '/'));
    }

    // 如果是第一级路由，path必须以 / 开头
    protected function setPathAttr($data, $alldata)
    {
        $value = ltrim($data, '/');
        if (intval($alldata['pid']) === 0) {
            $value = '/' . $value;
        }
        return $value;
    }


    /**
     * 菜单树
     * @param $where
     * @param array $options
     * @return array
     */
    public function getTree($where = [], array $options = [])
    {
        if ($where) {
            $this->where($where);
        }
        $data = $this->setOrder()->all();
        $Tree = new Tree($options + ['data' => $data]);
        return $Tree->treeData();
    }

    public function getHomePage($id)
    {
        $data = $this->where(['type' => [[0, 1], 'in']])->setOrder()->all();
        $Tree = new Tree(['data' => $data, 'filterIds' => $id]);
        return $Tree->getHomePage();
    }

    /**
     * 角色组权限码
     * @param int $rid
     * @return array
     * @throws \EasySwoole\Mysqli\Exception\Exception
     * @throws \EasySwoole\ORM\Exception\Exception
     * @throws \Throwable
     */
    public function permCode($rid): array
    {
        $where = ['permission' => ['', '<>']];

        if (!is_super($rid)) {
            /** @var  \App\Model\Admin\AdminRole $Role */
            $Role = model('admin_role');
            $menuIds = $Role->where('id', $rid)->val('menu');
            if (empty($menuIds)) {
                return [];
            }

            $where['id'] = [explode(',', $menuIds), 'in'];
        }
        $permission = $this->where($where)->column('permission');
        return is_array($permission) ? $permission : [];
    }
}
