<?php

namespace UserBase\Server\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Service;

class OAuth2Controller
{
    public function confirmAction(Application $app, Request $req, $provider)
    {
        $oauth2 = Service::oauth2($app);
        if (empty($oauth2->$provider) || !$req->get('code')) {
            return new Response('', 404);
        }
        $provider = $oauth2->$provider;

        $token = $provider->getAccessToken('authorization_code', ['code' => $req->get('code')]);
        $userDetails = $provider->getUserDetails($token);
        return $app->getOAuthrepository()->save($app, $userDetails, $token);
    }

    public function authorizeAction(Application $app, Request $req, $provider)
    {
        $oauth2 = Service::oauth2($app);
        if (empty($oauth2->$provider)) {
            return new Response('', 404);
        }

        $authUrl = $oauth2->$provider->getAuthorizationUrl();
        $app['session']->set('oauth2_state', $oauth2->$provider->state);
        return $app->redirect($authUrl);
    }
}
