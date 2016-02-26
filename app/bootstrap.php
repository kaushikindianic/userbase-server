<?php

use UserBase\Server\Application;
use Symfony\Component\HttpFoundation\Request;

$service = new \ServiceProvider\Provider(
    __DIR__ . '/../app/config/parameters.yml',
    __DIR__ . '/../services/*.php',
    '/tmp/userbase.service.' . sha1(__DIR__) . '.php',
    'Service'
);

$application = new Application();

$application->before(function (Request $request) use ($application) {
    $token = $application['security.token_storage']->getToken();
    if ($token) {
        if ($request->getRequestUri()!='/login') {
            if ($token->getUser() == 'anon.') {
                //exit('anon!');
                //return $app->redirect('/login');
            } else {
                $accountRepo = $application->getAccountRepository();
                $account = $accountRepo->getByName($token->getUser()->getUsername());
                $application['currentuser'] = $token->getUser();
                $application['currentaccount'] = $account;
                
                $application['twig']->addGlobal('currentuser', $token->getUser());
            }
        }
    }

    $postfix = $application['userbase.postfix'];
    if ($postfix) {
        $application['twig']->addGlobal('postfix', $postfix);
    }
    $application['twig']->addGlobal('logourl', $application['userbase.logourl']);
    $application['twig']->addGlobal('enable_mobile', $application['userbase.enable_mobile']);

    $filter = new Twig_SimpleFilter('mydate', function ($value) {
        if ($value>0) {
            return date('d/M/Y', $value);
        } else {
            return '-';
        }
    });
    $application['twig']->addFilter($filter);

    $filter = new Twig_SimpleFilter('star', function ($value) {
        $value = str_replace('*', '<i class="fa fa-star"></i>', $value);
        return $value;
    });
    $application['twig']->addFilter($filter);
    
    // Secure admin and api urls
    if (strpos($request->getUri(), '/admin') || strpos($request->getUri(), '/api/')) {
        if (!isset($application['currentuser'])) {
            return $application->redirect('/?errcode=noadmin1');
        }
        if (!$application['currentuser']->isAdmin()) {
            return $application->redirect('/?errcode=noadmin2');
        }
    }
    
    if (!strpos($request->getUri(), '/api/')) {
        /*
        if ($application['currentaccount']->getAccountType()!='apikey') {
            return $application->redirect('/?errcode=apikey');
        }
        */
    }

    //$application['twig']->addGlobal('site', $application['site']);
});

return $application;
