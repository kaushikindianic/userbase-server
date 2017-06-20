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
        // Update 'account_name' for accepted invites
        $inviteRepo = $app->getInviteRepository();
        $userRepo = $app->getUserRepository();
        $invites = $inviteRepo->findAll();
        $lastEmail = null;

        foreach ($invites as $invite) {
            $email = $invite['email'];
            echo $email;

            if ($lastEmail==$email) {
                echo "- Deleting double for $email\n";
                $inviteRepo->registerAttempt($email, $invite['created_at']); // register it as an attempt
                $inviteRepo->remove($invite['id']);
            } else {
                if (!$invite['attempts']) {
                    // register first attempt
                    $inviteRepo->registerAttempt($email, $invite['created_at']);
                }

                if (!$invite['account_name']) {
                    $user = $userRepo->getByName($email);
                    if ($user) {
                        echo " linking to @" . $user->getName();
                        $inviteRepo->accept($invite['id'], $user->getName());
                    } else {
                        echo " not linkable :(";
                    }
                    echo "<br />\n";
                }
            }
            $lastEmail = $invite['email'];
        }

        $i = 0;
        $invites = $inviteRepo->findAll();
        // update inviter_org when missing (resolve from payload)
        foreach ($invites as $invite) {
            if (!$invite['inviter_org']) {
                echo "Resolving inviter_org for " . $invite['email'] . "\n";
                $json = $invite['payload'];
                $data = json_decode($json, true);
                //print_r($data);
                if (isset($data['properties']['organization_id'])) {
                    $invite['inviter_org'] = $data['properties']['organization_id'];
                }
            }
            if (!$invite['status']) {
                if ($invite['account_name']!='') {
                    $invite['status'] = 'ACCEPTED';
                } else {
                    $invite['status'] = 'NEW';
                }
            }
            $inviteRepo->updateFromArray($invite);
        }
        exit();
    }
}
