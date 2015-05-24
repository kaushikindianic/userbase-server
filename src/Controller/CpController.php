<?php

namespace UserBase\Server\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Exception;

class CpController
{

    public function indexAction(Application $app, Request $request)
    {
        
        $data = array();
        
        $token = $app['security']->getToken();
        if (null !== $token) {
            $user = $token->getUser();
            $data['user'] = $user;
        }
        
        $error = $app['security.last_error']($request);
        echo "error: " . $error;

        return new Response($app['twig']->render(
            'site/cp/index.html.twig',
            $data
        ));
    }
}
