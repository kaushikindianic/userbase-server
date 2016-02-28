<?php

namespace UserBase\Server\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Service;
use Exception;
use UserBase\Server\Model\Event;
use UserBase\Server\Model\Account;

class SignupController
{
    public function signupAction(Application $app, Request $request)
    {
        $data = $app->getOAuthrepository()->getQueueData($app);
        $session = $app['session'];
        $data['last_username'] = $session->get('_signup.last_username');
        $data['last_email'] = $session->get('_signup.last_email');
        $data['last_mobile'] = $session->get('_signup.last_mobile');
        $data['last_username'] = $session->get('_signup.last_username');
        $data['last_displayname'] = $session->get('_signup.last_displayname');
        return new Response($app['twig']->render(
            'site/signup/start.html.twig',
            $data
        ));
    }

    public function signupSubmitAction(Application $app, Request $request)
    {
        $username = trim(strtolower($request->request->get('_username')));
        $email = $request->request->get('_email');
        $displayname = $request->request->get('_displayname');
        $password = $request->request->get('_password');
        $password2 = $request->request->get('_password2');
        
        $session = $app['session'];
        $session->set('_signup.last_username', $username);
        $session->set('_signup.last_displayname', $displayname);
        $session->set('_signup.last_email', $email);
        
        $mobile = '';
        if ($request->request->has('_mobile')) {
            $mobile = $request->request->get('_mobile');
            $session->set('_signup.last_mobile', $mobile);
            $mobile = trim($mobile);
            $mobile = str_replace(' ', '', $mobile);
            $mobile = str_replace('-', '', $mobile);
            $mobile = str_replace('+', '00', $mobile);
            
            if (substr($mobile, 0, 2) == '06') {
                $mobile = '00316' . substr($mobile, 2);
            }
            
            if (strlen($mobile)!=13) {
                return $app->redirect($app['url_generator']->generate('signup') . '?errorcode=invalid_mobile&mobile=' . $mobile);
            }
        }
        
        if (!ctype_alpha($username)) {
            return $app->redirect($app['url_generator']->generate('signup') . '?errorcode=invalid_username');
        }
        if ((strlen($username)>32) || (strlen($username)<3)) {
            return $app->redirect($app['url_generator']->generate('signup') . '?errorcode=invalid_username');
        }

        if ($password != $password2) {
            return $app->redirect($app['url_generator']->generate('signup') . '?errorcode=password_not_matching');
        }

        $userRepo = $app->getUserRepository();
        $accountRepo = $app->getAccountRepository();
        
        if ($accountRepo->getByName($username)) {
            return $app->redirect($app['url_generator']->generate('signup') . '?errorcode=account_exists');
        }
        if ($accountRepo->getByEmail($email)) {
            return $app->redirect($app['url_generator']->generate('signup') . '?errorcode=email_exists');
        }
        if ($accountRepo->getByMobile($mobile)) {
            return $app->redirect($app['url_generator']->generate('signup') . '?errorcode=mobile_exists');
        }
        
        
        //--CREATE PERSONAL ACCOUNT--//
        $account = new Account($username);
        $account
            ->setDisplayName($displayname)
            ->setAbout('')
            ->setPictureUrl('')
            ->setAccountType('user')
            ->setEmail($email)
            ->setMobile($mobile)
        ;
        
        if (!$accountRepo->add($account)) {
            return $app->redirect($app['url_generator']->generate('signup') . '?errorcode=register_failed');
        }

        try {
            $user = $userRepo->register($app, $username, $email);
        } catch (Exception $e) {
            return $app->redirect($app['url_generator']->generate('signup') . '?errorcode=register_failed');
        }
        $user = $userRepo->getByName($username);

        $userRepo->setPassword($user, $password);
        
        //--CLEAR SIGNUP SESSION DATA
        $session->set('_signup.last_username', null);
        $session->set('_signup.last_email', null);
        $session->set('_signup.last_mobile', null);
        $session->set('_signup.last_displayname', null);
        
        $accountRepo->addAccUser($user->getUsername(), $user->getUsername(), 'user');
        //--EVENT LOG --//
        $time = time();
        $sEventData = json_encode(
            array(
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
                'time' => $time
            )
        );
        
        $event = new Event();
        $event->setName($user->getUsername());
        $event->setEventName('user.create');
        $event->setOccuredAt($time);
        $event->setData($sEventData);
        $event->setAdminName('');
        
        $eventRepo = $app->getEventRepository();
        $eventRepo->add($event);
        
        $app->sendMail('welcome', $username);

        //-- END EVENT LOG --//
        return $app->redirect(
            $app['url_generator']->generate(
                'signup_thankyou',
                ['accountName' => $user->getUsername()]
            )
        );
    }

    public function signupThankYouAction(Application $app, Request $request, $accountName)
    {
        $repo = $app->getAccountRepository();
        $account = $repo->getByName($accountName);
        if (!$account) {
            return $app->redirect($app['url_generator']->generate('signup')) . '?errorcode=E21&detail=noaccount';
        }
        if (!$account->isEmailVerified()) {
            return $app->redirect($app['url_generator']->generate('verify_email', ['accountName'=>$accountName]));
        }
        if ($app['userbase.enable_mobile']) {
            if (!$account->isMobileVerified()) {
                return $app->redirect($app['url_generator']->generate('verify_mobile', ['accountName'=>$accountName]));
            }
        }

        $data = array();
        return new Response($app['twig']->render(
            'site/signup/thankyou.html.twig',
            $data
        ));
    }
}
