<?php

namespace Kyzone\EsUtility\Library\Oss;

use Kyzone\EsUtility\Common\Classes\CtxRequest;
use Kyzone\EsUtility\Common\Exception\HttpParamException;

class LocalUpload extends UploadBasic
{
    public function __construct($config, $file)
    {
        parent::__construct($config, $file);
    }

    public function upload()
    {

        $baseUrl = $this->config['base_url'];
        if ($baseUrl == "") {
            $uri = CtxRequest::getInstance()->request->getUri();
            $baseUrl = $uri->getScheme() . "://" . $uri->getAuthority() . "/ky-api";
        }
        $dirname = "/uploads/" . date("Y") . "/" . date("m");
        $dir = EASYSWOOLE_ROOT . "/public/" . $dirname;
        //    需要检查目录是否存在，否则是写不进去的
        if (!is_dir($dir)) {
            \EasySwoole\Utility\File:: createDirectory($dir);
        }
        $basename = "/" . $this->fileInfo['file_name'];
        $this->file->moveTo($dir . $basename);
        $this->fileInfo['file_url'] = $baseUrl . $dirname . $basename;

    }

    public function delete($fileName)
    {
        // TODO: Implement delete() method.
    }

    /**
     * @throws HttpParamException
     */
    public function uploadBig(string $md5, string $size, string $upload_id, int $chunk, int $chunks)
    {
        throw new HttpParamException("不支持该操作");
    }
}