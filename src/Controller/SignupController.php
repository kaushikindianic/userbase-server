<?php

namespace UserBase\Server\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Service;
use Exception;
use UserBase\Server\Model\Event;
use UserBase\Server\Model\Account;
use UserBase\Server\Model\AccountTag;
use UserBase\Server\Model\AccountEmail;
use UserBase\Server\Model\AccountProperty;
use RuntimeException;
use Ramsey\Uuid\Uuid;

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
        $data['agree_text'] = $app['userbase.agree_text'];
        $data['stickTo'] = 'right';
        $useragent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        if (preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i', $useragent)||preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i', substr($useragent, 0, 4))) {
            $data['stickTo'] = 'top';
        }

        return new Response($app['twig']->render(
            '@PreAuth/signup/start.html.twig',
            $data
        ));
    }

    public function signupSubmitAction(Application $app, Request $request)
    {
        if ($app['userbase.agree_text']) {
            $agreeCheckbox = $request->request->get('agree_checkbox');
            if ($agreeCheckbox != 'Y') {
                throw new RuntimeException("Missing agreement checkbox");
            }
        }
        $username = trim(strtolower($request->request->get('_username')));
        $email = $request->request->get('_email');
        $displayname = $request->request->get('_displayname');
        $password = $request->request->get('_password');
        $password2 = $request->request->get('_password2');

        $session = $app['session'];
        $session->set('_signup.last_username', $username);
        $session->set('_signup.last_displayname', $displayname);
        $session->set('_signup.last_email', $email);

        //-- CHECK ACCOUNTNAME BLCKLIST--//
        $oBlacklistRepo = $app->getBlacklistRepository();
        foreach ($oBlacklistRepo->findAll() as $row) {
            $pattern = $row['account_name']; // this db field should probably be renamed
            if (fnmatch($pattern, $username)) {
                return $app->redirect($app['url_generator']->generate('signup') .
                    '?errorcode=invalid_accountname_word&pattern=' . $pattern);
            }
        }

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
                return $app->redirect($app['url_generator']->generate('signup') .
                 '?errorcode=invalid_mobile&mobile=' . $mobile);
            }
        }

        if (!ctype_alnum($username)) {
            return $app->redirect($app['url_generator']->generate('signup') . '?errorcode=invalid_username&detail=ctype');
        }
        if ((strlen($username)>15) || (strlen($username)<3)) {
            return $app->redirect($app['url_generator']->generate('signup') . '?errorcode=invalid_username&detail=length');
        }

        if ($password != $password2) {
            return $app->redirect($app['url_generator']->generate('signup') . '?errorcode=password_not_matching');
        }

        $userRepo = $app->getUserRepository();
        $accountRepo = $app->getAccountRepository();
        $accountEmailRepo = $app->getAccountEmailRepository();

        if ($accountRepo->getByName($username)) {
            return $app->redirect($app['url_generator']->generate('signup') . '?errorcode=account_exists');
        }
        if ($accountRepo->getByEmail($email)) {
            return $app->redirect($app['url_generator']->generate('signup') . '?errorcode=email_exists');
        }
        if ($accountEmailRepo->findByEmail($email)) {
            return $app->redirect($app['url_generator']->generate('signup') . '?errorcode=email_exists');
        }

        if ($app['userbase.enable_mobile']) {
            if ($accountRepo->getByMobile($mobile)) {
                return $app->redirect($app['url_generator']->generate('signup') . '?errorcode=mobile_exists');
            }
        }

        //--REGISTER THE EMAIL--//
        $accountEmail = new AccountEmail();
        $accountEmail->setAccountName($username);
        $accountEmail->setEmail($email);
        $accountEmailRepo->add($accountEmail);

        //--CREATE PERSONAL ACCOUNT--//
        $account = new Account($username);
        $account
            ->setDisplayName($displayname)
            ->setAbout('')
            ->setPictureUrl('')
            ->setAccountType('user')
            ->setEmail($email)
            ->setMobile($mobile)
            ->setStatus('NEW')
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

        // TAGS //
        if ($app['userbase.signup_tag']) {
            $tagRepo = $app->getTagRepository();
            $accountTagRepo = $app->getAccountTagRepository();

            $tagNames = explode(",", $app['userbase.signup_tag']);
            foreach ($tagNames as $tagName) {
                $tagData = $tagRepo->getByName($tagName);
                if (!$tagData) {
                    throw new RuntimeException("No such tag! " . $tagName);
                }
                $tagId = $tagData['id'];
                $accountTag = new AccountTag();
                $accountTag->setTagId($tagId);
                $accountTag->setAccountName($user->getUsername());
                $accountTagRepo->add($accountTag);
            }
        }
        if ($app['userbase.signup_properties']) {
            foreach ($app['userbase.signup_properties'] as $name => $value) {
                if ($value=='{uuid}') {
                    $value = $uuid4 = Uuid::uuid4();
                }
                $accountPropertyRepo = $app->getAccountPropertyRepository();
                $accountProperty = new AccountProperty();
                $accountProperty->setAccountName($user->getUsername());
                $accountProperty->setName($name);
                $accountProperty->setValue($value);
                $accountPropertyRepo->add($accountProperty);
            }
        }

        if ($app['userbase.signup_webhook']) {
            $app->sendWebhook($app['userbase.signup_webhook'], 'user.create', $user->getUsername());
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

        if ($account->getStatus()=='NEW') {
            $app->sendMail('verified', $account->getName());
            if ($app['userbase.verified_webhook']) {
                $app->sendWebhook($app['userbase.verified_webhook'], 'user.verified', $account->getName());
            }
            $account->setStatus('ACTIVE');
            $repo->update($account);
        }



        $data = array();
        return new Response($app['twig']->render(
            '@PreAuth/signup/thankyou.html.twig',
            $data
        ));
    }


    public function checkUsernameAction(Application $app, Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $username =  $request->request->get('username');
            $oUserRepo = $app->getUserRepository();

            if ($oUserRepo->getByName($username)) {
                return new JsonResponse(array(
                        'success' => false,
                        'html' => 'username already exist'
                    ));
            } else {
                return new JsonResponse(array(
                        'success' => true,
                        'html' => null
                    ));
            }
        } else {
            return $app->redirect($app['url_generator']->generate('signup'));
        }
    }
}
