<?php

namespace UserBase\Server\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use RuntimeException;

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
                    'resource' => 'xrn:' . $partition . ':userbase::account/' . strtolower($oAccount->getName()) . '',
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
    
    public function userListAction(Application $app)
    {
        $this->baseUrl = $app['userbase.baseurl'];
        $repo = $app->getUserRepository();
        $users = $repo->getAll();
        $data = array();
        $items = array();
        foreach ($users as $user) {
            $a = $this->user2array($app, $user);
            $items[] = $a;
        }
        $data['items'] = $items;
        return new JsonResponse($data);
    }
    
    public function userViewAction(Application $app, $username)
    {
        $this->baseUrl = $app['userbase.baseurl'];
        $repo = $app->getUserRepository();
        $user = $repo->getByName($username);
        if (!$user) {
            return $this->getErrorResponse(404, "User not found");
        }
        $data = $this->user2array($app,$user, true);

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
}
