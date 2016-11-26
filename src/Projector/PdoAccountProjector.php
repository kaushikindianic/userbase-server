<?php

namespace UserBase\Server\Projector;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use UserBase\Server\Domain;

class PdoAccountProjector extends BasePdoProjector implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return array(
            Domain\Account\CreatedEvent::class => 'onCreated',
            Domain\Account\ChangedEmailEvent::class => 'onChangedEmail'
        );
    }
    
    public function onCreated(Domain\Account\CreatedEvent $event)
    {
        $this->upsert(
            [
                'name' => $event->getAccountName(),
                'account_type' => $event->getAccountType()
            ],
            [
                'status' => 'NEW',
                'created_at' => time()
            ]
        );
    }

    public function onChangedEmail(Domain\Account\ChangedEmailEvent $event)
    {
        $this->update(
            [
                'name' => $event->getAccountName()
            ],
            [
                'email' => $event->getEmail()
            ]
        );
    }
}
