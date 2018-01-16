<?php

namespace Webbhuset\SveaWebpay\Model\System\Message;

class Notice implements \Magento\Framework\Notification\MessageInterface
{
    public function getIdentity()
    {
        return 'svea_notice';
    }

    public function isDisplayed()
    {
        return true;
    }

    public function getText()
    {
        return 'Svea Ekonomi payment module has been updated. You might have to re-enter your credentials.';
    }

    public function getSeverity()
    {
        return self::SEVERITY_MAJOR;
    }
}