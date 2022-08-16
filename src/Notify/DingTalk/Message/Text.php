<?php

namespace Kyzone\EsUtility\Notify\DingTalk\Message;

class Text extends Base
{
    protected $content = '';

    public function fullData()
    {
        return [
            'msgtype' => 'text',
            'text' => [
                'content' => $this->getAtText($this->content)
            ],
            'at' => [
                'atMobiles' => $this->atMobiles,
                'atUserIds' => $this->atUserIds,
                'isAtAll' => $this->isAtAll
            ]
        ];
    }
}
