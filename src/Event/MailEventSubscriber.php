<?php

namespace UserBase\Server\Event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use UserBase\Server\Domain;

class MailEventSubscriber implements EventSubscriberInterface
{
    protected $app;
    
    public function __construct($app)
    {
        $this->app = $app;
    }
    
    public static function getSubscribedEvents()
    {
        return array(
            Domain\Account\SignedUpEvent::class => 'onSignedUpEvent',
        );
    }
    
    public function onSignedUpEvent(Domain\Account\SignedUpEvent $event)
    {
        $this->app->sendMail(
            'welcome',
            $event->getAccountName()
        );
    }
    
    public function onVerifiedEvent(Domain\Account\VerifiedEvent $event)
    {
        $this->app->sendMail(
            'verified',
            $event->getAccountName()
        );
    }
}
