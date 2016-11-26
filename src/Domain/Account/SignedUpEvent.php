<?php

namespace UserBase\Server\Domain\Account;

use UserBase\Server\Domain\BaseEvent;

class SignedUpEvent extends BaseEvent
{
    protected $accountName;
    protected $email;
}
