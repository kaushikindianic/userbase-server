<?php

namespace UserBase\Server\Domain\Invite;

use UserBase\Server\Domain\BaseEvent;

class AcceptedEvent extends BaseEvent
{
    protected $inviteId;
    protected $accountName;
}
