<?php

namespace UserBase\Server\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Service;
use Exception;
use UserBase\Server\Model\Event;
use UserBase\Server\Model\Account;
use RunMyBusiness\Initialcon\Initialcon;

class SiteController
{

    public function indexAction(Application $app, Request $request)
    {

        if (isset($app['currentuser'])) {
            return $app->redirect($app['url_generator']->generate('portal_index'));
        }
        $data = array(
            'services' => array_keys((array)Service::oauth2()),
        );

        $error = $app['security.last_error']($request);
        /*
        if (!$app['currentuser']->isEmailVerified()) {
            return $app->redirect($app['url_generator']->generate('verify_email'));
        }
        */

        return new Response($app['twig']->render(
            'site/index.html.twig',
            $data
        ));
    }

    public function loginAction(Application $app, Request $request)
    {
        if (isset($app['currentuser'])) {
            return $app->redirect($app['url_generator']->generate('portal_index'));
        }

        $data = array();
        //echo $error;
        $last_username = $app['session']->get('_security.last_username');
        $data['last_username'] = $last_username;
        
        $error = $app['security.last_error']($request);
        switch ($error) {
            case 'Bad credentials.':
                $data['errormessage'] = $app['translator']->trans('common.error_incorrectcredentials');
                break;
            case 'User account is locked.':
                $app['session']->set('_security.last_username', null);
                if ($last_username) {
                    return $app->redirect(
                        $app['url_generator']->generate(
                            'verify_email',
                            ['accountName' => $last_username]
                        )
                    );
                }
        }

        return new Response($app['twig']->render(
            'site/login.html.twig',
            $data
        ));
    }

    public function signupAction(Application $app, Request $request)
    {
        $data = $app->getOAuthrepository()->getQueueData($app);
        return new Response($app['twig']->render(
            'site/signup.html.twig',
            $data
        ));
    }

    public function signupSubmitAction(Application $app, Request $request)
    {
        $username = $request->request->get('_username');
        $email = $request->request->get('_email');
        $password = $request->request->get('_password');
        $password2 = $request->request->get('_password2');
        $mobile = '';
        if ($request->request->has('_mobile')) {
            $mobile = $request->request->get('_mobile');
        }
        

        if ($password != $password2) {
            return $app->redirect($app['url_generator']->generate('signup') . '?errorcode=E02');
        }

        $repo = $app->getUserRepository();
        try {
            $user = $repo->register($app, $username, $email);
        } catch (Exception $e) {
            return $app->redirect($app['url_generator']->generate('signup') . '?errorcode=E01');
        }
        $user = $repo->getByName($username);

        $repo->setPassword($user, $password);

        $app->sendMail('welcome', $username);
        
        //--CREATE PERSONAL ACCOUNT--//
        $oAccount = new Account($user->getUsername());
        $oAccount
            ->setDisplayName($user->getUsername())
            ->setAbout('')
            ->setPictureUrl('')
            ->setAccountType('user')
            ->setEmail($user->getEmail())
            ->setMobile($mobile)
        ;
        
        $oAccountRepo = $app->getAccountRepository();
        if ($oAccountRepo->add($oAccount)) {
            $oAccountRepo->addAccUser($user->getUsername(), $user->getUsername(), 'user');
        }
        //--EVENT LOG --//
        $time = time();
        $sEventData = json_encode(
            array(
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
                'time' => $time
            )
        );
        
        $oEvent = new Event();
        $oEvent->setName($user->getUsername());
        $oEvent->setEventName('user.create');
        $oEvent->setOccuredAt($time);
        $oEvent->setData($sEventData);
        $oEvent->setAdminName('');
        
        $oEventRepo = $app->getEventRepository();
        $oEventRepo->add($oEvent);
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
        if (!$account->isMobileVerified()) {
            return $app->redirect($app['url_generator']->generate('verify_mobile', ['accountName'=>$accountName]));
        }

        $data = array();
        return new Response($app['twig']->render(
            'site/signup_thankyou.html.twig',
            $data
        ));
    }

    public function loginSuccessAction(Application $app, Request $request)
    {
        $next = $app['session']->get('next');
        return $app->redirect($next ?: $app['url_generator']->generate('index'));
    }

    public function logoutSuccessAction(Application $app, Request $request)
    {
        $data = array();
        return new Response($app['twig']->render(
            'site/logout_success.html.twig',
            $data
        ));
    }
    
    public function verifyEmailAction(Application $app, Request $request, $accountName)
    {
        $repo = $app->getAccountRepository();
        $account = $repo->getByName($accountName);
        if (!$account) {
            // no such user
            return $app->redirect($app['url_generator']->generate('login') . '?errorcode=E04&detail=noaccount');
        }
        if ($account->isEmailVerified()) {
            return $app->redirect($app['url_generator']->generate('signup_thankyou', ['accountName'=>$accountName]));
        }
        $data = array();
        if ($request->query->has('resend')) {
            $app->sendMail('welcome', $accountName);
            $data['resent'] = true;
        }
        $data['accountName'] = $accountName;
        return new Response($app['twig']->render(
            'site/verify_email.html.twig',
            $data
        ));
    }

    public function verifyMobileAction(Application $app, Request $request, $accountName)
    {
        $accountRepo = $app->getAccountRepository();
        $account = $accountRepo->getByName($accountName);
        if (!$account) {
            // no such user
            return $app->redirect($app['url_generator']->generate('login') . '?errorcode=E04&detail=noaccount');
        }
        if ($account->isMobileVerified()) {
            return $app->redirect($app['url_generator']->generate('signup_thankyou', ['accountName'=>$accountName]));
        }
        
        if ($request->request->has('mobile_code')) {
            $code = $request->request->get('mobile_code');
            if ($code == '') {
                return $app->redirect($app['url_generator']->generate('verify_mobile', ['accountName'=>$accountName]) . '?errorcode=E81&detail=nocode1');
            }
            if ($account->getMobileCode() == '') {
                return $app->redirect($app['url_generator']->generate('verify_mobile', ['accountName'=>$accountName]) . '?errorcode=E81&detail=nocode2');
            }
            if ($code != $account->getMobileCode()) {
                return $app->redirect($app['url_generator']->generate('verify_mobile', ['accountName'=>$accountName]) . '?errorcode=E81&detail=nomatch');
            }
            $accountRepo->setMobileVerifiedStamp($account, time());
            return $app->redirect($app['url_generator']->generate('signup_thankyou', ['accountName'=>$accountName]));
        }
        $data = array();
        if ($request->query->has('resend')) {
            $code = $accountRepo->setMobileCode($account);
            $app->sendSms('verify', $accountName, ['code'=>$code]);
            $data['resent'] = true;
        }
        $data['accountName'] = $accountName;
        return new Response($app['twig']->render(
            'site/verify_mobile.html.twig',
            $data
        ));
    }


    public function verifyEmailLinkAction(Application $app, Request $request, $accountName, $stamp, $token)
    {
        $accountRepo = $app->getAccountRepository();

        $account = $accountRepo->getByName($accountName);
        
        if (!$account) {
            // no such user
            return $app->redirect($app['url_generator']->generate('verify_email') . '?errorcode=E03&detail=noaccount');
        }
        $leeway = 60 * 60; // +/- 60 minutes

        if ($stamp > time() + $leeway) {
            // expired - too early
            return $app->redirect($app['url_generator']->generate('verify_email') . '?errorcode=E05&detail=expired');
        }
        if ($stamp < time() - $leeway) {
            // expired - too late
            return $app->redirect($app['url_generator']->generate('verify_email') . '?errorcode=E05&detail=early');
        }

        $test = sha1($stamp . ':' . $account->getEmail() . ':' . $app['userbase.salt']);
        if ($test != $token) {
            // invalid token
            return $app->redirect($app['url_generator']->generate('verify_email') . '?errorcode=E04');
        }

        $accountRepo->setEmailVerifiedStamp($account, $stamp);
        return $app->redirect($app['url_generator']->generate('signup_thankyou', ['accountName'=> $accountName]));
    }

    public function passwordLostAction(Application $app, Request $request)
    {
        $data = array();
        return new Response($app['twig']->render(
            'site/password_lost.html.twig',
            array($data)
        ));
    }

    public function passwordResetRequestAction(Application $app, Request $request)
    {
        $username = $request->request->get('_username');

        $repo = $app->getUserRepository();

        $user = $repo->getByName($username);
        if (!$user) {
            // no such user
            return $app->redirect($app['url_generator']->generate('password_lost') . '?errorcode=E06');
        }

        $baseUrl = $app['userbase.baseurl'];

        $stamp = time();
        $token = sha1($stamp . ':' . $user->getEmail() . ':' . $app['userbase.salt']);
        $link = $baseUrl . '/password/reset/' . $user->getUsername() . '/' . $stamp . '/' . $token;

        $data = array();
        $data['link'] = $link;
        $data['username'] = $username;

        $app['mailer']->sendTemplate('password-reset', $user, $data);

        return $app->redirect($app['url_generator']->generate('password_reset_sent'));
    }

    public function passwordResetSentAction(Application $app, Request $request)
    {
        $data = array();
        return new Response($app['twig']->render(
            'site/password_reset_sent.html.twig',
            $data
        ));
    }


    public function passwordResetAction(Application $app, Request $request, $username, $stamp, $token)
    {
        $data = array();
        $data['stamp'] = $stamp;
        $data['username'] = $username;
        $data['token'] = $token;
        return new Response($app['twig']->render(
            'site/password_reset.html.twig',
            $data
        ));
    }
    public function passwordResetSubmitAction(Application $app, Request $request, $username, $stamp, $token)
    {
        $password = $request->request->get('_password');
        $password2 = $request->request->get('_password2');

        $urldata = array(
            'username' => $username,
            'stamp' => $stamp,
            'token' => $token
        );
        $repo = $app->getUserRepository();
        $user = $repo->getByName($username);
        if (!$user) {
            // user does not exist
            return $app->redirect($app['url_generator']->generate('password_reset', $urldata) . '?errorcode=E10');
        }

        if ($password != $password2) {
            // passwords not the same
            return $app->redirect($app['url_generator']->generate('password_reset', $urldata) . '?errorcode=E11');
        }


        $leeway = 60 * 10; // +/- 10 minutes

        if ($stamp > time() + $leeway) {
            // expired - too early
            return $app->redirect($app['url_generator']->generate('password_reset', $urldata) . '?errorcode=E12');
        }
        if ($stamp < time() - $leeway) {
            // expired - too late
            return $app->redirect($app['url_generator']->generate('password_reset', $urldata) . '?errorcode=E12');
        }

        $test = sha1($stamp . ':' . $user->getEmail() . ':' . $app['userbase.salt']);
        if ($test != $token) {
            // invalid token
            return $app->redirect($app['url_generator']->generate('password_reset', $urldata) . '?errorcode=E14');
        }

        $repo->setEmailVerifiedStamp($user, $stamp);
        $repo->setPassword($user, $password);


        return $app->redirect($app['url_generator']->generate('password_reset_success'));
    }

    public function passwordResetSuccessAction(Application $app, Request $request)
    {
        $data = array();
        return new Response($app['twig']->render(
            'site/password_reset_success.html.twig',
            $data
        ));
    }
    
    public function pictureAction(Application $app, Request $request, $accountname)
    {
        $repo = $app->getAccountRepository();
        $account = $repo->getByName($accountname);
        $fileName = $accountname . '.png';
        
        
        if (is_file($app['picturePath'].'/'.$fileName)) {
           //echo '/'.$app['picturePath'].'/'.$account->getPictureUrl();exit;
            header("Expires: Sat, 26 Jul 2020 05:00:00 GMT");
            return $app->redirect('/'.$app['picturePath'].'/'.$fileName);
        } else {
            if ($account) {
                $value = $account->getEmail();
                if (!$value) {
                    $value = $account->getName();
                }
                $initials = $account->getInitials();
            } else {
                $initials = '?';
            }
            /*
            $url = "https://www.gravatar.com/avatar/" . md5(strtolower(trim($value))) . "?d=retro";
            return $app->redirect($url);
            */
            //$url = $account->getPictureUrl();
            
            $size = 128;
            if ($request->query->has('s')) {
                $size = (int)$request->query->get('s');
                if ($size>512) {
                    $size = 512;
                }
            }
            $initialcon = new Initialcon();
            $img = $initialcon->getImageObject($initials, $accountname, $size);
            header("Expires: Sat, 26 Jul 2020 05:00:00 GMT");
            echo $img->response('png');
            exit();
        }
    }
    
    
}
