<?php

namespace UserBase\Server\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Service;
use Exception;
use UserBase\Server\Model\Event;
use UserBase\Server\Model\Account;
use RuntimeException;

class LoginController
{
    public function indexAction(Application $app, Request $request)
    {

        if (isset($app['currentuser'])) {
            return $app->redirect($app['url_generator']->generate('portal_index'));
        }
        $data = array(
            'services' => array_keys((array)Service::oauth2()),
        );

        $error = $app['security.last_error']($request);

        return new Response($app['twig']->render(
            'site/index.html.twig',
            $data
        ));
    }

    public function loginAction(Application $app, Request $request)
    {
        if (isset($app['currentuser'])) {
            return $app->redirect($app['url_generator']->generate('portal_index'));
        }

        $data = array();
        //echo $error;
        $last_username = $app['session']->get('_security.last_username');
        $data['last_username'] = $last_username;
        
        $error = $app['security.last_error']($request);
        switch ($error) {
            case 'Bad credentials.':
                $data['errormessage'] = $app['translator']->trans('common.error_incorrectcredentials');
                break;
            case 'User account is disabled.':
                $app['session']->set('_security.last_username', null);
                if ($last_username) {
                    return $app->redirect(
                        $app['url_generator']->generate(
                            'verify_email',
                            ['accountName' => $last_username]
                        )
                    );
                }
                break;
            default:
                if ($error) {
                    throw new RuntimeException("Unsupported error: " . $error);
                }
        }

        return new Response($app['twig']->render(
            'site/login.html.twig',
            $data
        ));
    }
    
    public function loginSuccessAction(Application $app, Request $request)
    {
        $next = $app['session']->get('next');
        return $app->redirect($next ?: $app['url_generator']->generate('index'));
    }

    public function logoutSuccessAction(Application $app, Request $request)
    {
        $data = array();
        return new Response($app['twig']->render(
            'site/logout_success.html.twig',
            $data
        ));
    }

}
