<?php

use UserBase\Server\Application;
use Symfony\Component\HttpFoundation\Request;

$application = new Application();

$application->before(function (Request $request) use ($application) {
    $token = $application['security']->getToken();
    if ($token) {
        if ($request->getRequestUri()!='/login') {

            if ($token->getUser() == 'anon.') {
                //exit('anon!');
                //return $app->redirect('/login');
            } else {
                $application['user'] = $token->getUser();
                $application['twig']->addGlobal('user', $token->getUser());
            }
        }
    }
    //$application['twig']->addGlobal('site', $application['site']);
});

return $application;
