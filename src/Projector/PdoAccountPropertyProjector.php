<?php

namespace UserBase\Server\Projector;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use UserBase\Server\Domain;

class PdoAccountPropertyProjector extends BasePdoProjector implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return array(
            Domain\AccountProperty\WasSetEvent::class => 'onAccountPropertyWasSet',
            Domain\AccountProperty\WasUnsetEvent::class => 'onAccountPropertyWasUnset',
        );
    }
    
    public function onAccountPropertyWasSet(Domain\AccountProperty\WasSetEvent $event)
    {
        $this->upsert(
            [
                'account_name' => $event->getAccountName(),
                'name' => $event->getPropertyName()
            ],
            [
                'value' => $event->getPropertyValue()
            ]
        );
    }
        
    public function onAccountPropertyWasUnset(Domain\AccountProperty\WasUnsetEvent $event)
    {
        $this->delete(
            [
                'account_name' => $event->getAccountName(),
                'name' => $event->getPropertyName()
            ]
        );
    }
}
