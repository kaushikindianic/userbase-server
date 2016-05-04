<?php

namespace UserBase\Server\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Service;
use Exception;
use UserBase\Server\Model\Event;
use UserBase\Server\Model\Account;

class PasswordResetController
{
    public function passwordResetStartAction(Application $app, Request $request)
    {
        $data = array();
        return new Response($app['twig']->render(
            '@PreAuth/password_reset/start.html.twig',
            array($data)
        ));
    }

    public function passwordResetRequestAction(Application $app, Request $request)
    {
        $search = $request->request->get('_search');

        $userRepo = $app->getUserRepository();
        $accountRepo = $app->getAccountRepository();

        $account = null;
        $account = $accountRepo->getByName($search);
        if (!$account) {
            $account = $accountRepo->getByEmail($search);
        }
        if (!$account) {
            $account = $accountRepo->getByMobile($search);
        }

        if (!$account) {
            return $app->redirect($app['url_generator']->generate('password_reset') . '?errorcode=account_not_found');
        }

        if ($account->getAccountType()!='user') {
            return $app->redirect($app['url_generator']->generate('password_reset') . '?errorcode=account_not_user');
        }


        $user = $userRepo->getByName($account->getName());
        $baseUrl = $app['userbase.baseurl'];

        if ($app['userbase.enable_mobile']) {
            if (!$account->getMobile() || !$account->isMobileVerified()) {
                return $app->redirect($app['url_generator']->
                generate('password_reset') . '?errorcode=mobile_not_verified');
            }

            $code = $accountRepo->setMobileCode($account);
            $app->sendSms('verify', $account->getName(), ['code'=>$code]);
            $data['sent'] = true;

            return $app->redirect($app['url_generator']->
                    generate('password_reset_mobile_check', ['accountName' => $account->getName()]));
        } else {
            $stamp = time();
            $token = sha1($stamp . ':' . $user->getEmail() . ':' . $app['userbase.salt']);
            $link = $baseUrl . '/password-reset/' . $user->getUsername() . '/' . $stamp . '/' . $token;

            $data = array();
            $data['link'] = $link;
            $data['username'] = $username;

            $app['mailer']->sendTemplate('password-reset', $account, $data);

            return $app->redirect($app['url_generator']->generate('password_reset_sent'));
        }
    }

    public function passwordResetSentAction(Application $app, Request $request)
    {
        $data = array();
        return new Response($app['twig']->render(
            '@PreAuth/password_reset/sent.html.twig',
            $data
        ));
    }


    public function passwordResetUpdateAction(Application $app, Request $request, $username, $stamp, $token)
    {
        $data = array();
        $data['stamp'] = $stamp;
        $data['username'] = $username;
        $data['token'] = $token;
        return new Response($app['twig']->render(
            '@PreAuth/password_reset/update.html.twig',
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
        $userRepo = $app->getUserRepository();
        $accountRepo = $app->getAccountRepository();
        $user = $userRepo->getByName($username);
        $account = $accountRepo->getByName($username);
        if (!$user) {
            // user does not exist
            return $app->redirect($app['url_generator']->
            generate('password_reset_update', $urldata) . '?errorcode=account_not_found');
        }

        if ($password != $password2) {
            // passwords not the same
            return $app->redirect($app['url_generator']->
            generate('password_reset_update', $urldata) . '?errorcode=password_not_matching');
        }


        $leeway = 60 * 10; // +/- 10 minutes

        if ($stamp > time() + $leeway) {
            // expired - too early
            return $app->redirect($app['url_generator']->
                generate('password_reset_update', $urldata) . '?errorcode=password_reset_link_invalid');
        }
        if ($stamp < time() - $leeway) {
            // expired - too late
            return $app->redirect($app['url_generator']->
            generate('password_reset_update', $urldata) . '?errorcode=password_reset_link_invalid');
        }

        $test = sha1($stamp . ':' . $user->getEmail() . ':' . $app['userbase.salt']);
        if ($test != $token) {
            // invalid token
            return $app->redirect($app['url_generator']
            ->generate('password_reset_update', $urldata) . '?errorcode=password_reset_link_invalid');
        }

        $accountRepo->setEmailVerifiedStamp($account, $stamp);
        $userRepo->setPassword($user, $password);


        return $app->redirect($app['url_generator']->generate('password_reset_success'));
    }

    public function passwordResetSuccessAction(Application $app, Request $request)
    {
        $data = array();
        return new Response($app['twig']->render(
            '@PreAuth/password_reset/success.html.twig',
            $data
        ));
    }

    public function passwordResetMobileCheckAction(Application $app, Request $request, $accountName)
    {
        $accountRepo = $app->getAccountRepository();
        $account = $accountRepo->getByName($accountName);
        if (!$account) {
            // no such user
            return $app->redirect($app['url_generator']->generate('password_reset') . '?errorcode=account_not_found');
        }
        if (!$account->isMobileVerified()) {
            return $app->redirect($app['url_generator']->generate('password_reset') . '?errorcode=mobile_not_verified');
        }

        if ($request->request->has('mobile_code')) {
            $code = $request->request->get('mobile_code');
            if ($code == '') {
                return $app->redirect($app['url_generator']->
                generate('password_reset_mobile_check', ['accountName'=>$accountName]) . '?errorcode=no_mobile_code');
            }
            if ($account->getMobileCode() == '') {
                return $app->redirect($app['url_generator']->
                generate('password_reset_mobile_check', ['accountName'=>$accountName]) . '?errorcode=no_mobile_code');
            }
            if ($code != $account->getMobileCode()) {
                return $app->redirect(
                    $app['url_generator']->generate(
                        'password_reset_mobile_check',
                        ['accountName'=>$accountName]
                    ) . '?errorcode=mobile_code_does_not_match'
                );
            }
            $accountRepo->setMobileVerifiedStamp($account, time());

            $baseUrl = $app['userbase.baseurl'];
            $stamp = time();
            $token = sha1($stamp . ':' . $account->getEmail() . ':' . $app['userbase.salt']);
            $link = $baseUrl . '/password-reset/' . $account->getName() . '/' . $stamp . '/' . $token;
            return $app->redirect($link);
        }
        $data = array();
        $data['accountName'] = $account->getName();
        return new Response($app['twig']->render(
            '@PreAuth/password_reset/mobile_check.html.twig',
            $data
        ));
    }
}
