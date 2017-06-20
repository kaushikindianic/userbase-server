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

    public function invitesAction(Application $app, Request $request)
    {
        $inviteRepo = $app->getInviteRepository();
        $userRepo = $app->getUserRepository();
        $invites = $inviteRepo->findAll();
        foreach ($invites as $invite) {
            if (!$invite['account_name']) {
                echo $invite['email'];
                $user = $userRepo->getByName($invite['email']);
                if ($user) {
                    echo " @" . $user->getName();
                    $inviteRepo->accept($invite['id'], $user->getName());
                } else {
                    echo " :(";
                }
                echo "<br />\n";
            }
        }
        exit();
    }

    public function inviterOrgAction(Application $app, Request $request)
    {
        $inviteRepo = $app->getInviteRepository();
        $invites = $inviteRepo->findAll();
        foreach ($invites as $invite) {
            if (!$invite['inviter_org']) {
                $json = $invite['payload'];
                $data = json_decode($json, true);
                //print_r($data);
                if (isset($data['properties']['organization_id'])) {
                    $invite['inviter_org'] = $data['properties']['organization_id'];
                    $inviteRepo->updateFromArray($invite);
                }
            }
        }
        exit('Done');
    }


}
