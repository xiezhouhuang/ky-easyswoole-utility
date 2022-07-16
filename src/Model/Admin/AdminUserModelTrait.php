<?php

namespace Kyzone\EsUtility\Model\Admin;

use EasySwoole\Mysqli\QueryBuilder;

trait AdminUserModelTrait
{
	/**
	 * 保存登录日志，新项目将log表统一命名规则，自己实现记录日志的操作
	 *   实现示例: $model->data($data)->save();
	 * @param $data
	 * @return mixed
	 */
	abstract public function signInLog($data = []);

	protected function setBaseTraitProtected()
	{
		$this->autoTimeStamp = true;
		$this->sort = ['sort' => 'asc', 'id' => 'asc'];
	}

	protected function setPasswordAttr($password = '', $alldata = [])
	{
		if ($password != '') {
			return password_hash($password, PASSWORD_DEFAULT);
		}
		return false;
	}
	/**
	 * 关联Role分组模型
	 * @return array|mixed|null
	 * @throws \Throwable
	 */
	public function relation()
	{
		return $this->hasOne(model('admin_role'), null, 'rid', 'id');
	}
}
