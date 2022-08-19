<?php

namespace Kyzone\EsUtility\Notify\Email;

use Kyzone\EsUtility\Notify\Interfaces\ConfigInterface;
use Kyzone\EsUtility\Notify\Interfaces\NotifyInterface;
use EasySwoole\Spl\SplBean;

class Config extends SplBean implements ConfigInterface
{
    /**
     * MAIL HOST
     * @var string
     */
    protected $mailHost = '';
    /**
     * MAIL PROT
     * @var string
     */
    protected $mailPort = '';
    /**
     * MAIL USER
     * @var string
     */
    protected $mailUser = '';
    /**
     * MAIL PASS
     * @var string
     */
    protected $mailPass = '';
    /**
     * 发送者
     * @var string
     */
    protected $mailSender = '';


    /**
     * @return string
     */
    public function getMailPort(): string
    {
        return $this->mailPort;
    }

    /**
     * @param string $mailPort
     */
    public function setMailPort(string $mailPort): void
    {
        $this->mailPort = $mailPort;
    }

    /**
     * @return string
     */
    public function getMailUser(): string
    {
        return $this->mailUser;
    }

    /**
     * @param string $mailUser
     */
    public function setMailUser(string $mailUser): void
    {
        $this->mailUser = $mailUser;
    }

    /**
     * @return string
     */
    public function getMailPass(): string
    {
        return $this->mailPass;
    }

    /**
     * @param string $mailPass
     */
    public function setMailPass(string $mailPass): void
    {
        $this->mailPass = $mailPass;
    }

    /**
     * @return string
     */
    public function getMailSender(): string
    {
        return $this->mailSender;
    }

    /**
     * @param string $mailSender
     */
    public function setMailSender(string $mailSender): void
    {
        $this->mailSender = $mailSender;
    }


    /**
     * @return string
     */
    public function getMailHost(): string
    {
        return $this->mailHost;
    }

    /**
     * @param string $mailHost
     */
    public function setMailHost(string $mailHost): void
    {
        $this->mailHost = $mailHost;
    }


    public function getNotifyClass(): NotifyInterface
    {
        return new Notify($this);
    }
}
