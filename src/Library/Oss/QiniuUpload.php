<?php

namespace Kyzone\EsUtility\Library\Oss;

use EasySwoole\EasySwoole\Logger;
use EasySwoole\Oss\QiNiu\Auth;
use EasySwoole\Oss\QiNiu\Config;
use EasySwoole\Oss\QiNiu\Storage\UploadManager;
use EasySwoole\Oss\QiNiu\Zone;
use Kyzone\EsUtility\Common\Exception\HttpParamException;

class QiniuUpload extends UploadBasic
{

    public function __construct($config, $file)
    {
        parent::__construct($config, $file);
    }

    public function upload()
    {

        $bucket = $this->config['bucket'];
        $access = $this->config['app_id'];
        $secret = $this->config['app_secret'];
        $baseUrl = $this->config['base_url'];
        \EasySwoole\Oss\QiNiu\Config::setTimeout(30);
        \EasySwoole\Oss\QiNiu\Config::setConnectTimeout(30);

        $auth = new Auth($access, $secret);
        $key = $this->fileInfo['file_name'];
        $config = new Config();
        $config->zone = Zone::regionHuanan();

        $token = $auth->uploadToken($bucket);
        $upManager = new UploadManager($config);
        list($ret, $err) = $upManager->put($token, $key, $this->file->getStream());
        if ($err) {
            Logger::getInstance()->error(json_encode($err));
            throw  new HttpParamException("上传文件失败");
        }
        $this->fileInfo['file_url'] = $baseUrl . "/" . $this->fileInfo['file_name'];
    }

    public function delete($fileName)
    {
        // TODO: Implement delete() method.
    }

    public function uploadBig(string $md5, string $size, string $upload_id, int $chunk, int $chunks)
    {
        // TODO: Implement uploadBig() method.
    }
}