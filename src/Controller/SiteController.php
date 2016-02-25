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
            //return $app->redirect("/cp");
        }
        $data = array(
            'services' => array_keys((array)Service::oauth2()),
        );

        $error = $app['security.last_error']($request);

        return new Response($app['twig']->render(
            'site/index.html.twig',
            $data
        ));
    }

    public function loginAction(Application $app, Request $request)
    {
        if (isset($app['currentuser'])) {
            return $app->redirect($app['url_generator']->generate('cp_index'));
        }

        $data = array();
        //echo $error;

        $error = $app['security.last_error']($request);
        if ($error == 'Bad credentials.') {
            $data['errormessage'] = $app['translator']->trans('common.error_incorrectcredentials');
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

        $baseUrl = $app['userbase.baseurl'];

        $stamp = time();
        $validatetoken = sha1($stamp . ':' . $user->getEmail() . ':' . $app['userbase.salt']);
        $link = $baseUrl . '/validate/' . $user->getUsername() . '/' . $stamp . '/' . $validatetoken;
        $data = array();
        $data['link'] = $link;
        $data['username'] = $username;
        $app['mailer']->sendTemplate('welcome', $user, $data);
        
        //--CREATE PERSONAL ACCOUNT--//
        $oAccunt = new Account($user->getUsername());
        $oAccunt->setDisplayName($user->getUsername())
                ->setAbout('')
                ->setPictureUrl('')
                ->setAccountType('user')
                ->setEmail($user->getEmail())
                ;
        
        $oAccRepo = $app->getAccountRepository();
        if ($oAccRepo->add($oAccunt)) {
            $oAccRepo->addAccUser($user->getUsername(), $user->getUsername(), 'user');
        }        
        //--EVENT LOG --//
        $time = time();
        $sEventData = json_encode( array('username' => $user->getUsername(), 'email' => $user->getEmail(), 'time' => $time ));
        
        $oEvent = new Event();
        $oEvent->setName($user->getUsername());
        $oEvent->setEventName('user.create');
        $oEvent->setOccuredAt($time);
        $oEvent->setData($sEventData);
        $oEvent->setAdminName('');
        
        $oEventRepo = $app->getEventRepository();
        $oEventRepo->add($oEvent);
        //-- END EVENT LOG --//        
        
        return $app->redirect($app['url_generator']->generate('signup_thankyou'));

    }

    public function signupThankYouAction(Application $app, Request $request)
    {
        $data = array();
        $data['email'] = 'x@y.z';
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

    public function validateAction(Application $app, Request $request, $username, $stamp, $token)
    {
        $repo = $app->getUserRepository();

        $user = $repo->getByName($username);
        if (!$user) {
            // no such user
            return $app->redirect($app['url_generator']->generate('signup') . '?errorcode=E03');
        }
        $leeway = 60 * 60; // +/- 60 minutes

        if ($stamp > time() + $leeway) {
            // expired - too early
            return $app->redirect($app['url_generator']->generate('signup') . '?errorcode=E05&detail=expired');
        }
        if ($stamp < time() - $leeway) {
            // expired - too late
            return $app->redirect($app['url_generator']->generate('signup') . '?errorcode=E05&detail=early');
        }

        $test = sha1($stamp . ':' . $user->getEmail() . ':' . $app['userbase.salt']);
        if ($test != $token) {
            // invalid token
            return $app->redirect($app['url_generator']->generate('signup') . '?errorcode=E04');
        }

        $repo->setEmailVerifiedStamp($user, $stamp);
        return $app->redirect($app['url_generator']->generate('validate_success'));
    }

    public function validateSuccessAction(Application $app, Request $request)
    {
        $data = array();
        return new Response($app['twig']->render(
            'site/validate_success.html.twig',
            array($data)
        ));
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
            
            $initialcon = new Initialcon();
            $img = $initialcon->getImageObject($initials, $accountname, 128);
            header("Expires: Sat, 26 Jul 2020 05:00:00 GMT");
            echo $img->response('png');
            exit();
        }
    }
    
    
}
