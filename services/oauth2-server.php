<?php

/**
 *  @Service(oauth2-server, {
 *  }, { shared: true})
 */
function oauth2_server($config, $extra)
{
    $storage = new OAuth2\Storage\Pdo(Service::pdo());
    $server  = new OAuth2\Server($storage);
    $server->addGrantType(new OAuth2\GrantType\ClientCredentials($storage)); // or any grant type you like!
    $server->addGrantType(new OAuth2\GrantType\AuthorizationCode($storage)); // or any grant type you like!

    return $server;
}
