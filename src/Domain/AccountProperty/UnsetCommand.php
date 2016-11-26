<?php

namespace UserBase\Server\Domain\AccountProperty;

use UserBase\Server\Domain\BaseCommand;

class UnsetCommand extends BaseCommand
{
    protected $accountName;
    protected $name;
}
