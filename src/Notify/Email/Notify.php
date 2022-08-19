<?php

namespace Kyzone\EsUtility\Notify\Email;

use EasySwoole\Smtp\Mailer;
use Kyzone\EsUtility\Notify\Interfaces\ConfigInterface;
use Kyzone\EsUtility\Notify\Interfaces\MessageInterface;
use Kyzone\EsUtility\Notify\Interfaces\NotifyInterface;

class Notify implements NotifyInterface
{
    /** @var Config */
    protected $Config = null;

    protected Mailer $mail;

    public function __construct(ConfigInterface $Config)
    {

        $this->Config = $Config;
        $this->mail = new Mailer(true);
        $this->mail->setTimeout(15);
        $this->mail->setMaxPackage(1024 * 1024 * 2);
        $this->mail->setHost($this->Config->getMailHost());
        $this->mail->setPort($this->Config->getMailPort());
        $this->mail->setSsl(true);
        $this->mail->setUsername($this->Config->getMailUser());
        $this->mail->setPassword($this->Config->getMailPass());
        $this->mail->setFrom($this->Config->getMailUser(), $this->Config->getMailSender());

    }

    /**
     * 每个机器人每分钟最多发送20条消息到群里，如果超过20条，会限流10分钟
     * @param MessageInterface $message
     * @return void
     */
    public function does(MessageInterface $message)
    {
        list($email, $name, $title, $content, $otherMail) = $message->fullData();
        $text = new \EasySwoole\Smtp\Request\Html();
        $text->setSubject($title);
        $text->setBody($content);
        // 发送
        try {
            $this->mail->addAddress($email, $name);
            if ($otherMail) {
                $other_email_arr = explode(",", $otherMail); // 多个抄送人
                foreach ($other_email_arr as $v) {
                    $this->mail->addCc(trim($v));
                }
            }
            $this->mail->send($text);
        } catch (\EasySwoole\Smtp\Exception\Exception $exception) {
            \EasySwoole\EasySwoole\Logger::getInstance()->error($exception->getMessage());
        }
    }
}
