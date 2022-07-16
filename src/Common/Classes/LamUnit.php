<?php
/**
 * 测试类
 *
 * @author 林坤源
 * @version 1.0.2 最后修改时间 2020年10月21日
 */

namespace Kyzone\EsUtility\Common\Classes;

use EasySwoole\Http\Request;
use EasySwoole\I18N\I18N;

class LamUnit
{
    /**
     * @param Request $request
     * @param array $array 要合并的数据
     * @param bool $merge 是否覆盖掉原参数的值
     * @param string|array $unset 要删除的量
     */
    static public function withParams(Request $request, $array = [], $merge = true, $unset = '')
    {
        $method = $request->getMethod();
        $params = $method == 'GET' ? $request->getQueryParams() : $request->getParsedBody();
        if (is_array($array)) {
            if ($merge) {
                $params = $array + $params;
            } else {
                $params += $array;
            }
        }

        if ($unset) {
            is_array($unset) or $unset = explode(',', $unset);
            foreach ($unset as $v) {
                unset($params[$v]);
            }
        }

        $method == 'GET' ? $request->withQueryParams($params) : $request->withParsedBody($params);
    }

    // 解密
    static public function decrypt(Request $request, $field = 'envkeydata')
    {
        $cipher = $request->getRequestParam($field);
        $envkeydata = LamOpenssl::getInstance()->decrypt($cipher);
        $array = json_decode($envkeydata, true);
        ($array && $envkeydata = $array) or parse_str($envkeydata, $envkeydata);

        $envkeydata = $envkeydata ?: [];
        // 下文环境中可以通过 $field 这个量的值来判断是否解密成功
        $envkeydata[$field] = (bool)$envkeydata;

        self::withParams($request, $envkeydata, true);

        return $envkeydata;
    }
}
