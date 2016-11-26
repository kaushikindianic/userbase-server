<?php

namespace UserBase\Server\Domain\AccountProperty;

use UserBase\Server\Domain\BaseCommand;

class SetCommand extends BaseCommand
{
    protected $accountName;
    protected $name;
    protected $value;
}
