<?php

namespace UserBase\Server\Domain\Account;

use UserBase\Server\Domain\BaseCommand;

class SignupCommand extends BaseCommand
{
    protected $username;
    protected $password;
    protected $displayName;
    protected $email;
    protected $mobile;
}
