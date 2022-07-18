<?php

namespace Kyzone\EsUtility\HttpController\Common;

use EasySwoole\Component\Timer;
use EasySwoole\Http\Exception\FileException;
use EasySwoole\ORM\AbstractModel;
use EasySwoole\ORM\Db\MysqliClient;
use EasySwoole\Policy\Policy;
use EasySwoole\Policy\PolicyNode;
use EasySwoole\Utility\MimeType;
use Kyzone\EsUtility\Common\Classes\CtxRequest;
use Kyzone\EsUtility\Common\Classes\DateUtils;
use Kyzone\EsUtility\Common\Classes\LamJwt;
use Kyzone\EsUtility\Common\Classes\XlsWriter;
use Kyzone\EsUtility\Common\Exception\HttpParamException;
use Kyzone\EsUtility\Common\Http\Code;

/**
 * @extends \App\HttpController\BaseController
 */
trait UploadTrait
{
    public function upload(){

    }
}
