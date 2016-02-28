<?php

namespace UserBase\Server\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Service;
use Exception;
use UserBase\Server\Model\Event;
use UserBase\Server\Model\Account;

class VerifyController
{
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
            'site/verify/email.html.twig',
            $data
        ));
    }

    public function verifyMobileAction(Application $app, Request $request, $accountName)
    {
        $accountRepo = $app->getAccountRepository();
        $account = $accountRepo->getByName($accountName);
        if (!$account) {
            // no such user
            return $app->redirect($app['url_generator']->generate('login') . '?errorcode=account_not_found');
        }
        if ($account->isMobileVerified()) {
            return $app->redirect($app['url_generator']->generate('signup_thankyou', ['accountName'=>$accountName]));
        }
        
        if ($request->request->has('mobile_code')) {
            $code = $request->request->get('mobile_code');
            if ($code == '') {
                return $app->redirect($app['url_generator']->generate('verify_mobile', ['accountName'=>$accountName]) . '?errorcode=no_mobile_code');
            }
            if ($account->getMobileCode() == '') {
                return $app->redirect($app['url_generator']->generate('verify_mobile', ['accountName'=>$accountName]) . '?errorcode=no_mobile_code');
            }
            if ($code != $account->getMobileCode()) {
                return $app->redirect($app['url_generator']->generate('verify_mobile', ['accountName'=>$accountName]) . '?errorcode=mobile_code_does_not_match');
            }
            $accountRepo->setMobileVerifiedStamp($account, time());
            return $app->redirect($app['url_generator']->generate('signup_thankyou', ['accountName'=>$accountName]));
        }
        $data = array();
        if ($request->query->has('send')) {
            $code = $accountRepo->setMobileCode($account);
            $app->sendSms('verify', $accountName, ['code'=>$code]);
            $data['sent'] = true;
        }
        $data['accountName'] = $accountName;
        return new Response($app['twig']->render(
            'site/verify/mobile.html.twig',
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
}
