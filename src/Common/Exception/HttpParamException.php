<?php

namespace Kyzone\EsUtility\Common\Exception;

use Kyzone\EsUtility\Common\Http\Code;

class HttpParamException extends \Exception
{
	public function __construct($message = "", $code = Code::ERROR_OTHER, Throwable $previous = null)
	{
		parent::__construct($message, $code, $previous);
	}
}
