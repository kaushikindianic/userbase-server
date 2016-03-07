<?php

namespace UserBase\Server\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use RuntimeException;
use UserBase\Server\Model\AccountProperty;

class ApiController
{
    private $baseUrl;


    public function indexAction(Application $app)
    {
        $data = array(
            'application' => 'UserBase',
            'version' => '0.1',
        );

        return new JsonResponse($data);
    }

    private function user2array(Application $app, $user, $details = false)
    {
        if (!isset($app['userbase.partition'])) {
            throw new RuntimeException("userbase.partition undefined");
        }
        $partition = strtolower($app['userbase.partition']);
        $data = array();
        $rolesData = array();
        $data['href'] = $this->baseUrl . '/api/v1/users/' . $user->getUsername();
        $data['username'] = $user->getUsername();
        if ($details) {
            $data['display_name'] = $user->getDisplayName();
            $data['alias'] = $user->getAlias();
            $data['picture_url'] = $user->getPictureUrl();
            $data['email'] = $user->getEmail();
            $data['password'] = $user->getPassword();
            $data['created_at'] = $user->getCreatedAt();
            $data['lastseen_at'] = $user->getLastSeenAt();
            $data['deleted_at'] = $user->getDeletedAt();
            $data['passwordupdated_at'] = $user->getPasswordUpdatedAt();

            // GET USER ACCOUNTS //
            $oAccRepo = $app->getAccountRepository();
            $aAccounts = $oAccRepo->getByUserName($user->getUsername());

            $data['accounts'] = array();
            foreach ($aAccounts as $oAccount) {
                $accountData = array();
                $accountData['name'] = $oAccount->getName();
                $accountData['display_name'] = $oAccount->getDisplayName();
                $accountData['about'] = $oAccount->getAbout();
                $accountData['picture_url'] = $oAccount->getPictureUrl();
                $accountData['email'] = $oAccount->getEmail();
                $accountData['created_at'] = $oAccount->getCreatedAt();
                $accountData['deleted_at'] = $oAccount->getDeletedAt();
                $accountData['account_type'] = $oAccount->getAccountType();

                $statement = array(
                    'effect' => 'allow',
                    'action' => ['userbase:manage_account', 'userbase:use_account'],
                    'resource' => 'xrn:' . $partition . ':userbase:::account/' . strtolower($oAccount->getName()) . '',
                );
                $rolesData[] = $statement;


                /*
                $accountData['roles'][] = 'ROLE_ADMIN';
                $accountData['roles'][] = 'ROLE_USER';
                */


                $data['accounts'][] = $accountData;
            }
            $data['policies'] = $rolesData;

            // GET USER SPACES //
            /*
            $oSpaceRepo = $app->getSpaceRepository();
            $aSpaces = $oSpaceRepo->getSpacesByAccounts($data['accounts']);
            $data['spaces'] =  ($aSpaces)? $aSpaces : array() ;
            */
        }
        return $data;
    }


    private function account2array(Application $app, $account, $details = false)
    {
        if (!isset($app['userbase.partition'])) {
            throw new RuntimeException("userbase.partition undefined");
        }
        $accountRepo = $app->getAccountRepository();
        $userRepo = $app->getUserRepository();

        $partition = strtolower($app['userbase.partition']);
        $data = array();
        $rolesData = array();
        $data['href'] = $this->baseUrl . '/api/v1/accounts/' . $account->getName();
        $data['name'] = $account->getName();
        $data['type'] = $account->getAccountType();

        if ($details) {
            $data['display_name'] = $account->getDisplayName();
            $data['about'] = $account->getAbout();
            $data['picture_url'] = $account->getPictureUrl();
            $data['mobile'] = $account->getMobile();
            $data['mobile_verified'] = $account->isMobileVerified();
            $data['email'] = $account->getEmail();
            $data['email_verified'] = $account->isEmailVerified();
            $data['created_at'] = $account->getCreatedAt();
            $data['deleted_at'] = $account->getDeletedAt();

            // GET USER ACCOUNTS //
            $members = $accountRepo->getAccountMembers($account->getName());

            $data['members'] = array();
            foreach ($members as $member) {
                $memberData = array();
                $memberData['user_name'] = $member['user_name'];
                $memberData['is_owner'] = $member['is_owner'];

                $data['members'][] = $memberData;
            }
            $data['properties'] = array();
            $accountPropertyRepo = $app->getAccountPropertyRepository();
            $accountProperties = $accountPropertyRepo->getByAccountName($account->getName());
            foreach ($accountProperties as $accountProperty) {
                $propertyData = array();
                $propertyData['name'] = $accountProperty->getName();
                $propertyData['value'] = $accountProperty->getValue();
                $data['properties'][] = $propertyData;
            }
        }

        return $data;
    }

    public function userIndexAction(Application $app, Request $request)
    {
        $details = false;
        if ($request->query->has('details')) {
            $details = true;
        }

        $this->baseUrl = $app['userbase.baseurl'];
        $repo = $app->getUserRepository();
        $users = $repo->getAll();
        $data = array();
        $items = array();
        foreach ($users as $user) {
            $a = $this->user2array($app, $user, $details);
            $items[] = $a;
        }
        $data['items'] = $items;
        return new JsonResponse($data);
    }

    public function userViewAction(Application $app, $userName)
    {
        $this->baseUrl = $app['userbase.baseurl'];
        $repo = $app->getUserRepository();
        $user = $repo->getByName($userName);
        if (!$user) {
            return $this->getErrorResponse(404, "User not found");
        }
        $data = $this->user2array($app, $user, true);

        return new JsonResponse($data);
    }

    public function accountIndexAction(Application $app, Request $request)
    {
        $this->baseUrl = $app['userbase.baseurl'];
        $details = false;
        if ($request->query->has('details')) {
            $details = true;
        }

        $repo = $app->getAccountRepository();
        $accounts = $repo->getAll();
        $data = array();
        $items = array();
        foreach ($accounts as $account) {
            $a = $this->account2array($app, $account, $details);
            $items[] = $a;
        }
        $data['items'] = $items;
        return new JsonResponse($data);
    }

    public function accountViewAction(Application $app, $accountName)
    {
        $this->baseUrl = $app['userbase.baseurl'];
        $accountRepo = $app->getAccountRepository();
        $account = $accountRepo->getByName($accountName);
        if (!$account) {
            return $this->getErrorResponse(404, "Account not found");
        }
        $data = $this->account2array($app, $account, true);

        return new JsonResponse($data);
    }

    private function getErrorResponse($code, $message)
    {
        $data = array();
        $data['error'] = array();
        $data['error']['code'] = $code;
        $data['error']['message'] = $message;
        return new JsonResponse($data, $code);
    }

    public function propertyAction(Application $app, $accountName, $propertyName, $propertyValue)
    {
        $oPropertyRepo = $app->getAccountPropertyRepository();

        $property = new AccountProperty();
        $property->setAccountName($accountName);
        $property->setName($propertyName);
        $property->setValue($propertyValue);
        $oPropertyRepo->insertOrUpdate($property);

        $data = ['status' => 'ok'];
        return new JsonResponse($data);
    }
}
