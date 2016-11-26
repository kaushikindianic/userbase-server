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
    /*
    public function __construct()
    {
        $i = 0;
        foreach (get_object_vars($this) as $key => $value) {
            $this->$key = func_get_arg($i);
            $i++;
        }
    }
    
    public function __call($name, $arguments)
    {
        if (substr($name, 0, 3) != 'get') {
            throw new RuntimeException("Undefined method: " . $name);
        }
        $var = lcfirst(substr($name, 3));
        if (!property_exists($this, $var)) {
            throw new RuntimeException("No such property: " . $var);
        }
        return $this->$var;
    }
    */
}
