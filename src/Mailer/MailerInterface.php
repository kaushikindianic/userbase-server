<?php

namespace UserBase\Server\Mailer;

use UserBase\Server\Model\Account;

interface MailerInterface
{
    public function sendTemplate($templateName, $recipient, array $data);
}
