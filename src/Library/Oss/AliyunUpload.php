<?php

namespace Kyzone\EsUtility\Library\Oss;

use \EasySwoole\Oss\AliYun\OssClient;

class AliyunUpload extends UploadBasic
{
    public function __construct($config, $file)
    {
        parent::__construct($config, $file);
    }

    public function upload()
    {

        $bucket = $this->config['bucket'];
        $baseUrl = $this->config['base_url'];
        $accessKeyId = $this->config['accessKey_id'];
        $accessKeySecret = $this->config['access_key_secret'];
        $endpoint = $this->config['endpoint'];
        $config = new \EasySwoole\Oss\AliYun\Config([
            'accessKeyId' => $accessKeyId,
            'accessKeySecret' => $accessKeySecret,
            'endpoint' => $endpoint,
        ]);
        $client = new OssClient($config);
        $client->putObject($bucket, $this->fileInfo['file_name'], $this->file->getStream());
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