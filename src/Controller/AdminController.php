<?php
namespace UserBase\Server\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Exception;

class AdminController
{

    public function indexAction(Application $app, Request $request)
    {
        $data = array();
        return new Response($app['twig']->render('admin/index.html.twig', $data));
    }

    public function logListAction(Application $app, Request $request)
    {
        $oEventRepo = $app->getEventRepository();
        $aEvents = $oEventRepo->getAll();
        
        return new Response($app['twig']->render(
            'admin/log_list.html.twig',
            array( 'aEvents' => $aEvents )
        ));
    }
}
