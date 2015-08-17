<?php

namespace UserBase\Server\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Exception;
use JWT;

class PortalController
{

    public function indexAction(Application $app, Request $request)
    {
        
        $data = array();
        
        $user = $app['currentuser'];
        $accountRepo = $app->getAccountRepository();
        $data['accounts'] = $accountRepo->getByUsername($user->getName());

        return new Response($app['twig']->render(
            'portal/index.html.twig',
            $data
        ));
    }
    
    public function appLoginAction(Application $app, Request $request, $appname)
    {
        $user = $app['currentuser'];
        $accountRepo = $app->getAccountRepository();
        $account = $accountRepo->getByAppNameAndUsername($appname, $user->getName());

        $key = 'super_secret'; // TODO: make this configurable + support rsa
        $token = array(
            "iss" => 'userbase',
            "aud" => $appname,
            "iat" => time(),
            "exp" => time() + (60*10),
            "sub" => $user->getName(),
            "my_own_thing" => 'this_needs_to_be_something_sensible'
        );
        $jwt = JWT::encode($token, $key);
        
        $url = $account->getApp()->getBaseUrl();
        
        // TODO: The way of passing JWT's should be configurable per app
        $url .= '/login/jwt/' . $jwt;
        return $app->redirect($url);
        
        exit($url);
    }
}
