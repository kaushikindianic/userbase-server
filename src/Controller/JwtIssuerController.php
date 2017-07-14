<?php

namespace UserBase\Server\Controller;

use DomainException;
use RuntimeException;

use JWT;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

use UserBase\Server\Application;

class JwtIssuerController
{
    const DEFAULT_APP_AUTH_PATH = '/auth';
    const DEFAULT_JWT_ALGO = 'RS256';

    /*
     * Provides a login form, similar to the standard one, but POSTing to the
     * "check_path" of the "issuer" firewall.
     */
    public function loginFormAction(Application $app, Request $request)
    {
        return $app['twig']->render(
            'issuer/login.html.twig',
            [
                'error' => $app['security.last_error']($request),
                'last_username' => $app['session']->get('_security.last_username'),
            ]
        );
    }

    public function issueJwtAction(Application $app, Request $request)
    {
        if (!$request->query->has('origin')) {
            throw new \Exception('Missing origin from request query');
        }

        $clientApp = $app->getAppRepository()->getByName($request->query->get('origin'));

        if (!$clientApp) {
            $app->abort(
                404,
                "An application matching \"{$request->query->get('origin')}\" cannot be found."
            );
        }
        if (!$clientApp->getBaseUrl()) {
            $app->abort(
                403,
                "Users of the application \"{$request->query->get('origin')}\" cannot be issued authentication tokens by this service."
            );
        }

        if (!isset($app['parameters']['jwt_issuer']['jwt_key_path'])) {
            throw new RuntimeException('Missing value for "jwt_key_path" of "jwt_issuer" config.');
        }

        $jwtPrivateKey = file_get_contents($app['parameters']['jwt_issuer']['jwt_key_path'], false);

        if (false === $jwtPrivateKey) {
            throw new RuntimeException(
                "Unable to load JWT key file from \"{$app['parameters']['jwt_issuer']['jwt_key_path']}\"."
            );
        }

        $jwtAlgorithm = self::DEFAULT_JWT_ALGO;
        if (isset($app['parameters']['jwt_issuer']['jwt_algorithm'])) {
            $jwtAlgorithm = $app['parameters']['jwt_issuer']['jwt_algorithm'];
        }

        $jwt = null;
        try {
            $jwt = JWT::encode(
                [
                    'iss' => 'userbase',
                    'iat' => time(),
                    'exp' => time() + 300,
                    'nbf' => time() -  30,
                    'username' => $app['user']->getUsername()
                ],
                $jwtPrivateKey,
                $jwtAlgorithm
            );
        } catch (DomainException $e) {
            throw new RuntimeException(
                'Failed to generate a token for issuance.',
                null,
                $e
            );
        }

        $authPath = self::DEFAULT_APP_AUTH_PATH;

        $apps = $app['session']->get('issuer.app_tracking', []);
        $apps[$clientApp->getName()] = $clientApp;
        $app['session']->set('issuer.app_tracking', $apps);

        return new RedirectResponse(
            "{$clientApp->getBaseUrl()}{$authPath}?jwt={$jwt}"
        );
    }
}
