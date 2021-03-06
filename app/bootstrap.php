<?php

use UserBase\Server\Application;
use Symfony\Component\HttpFoundation\Request;

$service = new \ServiceProvider\Provider(
    __DIR__.'/../app/config/parameters.yml',
    __DIR__.'/../services/*.php',
    '/tmp/userbase.service.'.sha1(__DIR__).'.php',
    'Service'
);

$application = new Application();

$application->before(function (Request $request) use ($application) {
    $token = $application['security.token_storage']->getToken();
    if ($token) {
        if ($request->get('_route') != 'login') {
            if ($token->getUser() == 'anon.') {
                //exit('anon!');
                //return $app->redirect('/login');
            } else {
                $accountRepo = $application->getAccountRepository();
                $account = $accountRepo->getByName($token->getUser()->getUsername());
                $application['currentuser'] = $token->getUser();
                $application['currentaccount'] = $account;

                $application['twig']->addGlobal('currentuser', $token->getUser());
                $application['twig']->addGlobal('currentaccount', $account);
            }
        }
    }

    $postfix = $application['userbase.postfix'];
    if ($postfix) {
        $application['twig']->addGlobal('postfix', $postfix);
    }
    $application['twig']->addGlobal('logourl', $application['userbase.logourl']);
    $application['twig']->addGlobal('enable_mobile', $application['userbase.enable_mobile']);

    $loginUrl = null;
    if (isset($application['userbase.login_url'])) {
        $loginUrl = $application['userbase.login_url'];
    }
    if (!$loginUrl) {
        $urlGenerator = $application['url_generator'];
        $loginUrl = $urlGenerator->generate('login');
    }
    $application['twig']->addGlobal('login_url', $loginUrl);

    $filter = new Twig_SimpleFilter('mydate', function ($value) {
        if ($value > 0) {
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

    if (strpos($request->getUri(), '/portal/')) {
        if ($request->attributes->has('accountname')) {
            $accountRepo = $application->getAccountRepository();
            $account = $accountRepo->getByName($request->attributes->get('accountname'));
            $application['twig']->addGlobal('account', $account);
        }
    }

    if ($request->query->has('errorcode')) {
        $errorcode = $request->query->get('errorcode');
        $errorString = $application['translator']->trans('error.'.$errorcode);
        if ('error.'.$errorcode == $errorString) {
            //   throw new RuntimeException("Undefinded error code: " . $errorcode);
        }
        $application['twig']->addGlobal('error_string', $errorString);
    }

    //$application['twig']->addGlobal('site', $application['site']);
});

return $application;
