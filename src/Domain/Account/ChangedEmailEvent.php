<?php

namespace UserBase\Server\Domain\Account;

use UserBase\Server\Domain\BaseEvent;

class ChangedEmailEvent extends BaseEvent
{
    protected $accountName;
    protected $email;
}
