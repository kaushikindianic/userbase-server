<?php

namespace UserBase\Server\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use RuntimeException;
use UserBase\Server\Model\AccountProperty;
use UserBase\Server\Model\Event;
use UserBase\Server\Model\Account;
use UserBase\Server\Model\AccountNotification;

class ApiController
{
    private $baseUrl;


    public function indexAction(Application $app)
    {
        $data = array(
            'application' => 'UserBase',
            'version' => '0.1',
        );

        return $this->getJsonResponse($data);
    }

    private function user2array(Application $app, $user, $details = false)
    {
        if (!isset($app['userbase.partition'])) {
            throw new RuntimeException("userbase.partition undefined");
        }
        $notificationRepo = $app->getAccountNotificationRepository();

        $partition = strtolower($app['userbase.partition']);
        $data = array();
        $rolesData = array();
        $data['href'] = $this->baseUrl . '/api/v1/users/' . $user->getUsername();
        $data['username'] = $user->getUsername();
        if ($details) {
            $data['display_name'] = $user->getDisplayName();
            //$data['alias'] = $user->getAlias();
            //$data['picture_url'] = $user->getPictureUrl();
            //$data['email'] = $user->getEmail();
            $data['password'] = $user->getPassword();
            $data['created_at'] = $user->getCreatedAt();
            $data['lastseen_at'] = $user->getLastSeenAt();
            $data['deleted_at'] = $user->getDeletedAt();
            $data['password_updated_at'] = $user->getPasswordUpdatedAt();

            // GET USER ACCOUNTS //
            $oAccRepo = $app->getAccountRepository();
            $aAccounts = $oAccRepo->getByUserName($user->getUsername());

            $account = $oAccRepo->getByName($user->getName());
            
            $data['accounts'] = array();
            foreach ($aAccounts as $account) {
                $accountData = $this->account2array($app, $account, true);

                $statement = array(
                    'effect' => 'allow',
                    'action' => ['userbase:manage_account', 'userbase:use_account'],
                    'resource' => 'xrn:' . $partition . ':userbase:::account/' . strtolower($account->getName()) . '',
                );
                $rolesData[] = $statement;
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
        $notificationRepo = $app->getAccountNotificationRepository();

        $partition = strtolower($app['userbase.partition']);
        $data = array();
        $rolesData = array();
        $data['href'] = $this->baseUrl . '/api/v1/accounts/' . $account->getName();
        $data['name'] = $account->getName();
        $data['type'] = $account->getAccountType();

        if ($details) {
            $data['display_name'] = $account->getDisplayName();
            $data['about'] = $account->getAbout();
            $data['url'] = $account->getUrl();
            $data['picture_url'] = $account->getPictureUrl();
            $data['mobile'] = $account->getMobile();
            $data['mobile_verified'] = $account->isMobileVerified();
            $data['email'] = $account->getEmail();
            $data['email_verified'] = $account->isEmailVerified();
            $data['created_at'] = $account->getCreatedAt();
            $data['deleted_at'] = $account->getDeletedAt();
            $data['status'] = $account->getStatus();
            $data['message'] = $account->getMessage();
            $data['expire_at'] = $account->getExpireAt();
            $data['approved_at'] = $account->getExpireAt();

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
            
            // NOTIFICATIONS //
            $notifications = $notificationRepo->findByAccountName($account->getName());
            $data['notifications'] = $this->notificationsToArray($notifications);
        }

        return $data;
    }
    
    public function notificationsToArray($notifications)
    {
        $data = array();
        foreach ($notifications as $notification) {
            $notificationData = array();
            $notificationData['key'] = $notification['xuid'];
            $notificationData['source_account_name'] = $notification['source_account_name'];
            $notificationData['type'] = $notification['notification_type'];
            $notificationData['subject'] = $notification['subject'];
            $notificationData['link'] = $notification['link'];
            $notificationData['body'] = $notification['body'];

            $data[] = $notificationData;
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
        
        return $this->getJsonResponse($data);
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

        return $this->getJsonResponse($data);
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
        return $this->getJsonResponse($data);
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

        return $this->getJsonResponse($data);
    }
    
    public function getJsonResponse($data)
    {
        $response = new JsonResponse($data);
        $response->setEncodingOptions(JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        return $response;
    }

    private function getErrorResponse($code, $message)
    {
        $data = array();
        $data['error'] = array();
        $data['error']['code'] = $code;
        $data['error']['message'] = $message;
        return $this->getJsonResponse($data);
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
        return $this->getJsonResponse($data);
    }

    /**
    * user assing to account
    */
    public function userAssignAccountAction(Application $app, $accountName, $userName, $isAdmin)
    {
        $oAccRepo = $app->getAccountRepository();

        if (!$oAccount = $oAccRepo->getByName($accountName)) {
            return $this->getErrorResponse(404, "Account not found");
        }
        if (in_array($oAccount->getAccountType(), ['organization', 'group'])) {
            $isOwner =  (strtolower($isAdmin) == 'true')? 1 : 0;
            $oAccRepo->addAccUser($accountName, $userName, $isOwner);
            $data = ['status' => 'ok'];
            return $this->getJsonResponse($data);
        }
        return $this->getErrorResponse(500, "Account is not organization OR group");
    }

    /**
    * @ User Remove form account
    */
    public function userRemoveAccountAction(Application $app, $accountName, $userName)
    {
        $oAccRepo = $app->getAccountRepository();

        if (!$oAccount = $oAccRepo->getByName($accountName)) {
            return $this->getErrorResponse(404, "Account not found");
        }
        if (in_array($oAccount->getAccountType(), ['organization', 'group'])) {
            $oAccRepo->delAccUsers($accountName, $userName);
            $data = ['status' => 'ok'];
            return $this->getJsonResponse($data);
        }
        return $this->getErrorResponse(500, "Account is not organization OR group");
    }

    public function addEventAction(Application $app, Request $request, $accountName, $eventName)
    {
        $sEventData = json_encode($request->query->all());

        $oEvent = new Event();
        $oEvent->setName($accountName);
        $oEvent->setEventName($eventName);
        $oEvent->setOccuredAt(time());
        $oEvent->setData($sEventData);
        $oEvent->setAdminName($request->getUser());

        $oEventRepo = $app->getEventRepository();
        $oEventRepo->add($oEvent);

        $data = ['status' => 'ok'];
        return $this->getJsonResponse($data);
    }

    public function accountAddAction(Application $app, Request $request)
    {
        $accountName = urldecode($request->get('accountName'));
        $accountType =  urldecode($request->get('accountType'));
        $oAccountRepo = $app->getAccountRepository();

        //-- CHECK ACCOUNTNAME BLCKLIST--//
        $oBlacklistRepo = $app->getBlacklistRepository();
        if ($status = $oBlacklistRepo->checkNameExist($accountName)) {
            return $this->getErrorResponse(500, 'error.invalid_accountname_word');
        }
        $oAccountModel = new Account($accountName);
        $oAccountModel->setAccountType($accountType)
                    ->setStatus('new');

        if (!$oAccountRepo->add($oAccountModel)) {
            return $this->getErrorResponse(500, 'Account name already exist');
        }
        $data = ['status' => 'ok'];
        return $this->getJsonResponse($data);
    }

    public function accountEditAction(Application $app, Request $request)
    {
        $accountName =  urldecode($request->get('accountName'));
        $displayName =  urldecode($request->get('displayName'));
        $email =  urldecode($request->get('email'));
        $mobile =  urldecode($request->get('mobile'));
        $about =  urldecode($request->get('about'));
        $oAccountRepo = $app->getAccountRepository();

        if (!$email || !$mobile || !$about) {
            return $this->getErrorResponse(500, 'email, mobile and about value required.');
        }
        if (!$oAccountRepo->getByName($accountName)) {
            return $this->getErrorResponse(500, 'Account name does not exist.');
        }
        $oAccountModel = new Account($accountName);
        $oAccountModel->setEmail($email)
            ->setMobile($mobile)
            ->setAbout($about);

        if ($displayName) {
            $oAccountModel->setDisplayName($displayName);
        }
        $oAccountRepo->update($oAccountModel);

        $data = ['status' => 'ok'];
        return $this->getJsonResponse($data);
    }

    public function addNotificationAction(Application $app, Request $request)
    {
        $accountName =  urldecode($request->get('accountName'));
        $jsonData = file_get_contents('php://input');
        $aData = [];
        if ($jsonData) {
            $aData =  json_decode($jsonData, true);
        } else {
            return $this->getErrorResponse(500, 'Provide all data');
        }
        $oAccountNotificationRepo = $app->getAccountNotificationRepository();
        $oAccountnotificationModel = new AccountNotification();

        $oAccountnotificationModel->setAccountName($accountName)
            ->setSourceAccountName($aData['sourceAccountName'])
            ->setNotificationType($aData['notificationType'])
            ->setLink($aData['link'])
            ->setSubject($aData['subject'])
            ->setBody($aData['body'])
            ->setCreatedAt(date('Y-m-d H:i:s'));
        $oAccountNotificationRepo->add($oAccountnotificationModel);

        $data = ['status' => 'ok'];
        return $this->getJsonResponse($data);
    }

    public function notificationAction(Application $app, Request $request)
    {
        $accountName =  urldecode($request->get('accountName'));
        $jsonData = file_get_contents('php://input');

        $notificationType = '';
        $status = '';
        if ($jsonData) {
            $aData =  json_decode($jsonData, true);
            $notificationType = ($aData['notificationType'])? $aData['notificationType']: '';
        }
        $oAccountNotificationRepo = $app->getAccountNotificationRepository();
        $entities = $oAccountNotificationRepo->searchData($accountName, $notificationType, $status);
        $data = array();

        if ($entities) {
            foreach ($entities as $entity) {
                $data[] = [
                    'notificationType' => $entity['notification_type'],
                    'sourceAccountName' => $entity['source_account_name'],
                    'subject' => $entity['subject'],
                    'body' => $entity['body'],
                    'link' => $entity['link'],
                    'createdAt' => $entity['created_at'],
                    'seenAt' => $entity['seen_at']
                ];
            }
        }
        return $this->getJsonResponse($data);
    }
}
