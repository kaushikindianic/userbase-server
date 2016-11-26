<?php

namespace UserBase\Server\Event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use UserBase\Server\Domain;
use HipChat\HipChat;

class HipChatEventSubscriber implements EventSubscriberInterface
{
    protected $hipchat;
    protected $room;
    
    public function __construct($app)
    {
        if (!isset($app['parameters']['hipchat'])) {
            return;
        };
        $this->hipchat = new HipChat($app['parameters']['hipchat']['token']);
        $this->room = $app['parameters']['hipchat']['room'];
    }
    
    public static function getSubscribedEvents()
    {
        return array(
            Domain\AccountProperty\WasSetEvent::class => 'onAccountPropertyWasSet',
            Domain\AccountProperty\WasUnsetEvent::class => 'onAccountPropertyWasUnset',
            Domain\Account\SignedUpEvent::class => 'onSignedUp',
            Domain\Account\VerifiedEvent::class => 'onVerified'
        );
    }
    
    private function send($message)
    {
        if (!$this->hipchat) {
            return;
        }
        $this->hipchat->message_room(
            $this->room,
            'UserBase',
            $message,
            true
        );
    }
    
    public function onAccountPropertyWasSet(Domain\AccountProperty\WasSetEvent $event)
    {
        $this->send(
            'Account ' . $event->getAccountName() .
            ' property `' . $event->getPropertyName() . '` was set to `' . $event->getPropertyValue() . '`'
        );
    }
        
    public function onAccountPropertyWasUnset(Domain\AccountProperty\WasUnsetEvent $event)
    {
        $this->send(
            'Account ' . $event->getAccountName() .
            ' property `' . $event->getPropertyName() . '` was unset'
        );
    }
    
    public function onSignedUp(Domain\Account\SignedUpEvent $event)
    {
        $this->send(
            'Signup: ' . $event->getAccountName() .
            ' email `' . $event->getEmail() . '`'
        );
    }
    
    public function onVerified(Domain\Account\VerifiedEvent $event)
    {
        $this->send(
            'Verified: ' . $event->getAccountName()
        );
    }
}
