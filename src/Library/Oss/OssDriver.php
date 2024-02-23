<?php

namespace Kyzone\EsUtility\Library\Oss;

use Kyzone\EsUtility\Common\Exception\HttpParamException;

class OssDriver
{
    private UploadBasic $engine;    // 当前存储引擎类

    /**
     * 构造方法
     * OssDriver constructor.
     * @throws HttpParamException
     */
    public function __construct($file)
    {
        $config = sysinfo("UPLOAD_OSS", [
            "method" => "local",
            "base_url" => "",
        ]);
        // 实例化当前存储引擎
        # 上传的方式
        $method = isset($config['method']) ? $config['method'] : "local";
        switch ($method) {
            case "tencent":
                $config = sysinfo("TENCENT_OSS", [
                    "app_id" => "",
                    "secret_id" => "",
                    "secret_key" => "",
                    "base_url" => "",
                    "bucket" => "",
                    "region" => ""
                ]);
                $this->engine = new TencentUpload($config, $file);
                break;
            case "aliyun":
                $config = sysinfo("ALIYUN_OSS", [
                    "access_key_id" => "",
                    "access_key_secret" => "",
                    "base_url" => "",
                    "bucket" => "",
                    "endpoint" => ""
                ]);
                $this->engine = new AliyunUpload($config, $file);
                break;
            case "qiniu":
                $config = sysinfo("QINIU_OSS", [
                    "access" => "",
                    "secret" => "",
                    "base_url" => "",
                    "bucket" => ""
                ]);
                $this->engine = new QiniuUpload($config, $file);
                break;
            default:
                $this->engine = new LocalUpload($config, $file);
                break;
        }
    }

    /**
     * 执行文件上传
     * @throws HttpParamException
     */
    public function upload()
    {
        $this->engine->checkMediaType();
        return $this->engine->upload();
    }

    /**
     * 分片文件上传
     * @throws HttpParamException
     */
    public function uploadBig(array $request)
    {
        $md5 = $request['md5'] ?? "";
        $name = $request['name'] ?? "";
        $size = $request['size'] ?? "";
        $upload_id = $request['upload_id'] ?? "";
        $chunk = (int)($request['chunk'] ?? 0);
        $chunks = (int)($request['chunks'] ?? 0);
        $this->engine->checkMediaType($name);
        return $this->engine->uploadBig($md5, $size, $upload_id, $chunk, $chunks);
    }

    /**
     * 执行文件删除
     * @param $fileName
     * @return mixed
     */
    public function delete($fileName)
    {
        return $this->engine->delete($fileName);
    }


    /**
     * 返回文件信息
     * @return mixed
     */
    public function getFileInfo()
    {
        return $this->engine->getFileInfo();
    }
}
