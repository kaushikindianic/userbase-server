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
        $data = array();
        return new Response($app['twig']->render(
            'site/index.html.twig',
            array($data)
        ));
    }
    
    public function loginAction(Application $app, Request $request)
    {
        $data = array();
        
        $error = $app['security.last_error']($request);
        //echo $error;
        return new Response($app['twig']->render(
            'site/login.html.twig',
            array($data)
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
        
        $repo = $app->getUserRepository();
        try {
            $user = $repo->register($username, $email);
        } catch (Exception $e) {
            return $app->redirect("/signup?errorcode=E01");
        }
        
        exit("UN$username EM:$email " . $user->getUsername());
        
    }

}
