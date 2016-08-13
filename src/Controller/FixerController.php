<?php
namespace UserBase\Server\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\FormError;
use Symfony\Component\Validator\Constraints as Assert;
use UserBase\Server\Model\AccountEmail;
use Exception;

class FixerController
{
    public function emailsAction(Application $app, Request $request)
    {
        $accountRepo = $app->getAccountRepository();
        $accountEmailRepo = $app->getAccountEmailRepository();
        $accounts = $accountRepo->getAll();
        foreach ($accounts as $account) {
            if ($account->getAccountType()=='user') {
                $email = $account->getEmail();
                
                $ae = new AccountEmail();
                $ae->setAccountName($account->getName());
                $ae->setEmail($account->getEmail());
                $ae->setVerifiedAt($account->getEmailVerifiedAt());
                $accountEmailRepo->add($ae);
            }
            
            print_r($account);
        }
        exit();
    }
}
