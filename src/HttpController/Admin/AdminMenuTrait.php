<?php


namespace Kyzone\EsUtility\HttpController\Admin;


use Kyzone\EsUtility\Common\Classes\Tree;
use Kyzone\EsUtility\Common\Exception\HttpParamException;
use Kyzone\EsUtility\Common\Http\Code;

/**
 * Class Menu
 * @property \App\Model\Admin\Menu $Model
 * @package App\HttpController\Admin
 */
trait AdminMenuTrait
{
	public function index()
	{
		$input = $this->get;

		$where = [];
		if ( ! empty($input['title'])) {
			$where['title'] = ["%{$input['title']}%", 'like'];
		}
		if (isset($input['status']) && $input['status'] !== '') {
			$where['status'] = $input['status'];
		}

		$result = $this->Model->menuAll($where);
		$this->success($result);
	}

    public function _add($return = false)
	{
		// 如果name不为空，检查唯一性
		$name = $this->post['name'] ?? '';
		if ( ! empty($name)) {
			$model = $this->Model->_clone();
			if ($model->where('name', $name)->count()) {
				return $this->error("name重复",Code::ERROR_OTHER);
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
		if ( ! is_null($userMenus) && empty($userMenus)) {
			throw new HttpParamException("对不起，没有权限");
		}
		$menu = $this->Model->getRouter($userMenus);
		return $return ? $menu : $this->success($menu);
	}

    /**
     * 所有菜单树形结构
     * @return void
     */
    public function _treeList($return = false)
    {
        $Tree = new Tree();
        $treeData = $Tree->originData()->getTree();
        return $return ? $treeData : $this->success($treeData);
    }
}
