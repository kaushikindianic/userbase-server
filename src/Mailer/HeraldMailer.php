<?php

namespace UserBase\Server\Mailer;

use Herald\Client\Client as HeraldClient;
use Herald\Client\Message as HeraldMessage;

class HeraldMailer implements MailerInterface
{
    private $client;
    public function __construct(HeraldClient $client)
    {
        $this->client = $client;
    }
    
    public function sendTemplate($templateName, $user, array $data)
    {
        $message = new HeraldMessage();
        $message->setTemplate($templateName);
        foreach ($data as $key => $value) {
            $message->setData($key, $value);
        }
        $message->setToAddress($user->getEmail(), $user->getDisplayName());
        $this->client->send($message);
    }
}
