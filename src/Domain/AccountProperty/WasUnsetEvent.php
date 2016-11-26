<?php

namespace UserBase\Server\Domain\AccountProperty;

use UserBase\Server\Domain\BaseEvent;

class WasUnsetEvent extends BaseEvent
{
    protected $accountName;
    protected $propertyName;
}
