<?php

namespace UserBase\Server\Repository;

use PDO;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
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

    /**
     *  Attempt to Login
     *
     *  This method check is the 3rd-party account is not already associated with 
     *  any account, if so, we log them in otherwise we show the sign-up form.
     *
     *
     */
    protected function attemptToLogin(Application $app, $userDetails, $token, &$return)
    {
        $urls    = $userDetails->urls;
        $service = key($urls);
        $query = $this->pdo->prepare("SELECT user_name FROM identities WHERE service = ? and identity_uid = ?");
        $query->execute(array($service, $userDetails->uid));
        $user_id = $query->fetch();
        if (empty($user_id) || !($user = $app->getUserRepository()->getByName($user_id['user_name']))) {
            /* No user :-( */
            return false;
        }

        $token = new UsernamePasswordToken($user, $user->getPassword(), "default", $user->getRoles());
        $app['security']->setToken($token);
        $return = $app->redirect("/");
        return true;
    }

    public function save(Application $app, $userDetails, $token)
    {
        if (empty($app['currentuser'])) {
            if ($this->attemptToLogin($app, $userDetails, $token, $return)) {
                return $return;
            }
            $app['session']->set('oauth', compact('userDetails', 'token'));
            return $app->redirect($app['url_generator']->generate('signup'));
        }

        // user does have a valid session, we store their identity
        // and redirect them to somewhere
        $this->addIdentity($app['currentuser'], $userDetails, $token);

        return $app->redirect('/');
    }
}
