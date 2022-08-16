<?php

namespace Kyzone\EsUtility\Notify\WeCom\Message;

class Text extends Base
{
    protected $content = '';

    public function fullData()
    {
        return [
            'msgtype' => 'text',
            'text' => [
                'content' => $this->content,
                "mentioned_mobile_list" => $this->isAtAll ? ['@all'] : $this->atMobiles,
                "mentioned_list" => $this->isAtAll ? ['@all'] : $this->atUserIds
            ]
        ];
    }
}
