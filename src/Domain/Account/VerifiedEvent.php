<?php

namespace UserBase\Server\Domain\Account;

use UserBase\Server\Domain\BaseEvent;

class VerifiedEvent extends BaseEvent
{
    protected $accountName;
}
