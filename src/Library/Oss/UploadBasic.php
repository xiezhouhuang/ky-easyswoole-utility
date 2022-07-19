<?php

namespace Kyzone\EsUtility\Library\Oss;

use \EasySwoole\Http\Message\UploadFile;
use Kyzone\EsUtility\Common\Exception\HttpParamException;

/**
 * 存储引擎抽象类
 */
abstract class UploadBasic
{
    protected UploadFile $file;
    protected array $fileInfo = [
        'file_name' => '',    // 重命名
        'file_size' => 0,     // 文件大小
        'file_url' => '',    // 路径
        'file_rename' => '', // 真实名
        'file_ext' => '', // 后缀名
        'file_type' => '' // 类型

    ];
    private array $fileExtTypes = [
        'mp4',
        'mp3',
        'x-flv',
        'images',
        'jpeg',
        'png',
        'gif',
        'jpg',
        'pdf',
        'doc',
        'docx',
        'ppt',
        'txt',
        'xls',
        'xlsx'
    ];
    protected ?array $config = [
        "method" => null,
        "app_id" => "",
        "app_secret" => "",
        "base_url" => "",
        "bucket" => "",
        "endpoint" => "",
        "region" => "",
    ];
    /**
     * 构造函数
     * Server constructor.
     * @throws HttpParamException
     */
    protected function __construct($config, $file)
    {
        $this->config = $config;
        $this->file = $file;
        $this->_checkSize();
        $clientMediaType = $this->file->getClientMediaType();
        $this->fileInfo['file_size'] = $this->file->getSize();
        $this->fileInfo['file_mime_type'] = $this->file->getClientMediaType();
        $this->fileInfo['file_type'] = $clientMediaType;
    }

    /**
     * 文件上传
     * @return mixed
     */
    abstract protected function upload();

    /**
     * 文件上传
     * @return mixed
     */
    abstract protected function uploadBig(string $md5, string $size, string $upload_id, int $chunk, int $chunks);

    /**
     * 文件删除
     * @param $fileName
     * @return mixed
     */
    abstract protected function delete($fileName);

    /**
     * 返回文件信息
     * @return mixed
     */
    public function getFileInfo()
    {
        return $this->fileInfo;
    }

    /**
     * 检查文件大小
     * @throws HttpParamException
     */
    private function _checkSize(): void
    {
        $size = $this->file->getSize();
        if (empty($size)) {
            throw new HttpParamException("上传文件大小不合法");
        }
        //    根据不同文件的类型大小进行对比
        //    todo

    }

    /**
     * 检查文件类型
     * @throws HttpParamException
     */
    public function checkMediaType(string $fileName = '')
    {

        if ($fileName == '') {
            $fileName = $this->file->getClientFileName();
        }
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
        $this->fileInfo['file_ext'] = $extension;
        $this->fileInfo['file_rename'] = $fileName;
        $this->fileInfo['file_name'] = \EasySwoole\Utility\SnowFlake::make() . '.' . $extension;
        if (!in_array(strtolower($extension), $this->fileExtTypes)) {
            throw new HttpParamException("上传文件不合法");
        }
        return true;
    }


}