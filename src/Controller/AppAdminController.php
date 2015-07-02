<?php
namespace UserBase\Server\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Exception;
use UserBase\Server\Model\App;
use Symfony\Component\HttpFoundation\JsonResponse;


class AppAdminController
{

    public function appListAction(Application $app, Request $request)
    {
        $data = array();
        $repo = $app->getAppRepository();
        $apps = $repo->getAll();
        $data['usercount'] = count($apps);
        $data['apps'] = $apps;
    
        return new Response($app['twig']->render('admin/app_list.html.twig', $data));
    }
    
    public function appAddAction(Application $app, Request $request)
    {
        return $this->appsEditForm($app, $request, null);
    }
    
    public function appEditAction(Application $app, Request $request, $appname)
    {
        return $this->appsEditForm($app, $request, $appname);
    }
    
    public function appDeleteAction(Application $app, Request $request, $appname)
    {
        $appRepo = $app->getAppRepository();
        $appRepo->delete($appname);
    
        return $app->redirect($app['url_generator']->generate('admin_apps_list'));
    }
    
    public function appViewAction(Application $app, Request $request, $appname)
    {
        $data = array();
        $appRepo = $app->getAppRepository();
        $viewapp = $appRepo->getByName($appname);
        $data['viewapp'] = $viewapp;
    
        return new Response($app['twig']->render('admin/app_view.html.twig', $data));
    }
    
    public function appUsersAction(Application $app, Request $request, $appname)
    {
        $error = $request->query->get('error');
        $oAppRepo  = $app->getAppRepository();
    
        if ($request->isMethod('POST')) {
            $userName = $request->get('delAssignUser');
    
            if ($userName) {
                $oAppRepo->delAppUser($appname, $userName);
    
                return $app->redirect($app['url_generator']->generate('admin_app_users', array(
                    'appname' => $appname
                )));
            }
        }
        $aAppUsers = $oAppRepo->getAppUsers($appname);
    
        return new Response($app['twig']->render('admin/app_users.html.twig', array(
            'appName' => $appname,
            'aAppUsers' => $aAppUsers,
            'error' => $error
        )));
    }
    
    public function appSearchUserAction(Application $app, Request $request, $appname)
    {
        $searchUser = $request->get('searchAppUser');
        $oAppRepo = $app->getAppRepository();
    
        if ($request->isMethod('POST')) {
            $userName = $request->get('userName');
            if ($userName) {
                $oAppRepo->addAppUser($appname, $userName);
                return new JsonResponse(array(
                    'success' => true
                ));
            }
        }
        $oUserRepo = $app->getUserRepository();
        $aUsers = $oUserRepo->getSearchUsers($searchUser);
    
        $oRes = new Response($app['twig']->render('admin/app_search_users.html.twig', array(
            'aUsers' => $aUsers
        )));
    
        return new JsonResponse(array(
            'html' => $oRes->getContent()
        ));
    }
    
    
    private function appsEditForm(Application $app, Request $request, $appname)
    {
        $error = $request->query->get('error');
        $repo = $app->getAppRepository();
        $add = false;
        $oApp = $repo->getByName($appname);
    
        if (!  $oApp && is_numeric($appname)) {
            $oApp = $repo->getById($appname);
        }
    
        if ( $oApp === null) {
            $defaults = null;
            $nameParam = array();
            $add = true;
        } else {
            $defaults = array(
                'name' =>  $oApp->getName(),
                'displayName' =>  $oApp->getDisplayName(),
                'about' =>  $oApp->getAbout(),
                'pictureUrl' =>  $oApp->getPictureUrl(),
                'baseUrl' =>  $oApp->getBaseUrl(),
                'createdAt' =>  $oApp->getCreatedAt(),
                'deletedAt' =>  $oApp->getDeletedAt()
            );
            $nameParam = array(
                'read_only' => true
            );
        }
    
        $form = $app['form.factory']->createBuilder('form', $defaults)
        ->add('name', 'text', $nameParam)
        ->add('displayName', 'text', array('required' => false, 'label' => 'Display name'))
        ->add('about', 'text', array('required' => false))
        ->add('pictureUrl', 'url', array('required' => false, 'label' => 'Picture URL'))
        ->add('baseUrl', 'url', array('required' => false,'label' => 'Baseurl URL'))
        ->getForm();
    
        // handle form submission
        $form->handleRequest($request);
        if ($form->isValid()) {
            $data = $form->getData();
    
            if ($add) {
                $oApp = new App();
            }
            $oApp->setName($data['name']);
            $oApp->setDisplayName($data['displayName']);
            $oApp->setAbout($data['about']);
            $oApp->setPictureUrl($data['pictureUrl']);
            $oApp->setBaseUrl($data['baseUrl']);
    
            if ($add) {
                if (! $repo->add($oApp)) {
                    return $app->redirect($app['url_generator']->generate('admin_app_add', array(
                        'error' => 'Name exists'
                    )));
                }
            } else {
                $repo->update($oApp);
            }
    
            return $app->redirect($app['url_generator']->generate('admin_apps_list'));
        }
    
        return new Response($app['twig']->render('admin/app_edit.html.twig', array(
            'form' => $form->createView(),
            'apps' => $oApp,
            'error' => $error
        )));
    }    
}