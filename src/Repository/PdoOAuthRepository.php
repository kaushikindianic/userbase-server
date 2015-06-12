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

    public function registerUser(Application $app, $user)
    {
        $queued = $app['session']->get('oauth');
        if ($queued) {
            $this->addIdentity($user, $queued['userDetails'], $queued['token']);
            // forget!
            $app['session']->set('oauth', null);
        }
    }

    protected function insert($table, Array $data)
    {
        $columns = array_keys($data);
        $fields = implode(",", $columns);
        $placeholders = ':' . implode(",:", $columns);
        $this->pdo
            ->prepare("INSERT INTO $table ($fields) VALUES($placeholders)")
            ->execute($data);
        
        return $this->pdo->lastInsertId();
    }

    public function addIdentity($user, $userDetails, $token)
    {
        $urls = $userDetails->urls;
        return $this->insert('identities', array(
            'user_name' => $user->name,
            'service' => key($urls),
            'access_token' => $token->accessToken,
            'expires' => $token->expires,
            'refresh_token' => $token->refreshToken,
            'identity_uid' => $userDetails->uid,
            'identity_avatar' => $userDetails->imageUrl,
            'identity_email' => $userDetails->email,
            'identity_first_name' => $userDetails->firstName,
            'identity_last_name' => $userDetails->lastName,
            'identity_object' => json_encode($userDetails->getArrayCopy()),
        ));
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

        // user does have a valid session, we store their identity
        // and redirect them to somewhere
        $this->addIdentity($user, $userDetails, $token);

        return $app->redirect('...');
    }
}
