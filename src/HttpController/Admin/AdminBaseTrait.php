<?php

namespace Kyzone\EsUtility\HttpController\Admin;

use EasySwoole\ORM\AbstractModel;

/**
 * @extends BaseControllerTrait
 */
trait AdminBaseTrait
{
	/** @var AbstractModel $Model */
	protected $Model;

	/**
	 * 实例化模型类
	 *   1.为空字符串自动实例化admin相关模型
	 *   2.为null不实例化
	 *   4.不为空字符串，实例化指定模型
	 * @var string
	 */
	protected $modelName = '';

    protected function onRequest(?string $action): ?bool
	{
		return parent::onRequest($action) && $this->_initialize();
	}

	protected function _initialize()
	{
		// 设置组件属性
		$this->setBaseTraitProtected();
		// 实例化模型
		$this->instanceModel();
        return true;
	}

	protected function setBaseTraitProtected()
	{
	}

	protected function getAuthorization()
	{
		$tokenKey = config('TOKEN_KEY');
		if ( ! $this->request()->hasHeader($tokenKey)) {
			return false;
		}

		$authorization = $this->request()->getHeader($tokenKey);
		if (is_array($authorization)) {
			$authorization = current($authorization);
		}
		return $authorization;
	}

    /**
     * 如果GET有传tzn参数，自动注入连接并切时区
     * @return bool
     */
    protected function instanceModel()
    {
        if ( ! is_null($this->modelName)) {
            $className = ucfirst($this->getStaticClassName());

            if ($this->modelName === '') {
                $this->Model = model($className, []);
            } else {
                $this->Model = model($this->modelName, []);
            }
        }
        return true;
    }

	/**
	 * [1 => 'a', 2 => 'b', 4 => 'c']
	 * 这种数组传给前端会被识别为object
	 * 强转为typescript数组
	 * @param array $array
	 * @return array
	 */
	protected function toArray($array = [])
	{
		$result = [];
		foreach ($array as $value) {
			$result[] = $value;
		}
		return $result;
	}

	// 零值元素转为空字符
	protected function zeroToEmpty($array = [], $filterCols = [], $setCols = true, $toArray = true)
	{
		$defaultValue = '';
		$result = [];
		foreach ($array as $key => $value) {
			$row = [];
			foreach ($value as $k => $v) {
				if (in_array($k, $filterCols)) {
					$row[$k] = $v;
					continue;
				}
				$row[$k] = (((is_bool($setCols) && $setCols) || (is_array($setCols) && in_array($k, $setCols))) && $v == 0) ? $defaultValue : $v;
			}

			if ($toArray) {
				$result[] = $row;
			} else {
				$result[$key] = $row;
			}
		}
		return $result;
	}
}
