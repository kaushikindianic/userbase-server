<?php

namespace UserBase\Server\Domain\Account;

use UserBase\Server\Domain\BaseCommand;

class ChangeEmailCommand extends BaseCommand
{
    protected $accountName;
    protected $email;
}
