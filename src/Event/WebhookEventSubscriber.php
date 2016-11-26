<?php

namespace UserBase\Server\Event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use UserBase\Server\Domain;
use Ramsey\Uuid\Uuid;

class WebhookEventSubscriber implements EventSubscriberInterface
{
    protected $app;
    
    public function __construct($app)
    {
        $this->app = $app;
    }
    
    public static function getSubscribedEvents()
    {
        return array(
            Domain\Account\SignedUpEvent::class => 'onSignedUp',
            Domain\Account\VerifiedEvent::class => 'onVerified',
            Domain\Invite\AcceptedEvent::class => 'onInviteAccepted',
            
        );
    }
    
    public function onSignedUp(Domain\Account\SignedUpEvent $event)
    {
        if ($this->app['userbase.signup_webhook']) {
            $this->sendWebhook(
                $this->app['userbase.signup_webhook'],
                'user.create',
                $event->getAccountName()
            );
        }
    }
    
    public function onVerified(Domain\Account\VerifiedEvent $event)
    {
        if ($this->app['userbase.verified_webhook']) {
            $this->sendWebhook(
                $this->app['userbase.verified_webhook'],
                'user.verified',
                $event->getAccountName()
            );
        }
    }
    
    public function onInviteAccepted(Domain\Invite\AcceptedEvent $event)
    {
        $inviteRepo = $this->app->getInviteRepository();
        $invite = $inviteRepo->getById($event->getInviteId());
        $payload = json_decode($invite['payload'], true);
        $arguments = [];
        if ($payload && isset($payload['properties'])) {
            $arguments = $payload['properties'];
        }
        if ($this->app['userbase.accepted_webhook']) {
            $this->sendWebhook(
                $this->app['userbase.accepted_webhook'],
                'invite.accepted',
                $event->getAccountName(),
                $arguments
            );
        }
    }
    
    private function sendWebhook($url, $eventName, $accountName, $arguments = [])
    {
        $accountRepo = $this->app->getAccountRepository();
        $account = $accountRepo->getByName($accountName);
        $data = [];
        $data['event'] = $eventName;
        $data['event-id'] = Uuid::uuid4();
        $data['datetime'] = date('Y-m-d H:i:s');

        $data['account'] = [
            'name' => $account->getName(),
            'display_name' => $account->getDisplayName(),
            'email' => $account->getEmail(),
            'mobile' => $account->getMobile()
        ];
        $data['arguments'] = $arguments;
        
        $verify = __DIR__ . '/../../app/config/cacert.pem';
        if (!file_exists($verify)) {
            throw new RuntimeException('cacert.pem not found');
        }
        
        // variable substitution in url
        foreach ($arguments as $key => $value) {
            $url = str_replace('{{arguments.' . $key . '}}', $value, $url);
        }
        foreach ($data['account'] as $key => $value) {
            $url = str_replace('{{account.' . $key . '}}', $value, $url);
        }

        $headers = [
            'Content-Type' => 'application/json'
        ];

        $guzzle = new \GuzzleHttp\Client(
            [
                'headers' => $headers,
                'verify' => $verify
            ]
        );
        try {
            $response = $guzzle->request('POST', $url, ['json' => $data]);
        } catch (\Exception $e) {
            //TODO: Log this exception, but fail silently
            //print_r($e); exit();
            //$body = $response->getBody();
            //exit((string)$body "RESPONSE");
        }
    }
}
