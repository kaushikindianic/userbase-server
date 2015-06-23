<?php

namespace UserBase\Server\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use OAuth2;
use Service;

class OAuth2ServerController
{
    public function authorize(Application $app, Request $req)
    {
        $server = Service::get('oauth2-server');
        $request = OAuth2\Request::createFromGlobals();
        $response = new OAuth2\Response();

        // validate the authorize request
        if (!$server->validateAuthorizeRequest($request, $response)) {
            $response->send();
            die;
        }

        if (empty($_POST)) {
            if (empty($app['currentuser'])) {
                /* show login dialog first */
                $app['session']->set('next', $req->getUri());
                return $app->redirect($app['url_generator']->generate('login'));
            }
            die('confirmation dialog');
        }

        exit;
    }

    public function api(Application $app, Request $req)
    {
        $server = Service::get('oauth2-server');
        if (!$server->verifyResourceRequest(OAuth2\Request::createFromGlobals())) {
            $server->getResponse()->send();
            die;
        }
        echo json_encode(array('success' => true, 'message' => 'You accessed my APIs!'));
        exit;
    }
}
