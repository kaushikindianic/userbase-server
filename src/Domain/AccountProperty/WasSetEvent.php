<?php

namespace UserBase\Server\Domain\AccountProperty;

use UserBase\Server\Domain\BaseEvent;

class WasSetEvent extends BaseEvent
{
    protected $accountName;
    protected $propertyName;
    protected $propertyValue;
}
