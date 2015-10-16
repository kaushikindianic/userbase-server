<?php

/**
 *  @Service(pdo, {
 *    dsn: { default:"mysql://localhost/userbase", type: string },
 *    username: { type: string},
 *    password: { type: string},
 *  }, { shared: true })
 */
function getPdo(Array $config)
{
    return new PDO($config['dsn'], $config['username'], $config['password']);
}
