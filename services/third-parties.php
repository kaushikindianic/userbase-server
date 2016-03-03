<?php


/**
 *  @Service(oauth2, {
 *      return_url : { type: string, required: true },
 *      scopes : { type: array, required: true },
 *      services : { type: array, required: true },
 *  }, {shared: true})
 */
function getOAuth2Client(Array $config, $app)
{
    $redirectUri = $app['userbase.baseurl'].(isset($config['return_url'])?$config['return_url']:'');
    $scopes      = isset($config['scopes'])?$config['scopes']:null;
    $services    = array();
    if (isset($config['services']) && is_array($config['services'])) {
        foreach ($config['services'] as $service => $settings) {
            $settings = array_merge($settings, compact('redirectUri', 'scopes'));
            $settings['redirectUri'] .= '/'.$service;
            $class    = 'League\OAuth2\Client\Provider\\'.ucfirst($service);
            $services[$service] = new $class($settings);
        }
    }
    return (object)$services;
}
