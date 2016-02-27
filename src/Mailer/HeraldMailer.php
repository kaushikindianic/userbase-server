<?php

namespace UserBase\Server\Mailer;

use Herald\Client\Client as HeraldClient;
use Herald\Client\Message as HeraldMessage;
use UserBase\Server\Model\Account;

class HeraldMailer implements MailerInterface
{
    private $client;
    public function __construct(HeraldClient $client)
    {
        $this->client = $client;
    }
    
    public function sendTemplate($templateName, Account $account, array $data)
    {
        $message = new HeraldMessage();
        $message->setTemplate($templateName);
        foreach ($data as $key => $value) {
            $message->setData($key, $value);
        }
        $message->setToAddress($account->getEmail(), $account->getDisplayName());
        $this->client->send($message);
    }
}
