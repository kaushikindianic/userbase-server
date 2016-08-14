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
    
    public function sendTemplate($templateName, $recipient, array $data)
    {
        if (is_a($recipient, 'UserBase\Server\Model\Account')) {
            $email = $account->getEmail();
            $displayName = $account->getDisplayName();
        }
        
        if (is_array($recipient)) {
            $email = $recipient['email'];
            $displayName = $recipient['display_name'];
        }
        
        if (!$this->client->templateExists($templateName)) {
            return;
        }

        $message = new HeraldMessage();
        $message->setTemplate($templateName);
        foreach ($data as $key => $value) {
            $message->setData($key, $value);
        }
        $message->setToAddress($email, $displayName);
        $this->client->send($message);
    }
}
