<?php


namespace Kyzone\EsUtility\HttpController\Admin;


use Kyzone\EsUtility\Common\Exception\HttpParamException;
use Kyzone\EsUtility\Common\Http\Code;
use Kyzone\EsUtility\Common\Languages\Dictionary;

/**
 * Class Menu
 * @property \App\Model\AdminMenu $Model
 * @package App\HttpController\Admin
 */
trait AdminMenuTrait
{
    public function index()
    {
        $input = $this->get;

        $where = [];
        if (!empty($input['title'])) {
            $where['title'] = ["%{$input['title']}%", 'like'];
        }
        if (isset($input['status']) && $input['status'] !== '') {
            $where['status'] = $input['status'];
        }

        $result = $this->Model->getTree($where);
        $this->success($result);
    }

    public function _add($return = false)
    {
        // 如果name不为空，检查唯一性
        $name = $this->post['name'] ?? '';
        if (!empty($name)) {
            $model = $this->Model->_clone();
            if ($model->where('name', $name)->count()) {
                return $this->error("name 重复");
            }
        }
        return parent::_add($return);
    }

    /**
     * Client vue-router
     */
    public function _getMenuList($return = false)
    {
        $userMenus = $this->getUserMenus();
        if (!is_null($userMenus) && empty($userMenus)) {
            throw new HttpParamException("对不起，没有权限");
        }

        $where = ['type' => [[0, 1], 'in'], 'status' => 1];
        $options = ['isRouter' => true, 'filterIds' => $userMenus];
        $menu = $this->Model->getTree($where, $options);
        return $return ? $menu : $this->success($menu);
    }

    /**
     * 所有菜单树形结构
     * @return void
     */
    public function _treeList($return = false)
    {
        $treeData = $this->Model->getTree();
        return $return ? $treeData : $this->success($treeData);
    }

    public function del()
    {

        $this->Model->startTrans();
        try {
            $id = $this->post['id'];
            $opt = $this->post['opt'];

            $model = $this->Model->where('id', $id)->get();
            if (!$model) {
                return $this->error('删除失败');
            }

            // 删除子元素
            if ($opt === 'del' && !empty($this->post['chilrenids'])) {
                $chilrenids = explode(',', $this->post['chilrenids']);
                $this->Model->_clone()->destroy($chilrenids);
            } // 转移子元素到另一菜单下
            else if ($opt === 'change' && !empty($this->post['changeid'])) {
                $this->Model->_clone()->update(['pid' => $this->post['changeid']], ['pid' => $id]);
            }

            $model->destroy();
            $this->Model->commit();

            $this->success();

        } catch (\Exception $e) {
            $this->Model->rollback();
            throw $e;
        }
    }
}
