<?php

namespace UserBase\Server\Domain;

use Symfony\Component\EventDispatcher\Event;
use Boost\BoostTrait;
use Boost\Accessors\ProtectedGettersTrait;
use Boost\Constructor\ProtectedConstructorTrait;

abstract class BaseEvent extends Event
{
    use BoostTrait;
    use ProtectedGettersTrait;
    use ProtectedConstructorTrait;
}
