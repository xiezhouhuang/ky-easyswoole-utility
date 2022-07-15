<?php

namespace Kyzone\EsUtility\HttpController\Admin;

use Kyzone\EsUtility\Common\Exception\HttpParamException;
use Kyzone\EsUtility\Common\Http\Code;

/**
 * @property \App\Model\Admin\Package $Model
 */
trait PackageTrait
{
	protected function __search()
	{
        $where = [];

		$filter = $this->filter();
		// 如果分配了包权限但没有分配该包所属的游戏权限，同样是看不到此包的
		foreach (['gameid', 'pkgbnd'] as $col) {
            empty($filter[$col]) or $where[$col] = [$filter[$col], 'IN'];
		}
        empty($this->get['name']) or $where['concat(name," ",pkgbnd)'] = ["%{$this->get['name']}%", 'like'];

        return $this->_search($where);
	}

    public function _add($return = false)
	{
		if ($this->isHttpPost()) {
            $pkgbnd = $this->post['pkgbnd'];
            $count = $this->Model->where('pkgbnd', $pkgbnd)->count();
            if ($count > 0) {
                throw new HttpParamException('pkgbnd已存在： ' . $pkgbnd);
            }
        }
		return parent::_add($return);
	}

    public function _saveKeyValue($return = false)
	{
		$kv = $this->formatKeyValue($this->post['kv'] ?? []);
		$model = $this->Model->where('id', $this->post['id'])->get();
		$extension = $model->getAttr('extension');

		// 由a.b.c 组装成 ['a']['b']['c']
		$name = "['" . str_replace('.', "']['", $this->post['name']) . "']";
		eval("\$extension$name = " . var_export($kv, true) . ';');

		$model->extension = $extension;
		$model->update();
		return $return ? $model->toArray() : $this->success();
	}


	// 检查pkgbnd是否已存在了
    public function _pkgbndExist($return = false)
	{
		$pkgbnd = $this->get['pkgbnd'];
		if (empty($pkgbnd)) {
			throw new HttpParamException('pkgbnd为空！');
		}
		$count = $this->Model->where('pkgbnd', $pkgbnd)->count();
        $data = ['count' => $count];
		return $return ? $data : $this->success($data);
	}

	protected function formatKeyValue($kv = [])
	{
		$data = [];
		foreach ($kv as $arr) {
			if (empty($arr['Key']) || empty($arr['Value'])) {
				continue;
			}
			$data[$arr['Key']] = $arr['Value'];
		}
		return $data;
	}

	protected function unformatKeyValue($kv = [])
	{
		$result = [];
		foreach ($kv as $key => $value) {
			$result[] = [
				'Key' => $key,
				'Value' => $value
			];
		}
		return $result;
	}

    public function _options($return = false)
    {
        // 除了extension外的所有字段
        $options = $this->Model->order('gameid', 'desc')->order('sort', 'asc')->field(['id', 'name', 'gameid', 'pkgbnd', 'os', 'sort'])->all();
        return $return ? $options : $this->success($options);
    }
}
