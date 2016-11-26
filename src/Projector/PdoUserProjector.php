<?php

namespace UserBase\Server\Projector;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use UserBase\Server\Domain;

class PdoUserProjector extends BasePdoProjector implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return array(
            Domain\Account\CreatedEvent::class => 'onCreated',
        );
    }
    
    public function onCreated(Domain\Account\CreatedEvent $event)
    {
        $this->upsert(
            [
                'name' => $event->getAccountName()
            ],
            []
        );
    }
}
