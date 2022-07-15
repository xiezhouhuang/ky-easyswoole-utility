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
    public static function setI18n(Request $request, $headerKey = 'accept-language')
    {
        if ($request->hasHeader($headerKey)) {
            $langage = $request->getHeader($headerKey);
            if (is_array($langage)) {
                $langage = current($langage);
            }
            $languages = config('LANGUAGES') ?: [];
            foreach ($languages as $lang => $value) {
                // 回调
                if (is_callable($value['match'])) {
                    $match = $value['match']($langage);
                    if ($match === true) {
                        I18N::getInstance()->setLanguage($lang);
                        break;
                    }
                }
                // 正则
                elseif(is_string($value['match']) && preg_match($value['match'], $langage))
                {
                    I18N::getInstance()->setLanguage($lang);
                    break;
                }
            }
        }
    }

    // 将yapi中的通用参数标识符转换为具体的通用参数数组
    static public function utilityParam(Request $request, $key = '一堆通用参数！！')
    {
        // 获取IP
        $utility = ['ip' => ip($request)];

        if ($comval = $request->getRequestParam($key)) {
            $comval = json_decode($comval, true);
            $utility += [
                'gameid' => 0,
                'sdkver' => 'Utility-sdkver',
                'devid' => 'Utility-devid',
                'pkgbnd' => 'com.pkgbnd.Utility',
                'imei' => 'Utility-imei',
                'os' => 0,
                'osver' => '12',
                'exmodel' => 'Utility-Huawei P40',
                'creqtime' => time()
            ];

            is_array($comval) && $utility = array_merge($utility, $comval);
        }

        // 销售渠道
        if ( ! $request->getRequestParam('dtorid')) {
            $utility['dtorid'] = $request->getRequestParam('os') == 1 ? 3 : 4;
        }

        // 包序号（版本序号）
        if ( ! $request->getRequestParam('versioncode')) {
            $utility['versioncode'] = 1;
        }

        self::withParams($request, $utility, false, $key);
    }

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
