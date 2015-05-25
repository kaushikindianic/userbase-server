<?php

namespace UserBase\Server\Mailer;

interface MailerInterface
{
    public function sendTemplate($templateName, $user, array $data);
}
