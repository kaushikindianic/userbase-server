<?php

namespace UserBase\Server\Controller;

use Silex\Application;
use OAuth2\HttpFoundationBridge\Response as BridgeResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use OAuth2;
use Service;

class OAuth2ServerController
{

    public function code(Application $app, Request $req)
    {
        $server = Service::get('oauth2-server');
        return $server->handleTokenRequest(OAuth2\Request::createFromGlobals(), new BridgeResponse);
    }

    public function authorize(Application $app, Request $req)
    {
        $server = Service::get('oauth2-server');
        $request = OAuth2\Request::createFromGlobals();
        $response = new BridgeResponse;

        // validate the authorize request
        if (!$server->validateAuthorizeRequest($request, $response)) {
            return $response;
        }

        if (empty($_POST)) {
            if (empty($app['currentuser'])) {
                /* show login dialog first */
                $app['session']->set('next', $req->getUri());
                return $app->redirect($app['url_generator']->generate('login'));
            }

            return new Response($app['twig']->render(
                'oauth2/authorize.html.twig',
                array()
            ));
        }

        $is_authorized = ($_POST['authorized'] === 'yes');
        $server->handleAuthorizeRequest($request, $response, $is_authorized);
        return $response;
    }

    public function api(Application $app, Request $req)
    {
        $server = Service::get('oauth2-server');
        $response = new BridgeResponse;
        if (!$server->verifyResourceRequest(OAuth2\Request::createFromGlobals(), $response)) {
            return $response;
        }
        
        return $app->json(array('success' => true, 'message' => 'You accessed my APIs!'));
    }
}
