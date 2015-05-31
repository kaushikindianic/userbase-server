<?php

use Herald\Client\Client as HeraldClient;
use UserBase\Server\Mailer\HeraldMailer;

/**
 *  @Service(mailer, {
 *    heraldclient: { type: HeraldClient },
 *  }, { shared: true })
 */

function getMailer(Array $config)
{
    $client = Service::herald();
    $mailer = new HeraldMailer($client);
    return $mailer;
}
