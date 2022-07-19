<?php

namespace Kyzone\EsUtility\HttpController\Common;

use Kyzone\EsUtility\Library\Oss\OssDriver;

/**
 * @extends \App\HttpController\BaseControllerTrait
 */
trait UploadTrait
{

    public function _upload()
    {
        $request = $this->request();
        /** @var \EasySwoole\Http\Message\UploadFile $file */
        $file = $request->getUploadedFile('file');

        $oss = new OssDriver($file);
        $oss->upload();
        $result = $oss->getFileInfo();
        return $this->success($result['file_url']);
    }

    public function _uploadBig()
    {
        $request = $this->request();
        $params = $this->request()->getRequestParam();
        /** @var \EasySwoole\Http\Message\UploadFile $file */
        $file = $request->getUploadedFile('file');

        $oss = new OssDriver($file);
        $upload_id = $oss->uploadBig($params);
        if ($upload_id !== false) {
            return $this->response()->write($upload_id);
        }
        $result = $oss->getFileInfo();
        return $this->success($result['file_url']);
    }
}
