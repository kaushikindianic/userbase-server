<?php

namespace UserBase\Server\Repository;

use PDO;

use Silex\Application;

final class PdoOAuthRepository
{
    private $pdo;
    
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function registerUser($user)
    {
        var_dump($user);exit;
    }

    public function getQueueData(Application $app)
    {
        $data   = array();
        $queued = $app['session']->get('oauth');

        if ($queued) {
            $data['_email'] = $queued['userDetails']->email;
            $data['_username'] = explode("@", $data['_email'])[0];
        }

        return $data;
    }

    public function save(Application $app, $userDetails, $token)
    {
        if (empty($app['currentuser'])) {
            $app['session']->set('oauth', compact('userDetails', 'token'));
            return $app->redirect($app['url_generator']->generate('signup'));
        }
    }
}
