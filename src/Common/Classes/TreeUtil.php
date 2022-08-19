<?php

namespace Kyzone\EsUtility\Common\Classes;

class TreeUtil
{
    /**
     * 原始数组
     * @var array
     */
    protected $data = [];

    /**
     * 表示下级菜单的key
     * @var string
     */
    protected $childName = 'children';

    /**
     * id  字段名
     * @var number
     */
    protected $id = 'id';

    /**
     * pid 字段名
     * @var number
     */
    protected $pid = 'pid';

    /**
     * 返回的tree限制在固定的id范围内, null 不限制
     * @var array|null
     */
    protected $ids = null;

    public function __construct($ids = null, $child = '', $data = [])
    {
        if (!is_null($ids)) {
            $this->ids = is_string($ids) ? explode(',', $ids) : $ids;
        }
        $child && $this->childName = $child;
        $data && $this->data = $data;
    }

    /**
     * 获取树形数据
     * @return array
     */
    public function getTree($pid = 0): array
    {
        return $this->buildTree($pid);
    }

    /**
     * 多级菜单树
     * @param int $pid
     * @return array
     */
    protected function buildTree($pid)
    {
        $result = [];
        foreach ($this->data as $key => $value) {
            if ($value instanceof \EasySwoole\ORM\AbstractModel) {
                $value = $value->toArray();
            }
            if ($value[$this->pid] === $pid) {
                // 继续找儿子
                if ($children = $this->buildTree($value[$this->id])) {
                    $value[$this->childName] = $children;
                }

                // 儿子在id列表爸爸不在，把爸爸也算上, 适用于 treeSelect 当子节点未选满时不会返回父节点的场景
                if (is_null($this->ids) || (is_array($this->ids) && in_array($value[$this->id], $this->ids) || $children)) {
                    $result[] = $value;
                }
            }
        }

        return $result;
    }

    /**
     * @param number $id
     */
    public function setId(string $id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @param number $pid
     */
    public function setPid(string $pid)
    {
        $this->pid = $pid;
        return $this;
    }

    /**
     * @param string $childName
     */
    public function setChildName($childName): void
    {
        $this->childName = $childName;
    }
}
