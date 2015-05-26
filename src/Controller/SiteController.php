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
            return $app->redirect($app['url_generator']->generate('signup') . '?errorcode=E02');
        }

        $repo = $app->getUserRepository();
        try {
            $user = $repo->register($username, $email);
        } catch (Exception $e) {
            return $app->redirect($app['url_generator']->generate('signup') . '?errorcode=E01');
        }
        $user = $repo->getByName($username);
        
        $repo->setPassword($user, $password);

        $baseUrl = $app['userbase.baseurl'];
        
        $validatetoken = sha1($user->getEmail() . 'somesalt');
        $data = array();
        $data['link'] = $baseUrl . '/validate/' . $user->getUsername() . '/' . $validatetoken;
        $data['username'] = $username;
        $app['mailer']->sendTemplate('welcome', $user, $data);
        
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
        return $app->redirect($app['url_generator']->generate('index'));
    }

    public function logoutSuccessAction(Application $app, Request $request)
    {
        $data = array();
        return new Response($app['twig']->render(
            'site/logout_success.html.twig',
            $data
        ));
    }
    
    public function validateAction(Application $app, Request $request, $username, $token)
    {
        // TODO: verify token and update db
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

}
