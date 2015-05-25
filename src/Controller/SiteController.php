<?php

namespace UserBase\Server\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Exception;

class SiteController
{

    public function indexAction(Application $app, Request $request)
    {
        
        if (isset($app['user'])) {
            //return $app->redirect("/cp");
        }
        $data = array();
        
        $error = $app['security.last_error']($request);
        
        return new Response($app['twig']->render(
            'site/index.html.twig',
            $data
        ));
    }
    
    public function loginAction(Application $app, Request $request)
    {
        if (isset($app['user'])) {
            return $app->redirect("/cp");
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
        $data = array();
        return new Response($app['twig']->render(
            'site/signup.html.twig',
            array($data)
        ));
    }

    public function signupSubmitAction(Application $app, Request $request)
    {
        $username = $request->request->get('_username');
        $email = $request->request->get('_email');
        $password = $request->request->get('_password');
        $password2 = $request->request->get('_password2');
        
        if ($password != $password2) {
            return $app->redirect("/signup?errorcode=E02");
        }

        $repo = $app->getUserRepository();
        try {
            $user = $repo->register($username, $email);
        } catch (Exception $e) {
            return $app->redirect("/signup?errorcode=E01");
        }
        $user = $repo->getByName($username);
        
        $repo->setPassword($user, $password);

        $baseUrl = $app['userbase.baseurl'];
        
        $validatetoken = sha1($user->getEmail() . 'somesalt');
        $data = array();
        $data['link'] = $baseUrl . '/validate/' . $user->getUsername() . '/' . $validatetoken;
        $data['username'] = $username;
        $app['mailer']->sendTemplate('welcome', $user, $data);
        
        return $app->redirect("/signup/thankyou");
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


}
