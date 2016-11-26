<?php

namespace UserBase\Server\Event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use UserBase\Server\Domain;
use UserBase\Server\Model\Event;

class EventLogEventSubscriber implements EventSubscriberInterface
{
    protected $eventRepo;
    
    public function __construct($app)
    {
        $this->eventRepo = $app->getEventRepository();
    }
    
    public static function getSubscribedEvents()
    {
        return array(
            Domain\Account\SignedUpEvent::class => 'onSignedUp',
        );
    }
    
    public function onSignedUp(Domain\Account\SignedUpEvent $event)
    {
        $time = time();
        $sEventData = json_encode(
            array(
                'username' => $event->getAccountName(),
                'email' => $event->getEmail(),
                'time' => $time
            )
        );

        $e = new Event();
        $e->setName($event->getAccountName());
        $e->setEventName('user.create');
        $e->setOccuredAt($time);
        $e->setData($sEventData);
        $e->setAdminName('');

        $this->eventRepo->add($e);
    }
}
