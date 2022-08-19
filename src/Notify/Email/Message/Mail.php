<?php

namespace Kyzone\EsUtility\Notify\Email\Message;

class Mail extends Base
{
    protected $title = '';

    protected $content = '';

    protected $email = '';

    protected $name = '';

    protected $otherMail = '';


    public function fullData()
    {
        return [$this->email, $this->name, $this->title, $this->content, $this->otherMail];
    }
}
