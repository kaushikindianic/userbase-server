<?php

namespace UserBase\Server\Domain\Account;

use UserBase\Server\Domain\BaseCommand;

class CreateCommand extends BaseCommand
{
    protected $accountName;
    protected $accountType;
}
