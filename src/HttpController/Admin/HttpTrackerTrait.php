<?php

namespace Kyzone\EsUtility\HttpController\Admin;

use EasySwoole\Mysqli\QueryBuilder;
use EasySwoole\ORM\AbstractModel;
use Kyzone\EsUtility\Common\Exception\HttpParamException;
use Kyzone\EsUtility\Common\Http\Code;

/**
 * @property \App\Model\HttpTracker $Model
 */
trait HttpTrackerTrait
{
    protected function instanceModel()
    {
        $this->Model = model($this->getStaticClassName());
        return true;
    }

    protected function __search()
	{
		if (empty($this->get['where'])) {
			// 默认最近14天
			$tomorrow = strtotime('tomorrow');
			$begintime = $tomorrow - (14 * 86400);
			$endtime = $tomorrow - 1;
			$this->Model->where('create_time', [$begintime, $endtime], 'BETWEEN');
		} else {
			$this->Model->where($this->get['where']);
		}
		return null;
	}

	// 单条复发
    public function _repeat($return = false)
	{
		$pointId = $this->post['pointId'];
		if (empty($pointId)) {
			throw new HttpParamException('PointId id empty.');
		}
		$row = $this->Model->where('point_id', $pointId)->get();
		if ( ! $row) {
			throw new HttpParamException('PointId id Error: ' . $pointId);
		}

		$response = $row->repeatOne();
        if ( ! $response) {
            throw new HttpParamException('Http Error! ');
        }

        $data = [
            'httpStatusCode' => $response->getStatusCode(),
            'data' => json_decode($response->getBody(), true)
        ];
        return $return ? $data : $this->success($data);
	}

	// 试运行，查询count
    public function _count($return = false)
	{
		$where = $this->post['where'];
		if (empty($where)) {
			throw new HttpParamException('ERROR is Empty');
		}
		try {
			$count = $this->Model->where($where)->count('point_id');
            $data = ['count' => $count];
			return $return ? $data : $this->success($data);
		} catch (\Exception | \Throwable $e) {
            if ($return) {
                throw $e;
            } else {
                $this->error($e->getMessage(), Code::ERROR_OTHER);
            }
		}
	}

	// 确定运行
    public function _run($return = false)
	{
		$where = $this->post['where'];
		if (empty($where)) {
			throw new HttpParamException('run ERROR is Empty');
		}
		try {
			$count = $this->Model->where($where)->count('point_id');
			if ($count <= 0) {
				throw new HttpParamException('COUNT行数为0');
			}
			$task = \EasySwoole\EasySwoole\Task\TaskManager::getInstance();
//            $status = $task->async(new \App\Task\HttpTracker([
//                'count' => $count,
//                'where' => $where
//            ]));
			$status = $task->async(function () use ($where) {
				trace('HttpTracker 开始 ');

				/** @var AbstractModel $model */
				$model = model('HttpTracker');
				$model->where($where)->chunk(function ($item) {
					$item->repeatOne();
				}, 300);
				trace('HttpTracker 结束 ');
			});
			if ($status > 0) {
                $data = ['count' => $count, 'task' => $status];
				return $return ? $data : $this->success($data);
			} else {
				throw new HttpParamException("投递异步任务失败: $status");
			}
		} catch (HttpParamException | \Exception | \Throwable $e) {
			if ($return) {
                throw $e;
            } else {
                $this->error($e->getMessage(), Code::ERROR_OTHER);
            }
		}
	}
}
