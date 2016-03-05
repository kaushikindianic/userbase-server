<?php
error_reporting(E_ALL);
ini_set('display_errors', 'on');

// use Symfony\Component\HttpFoundation\Request;
require_once __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../app/bootstrap.php';
// support baseurl
if (isset($app['userbase.baseurl'])) {
    $app->before(function () use ($app) {
        $app['request_context']->setBaseUrl($app['userbase.baseurl']);
    });
}
// $app->run(Request::createFromGlobals());
$app->run();
