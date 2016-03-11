<?php

use Herald\Client\Client as HeraldClient;

/**
 *  @Service(herald, {
 *    baseurl: { default:"http://localhost", type: string },
 *    username: { type: string},
 *    password: { type: string},
 *    transport: { type: string },
 *    prefix: { type: string }
 *  }, { shared: true })
 */

function getHerald(Array $config)
{
    $herald = new HeraldClient(
        $config['username'],
        $config['password'],
        $config['baseurl'],
        $config['account'],
        $config['library'],
        $config['transport']
    );
    $herald->setTemplateNamePrefix($config['prefix']);
    return $herald;
}
