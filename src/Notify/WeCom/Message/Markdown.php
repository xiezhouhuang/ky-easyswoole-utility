<?php

namespace Kyzone\EsUtility\Notify\WeCom\Message;

class Markdown extends Base
{

    protected $content = '';

    public function fullData()
    {
        return [
            'msgtype' => 'markdown',
            'markdown' => [
                'content' => $this->content,
                "mentioned_mobile_list" => $this->isAtAll ? ['@all'] : $this->atMobiles,
                "mentioned_list" => $this->isAtAll ? ['@all'] : $this->atUserIds
            ],
        ];
    }
}
