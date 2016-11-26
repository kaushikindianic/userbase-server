<?php

namespace UserBase\Server\Domain\Account;

use UserBase\Server\Domain\BaseEvent;

class CreatedEvent extends BaseEvent
{
    protected $accountName;
    protected $accountType;
}
