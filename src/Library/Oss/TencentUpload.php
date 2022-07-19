<?php

namespace Kyzone\EsUtility\Library\Oss;

use EasySwoole\Oss\Tencent\OssClient;

class TencentUpload extends UploadBasic
{
    private OssClient $cosClient;

    public function __construct($config, $file)
    {
        parent::__construct($config, $file);

        $region = $this->config['region'];
        $arr = explode(",", $region);
        $config = new \EasySwoole\Oss\Tencent\Config([
            'appId' => sizeof($arr) == 2 ? $arr[1] : "",
            'secretId' => $this->config['app_id'],
            'secretKey' => $this->config['app_secret'],
            'region' => $region,
            'bucket' => $this->config['bucket'],
        ]);
        //new客户端
        $this->cosClient = new OssClient($config);
    }

    public function upload()
    {
        $key = $this->fileInfo['file_name'];
        //上传
        $this->cosClient->upload($this->config['bucket'], $key, $this->file->getStream(), $options = ['PartSize' => 1024 + 1]);
        //获取文件内容
        $rt = $this->cosClient->getObject(['Bucket' => $this->config['bucket'], 'Key' => $key]);
        $this->fileInfo['file_url'] = $this->config['base_url'] . "/" . $this->fileInfo['file_name'];
    }

    public function uploadBig(string $md5, string $size, string $upload_id, int $chunk, int $chunks)
    {

        $key = "{$md5}{$size}.{$this->fileInfo['file_ext']}";
        $this->fileInfo['file_name'] = $key;
        if ($chunk == 0) {
            $init = $this->cosClient->createMultipartUpload(array(
                'Bucket' => $this->config['bucket'], //存储桶名称，由BucketName-Appid 组成，可以在COS控制台查看 https://console.cloud.tencent.com/cos5/bucket
                'Key' => $key,
            ));
            $upload_id = $init['UploadId'];

        }
        $this->cosClient->uploadPart(array(
            'Bucket' => $this->config['bucket'], //存储桶名称，由BucketName-Appid 组成，可以在COS控制台查看 https://console.cloud.tencent.com/cos5/bucket
            'Key' => $key,
            'Body' => $this->file->getStream(),
            'UploadId' => $upload_id, //UploadId 为对象分块上传的 ID，在分块上传初始化的返回参数里获得
            'PartNumber' => $chunk + 1, //PartNumber 为分块的序列号，COS 会根据携带序列号合并分块
        ));
        if ($chunk + 1 == $chunks) {
            $parts = [];
            $listParts = $this->cosClient->listParts(array(
                'Bucket' => $this->config['bucket'], //存储桶名称，由BucketName-Appid 组成，可以在COS控制台查看 https://console.cloud.tencent.com/cos5/bucket
                'Key' => $key,
                'UploadId' => $upload_id,
                'PartNumberMarker' => 1,
                'MaxParts' => 1000,
            ));
            print_r($listParts);
            foreach ($listParts['xmlData']['Part'] as $v) {
                $parts[] = [
                    'ETag' => $v['ETag'],
                    'PartNumber' => $v['PartNumber']
                ];
            }

            $this->cosClient->completeMultipartUpload(array(
                'Bucket' => $this->config['bucket'], //存储桶名称，由BucketName-Appid 组成，可以在COS控制台查看 https://console.cloud.tencent.com/cos5/bucket
                'Key' => $key,
                'UploadId' => $upload_id,
                'Parts' => $parts
            ));
            $this->fileInfo['file_url'] = $this->config['base_url'] . "/" . $this->fileInfo['file_name'];
            return false;
        }


        return $upload_id;

    }

    public function delete($fileName)
    {
        // TODO: Implement delete() method.
    }
}