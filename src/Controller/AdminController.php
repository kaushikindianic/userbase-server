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
        return new Response($app['twig']->render(
            'admin/index.html.twig',
            $data
        ));
    }

    public function userListAction(Application $app, Request $request)
    {
        $data = array();
        $repo = $app->getUserRepository();
        $users = $repo->getAll();
        $data['usercount'] = count($users);
        $data['users'] = $users;
        return new Response($app['twig']->render(
            'admin/user_list.html.twig',
            $data
        ));
    }

    public function userViewAction(Application $app, Request $request, $username)
    {
        $data = array();
        $userRepo = $app->getUserRepository();
        $accountRepo = $app->getAccountRepository();

        $viewuser = $userRepo->getByName($username);
        $data['username'] = $username;
        $data['viewuser'] = $viewuser;
        $data['accounts'] = $accountRepo->getByUsername($username);
        return new Response($app['twig']->render(
            'admin/user_view.html.twig',
            $data
        ));
    }

    public function userToolsAction(Application $app, Request $request, $username)
    {
        $data = array();
        $repo = $app->getUserRepository();
        $viewuser = $repo->getByName($username);
        $data['username'] = $username;
        $data['viewuser'] = $viewuser;
        return new Response($app['twig']->render(
            'admin/user_tools.html.twig',
            $data
        ));
    }

    public function userUpdatePasswordAction(Application $app, Request $request, $username)
    {
        $newPassword = $request->request->get('_password');
        $data = array();
        $repo = $app->getUserRepository();
        $viewuser = $repo->getByName($username);
        $repo->setPassword($viewuser, $newPassword);

        return $app->redirect($app['url_generator']->generate('admin_user_view', array('username' => $viewuser->getUsername())));
    }


    public function userUpdateEmailAction(Application $app, Request $request, $username)
    {
        $newEmail = $request->request->get('_email');
        $data = array();
        $repo = $app->getUserRepository();
        $viewuser = $repo->getByName($username);
        $repo->setEmail($viewuser, $newEmail);

        return $app->redirect($app['url_generator']->generate('admin_user_view', array('username' => $viewuser->getUsername())));
    }

    public function userUpdateDisplayNameAction(Application $app, Request $request, $username)
    {
        $newDisplayName = $request->request->get('_displayname');
        $data = array();
        $repo = $app->getUserRepository();
        $viewuser = $repo->getByName($username);
        $repo->setDisplayName($viewuser, $newDisplayName);

        return $app->redirect($app['url_generator']->generate('admin_user_view', array('username' => $viewuser->getUsername())));
    }

    public function logListAction(Application $app, Request $request)
    {
        $data = array();
        return new Response($app['twig']->render(
            'admin/log_list.html.twig',
            $data
        ));
    }

    public function groupListAction(Application $app, Request $request)
    {
        return new Response($app['twig']->render(
            'admin/group_list.html.twig',
            array(
                'groups' => $app->getGroupRepository()->getAll(),
            )
        ));
    }

    public function appListAction(Application $app, Request $request)
    {
        $data = array();
        $repo = $app->getAppRepository();
        $apps = $repo->getAll();
        $data['usercount'] = count($apps);
        $data['apps'] = $apps;

        return new Response($app['twig']->render(
            'admin/app_list.html.twig',
            $data
        ));
    }
}
