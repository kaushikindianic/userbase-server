<?php
namespace UserBase\Server\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Exception;
use UserBase\Server\Model\App;
use UserBase\Server\Model\Account;
use Symfony\Component\HttpFoundation\JsonResponse;

class AdminController
{

    public function indexAction(Application $app, Request $request)
    {
        $data = array();
        return new Response($app['twig']->render('admin/index.html.twig', $data));
    }

    public function userListAction(Application $app, Request $request)
    {
        $data = array();
        $repo = $app->getUserRepository();
        $users = $repo->getAll();
        $data['usercount'] = count($users);
        $data['users'] = $users;
        return new Response($app['twig']->render('admin/user_list.html.twig', $data));
    }

    public function userViewAction(Application $app, Request $request, $username)
    {
        $data = array();
        $userRepo = $app->getUserRepository();
        $accountRepo = $app->getAccountRepository();
        
        $viewuser = $userRepo->getByName($username);
        $data['username'] = $username;
        $data['viewuser'] = $viewuser;
        return new Response($app['twig']->render('admin/user_view.html.twig', $data));
    }

    public function userToolsAction(Application $app, Request $request, $username)
    {
        $data = array();
        $repo = $app->getUserRepository();
        $viewuser = $repo->getByName($username);
        $data['username'] = $username;
        $data['viewuser'] = $viewuser;
        return new Response($app['twig']->render('admin/user_tools.html.twig', $data));
    }

    public function userUpdatePasswordAction(Application $app, Request $request, $username)
    {
        $newPassword = $request->request->get('_password');
        $data = array();
        $repo = $app->getUserRepository();
        $viewuser = $repo->getByName($username);
        $repo->setPassword($viewuser, $newPassword);
        
        return $app->redirect($app['url_generator']->generate('admin_user_view', array(
            'username' => $viewuser->getUsername()
        )));
    }

    public function userUpdateEmailAction(Application $app, Request $request, $username)
    {
        $newEmail = $request->request->get('_email');
        $data = array();
        $repo = $app->getUserRepository();
        $viewuser = $repo->getByName($username);
        $repo->setEmail($viewuser, $newEmail);
        
        return $app->redirect($app['url_generator']->generate('admin_user_view', array(
            'username' => $viewuser->getUsername()
        )));
    }

    public function userUpdateDisplayNameAction(Application $app, Request $request, $username)
    {
        $newDisplayName = $request->request->get('_displayname');
        $data = array();
        $repo = $app->getUserRepository();
        $viewuser = $repo->getByName($username);
        $repo->setDisplayName($viewuser, $newDisplayName);
        
        return $app->redirect($app['url_generator']->generate('admin_user_view', array(
            'username' => $viewuser->getUsername()
        )));
    }

    public function logListAction(Application $app, Request $request)
    {
        $data = array();
        return new Response($app['twig']->render('admin/log_list.html.twig', $data));
    }

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
        $oUserRepo  = $app->getUserRepository();
        $aUsers = $oUserRepo->getAll();
        $tmpUsers = array();
        
        foreach ($aUsers AS $user) {
            $tmpUsers[$user->name] = $user->name;
        }
        $oAppRepo  = $app->getAppRepository();
        $aAppUsers = $oAppRepo->getAppUsers($appname);
        
        $form = $app['form.factory']->createBuilder('form')
        ->add('users', 'choice', array(
            'choices' => $tmpUsers,
            'multiple' => true,
            'expanded' => true,
            'data' => $aAppUsers
        ))->getForm();
        
        // FORM POST
        $form->handleRequest($request);
        if ($form->isValid()) {
            $data = $form->getData();
        
            if (!empty($data['users'])) {
        
                $oAppRepo->delAppUsers($appname);
        
                foreach ($data['users'] as  $key => $val ) {
                    $oAppRepo->addAppUser($appname, $val);
                }
            }
            return $app->redirect($app['url_generator']->generate('admin_apps_list'));
        }
        
        return new Response($app['twig']->render('admin/app_users.html.twig', array(
            'form' => $form->createView(),
            'appname' => $appname,
            //     'aUsers' => $aUsers,
            //     'aAccUsers' => $aAccUsers,
            'error' => $error
        )));        
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
  
    public function accountListAction(Application $app, Request $request)
    { 
        $accounts = $app->getAccountRepository()->getAll(); 
        return new Response($app['twig']->render('admin/account_list.html.twig', array(
            'accounts' => $accounts,
            'accountCount' => count($accounts)
        )));
    }
    
    public function accountDeleteAction(Application $app, Request $request, $accountname)
    {
        $repo = $app->getAccountRepository();
        $repo->delete($accountname);
    
        return $app->redirect($app['url_generator']->generate('admin_account_list'));
    }
    public function accountAddAction(Application $app, Request $request)
    {
        return $this->accountEditForm($app, $request, null);
    }

    public function accountEditAction(Application $app, Request $request, $accountname)
    {
        return $this->accountEditForm($app, $request, $accountname);
    }

    public function accountUsersAction(Application $app, Request $request, $accountname)
    {
        $error = $request->query->get('error');
        $oAccRepo = $app->getAccountRepository();
        
        if ($request->isMethod('POST')) {
            $userName = $request->get('delAssignUser');
            
            if ($userName) {
                $oAccRepo->delAccUsers($accountname, $userName);
                
                return $app->redirect($app['url_generator']->generate('admin_account_users', array(
                    'accountname' => $accountname
                )));
            }
        }
        $aAccUsers = $oAccRepo->getAccountUsers($accountname);
        
        return new Response($app['twig']->render('admin/account_users.html.twig', array(
            'accountName' => $accountname,
            'aAccUsers' => $aAccUsers,
            'error' => $error
        )));
    }

    public function accountSearchUserAction(Application $app, Request $request, $accountname)
    {
        $searchUser = $request->get('searchUser');
        $oAccRepo = $app->getAccountRepository();
        
        if ($request->isMethod('POST')) {
            $userName = $request->get('userName');
            if ($userName) {
                $oAccRepo->addAccUser($accountname, $userName);
                return new JsonResponse(array(
                    'success' => true
                ));
            }
        }
        $oUserRepo = $app->getUserRepository();
        $aUsers = $oUserRepo->getSearchUsers($searchUser);
        
        $oRes = new Response($app['twig']->render('admin/account_search_users.html.twig', array(
            'aUsers' => $aUsers
        )));
        
        return new JsonResponse(array(
            'html' => $oRes->getContent()
        ));
    }
    
    private function accountEditForm(Application $app, Request $request, $accountname)
    {
        $error = $request->query->get('error');
        $repo = $app->getAccountRepository();
        $add = false;
        
        $account = $repo->getByName($accountname);
        // also support getting template by id
        if (! $account && is_numeric($accountname)) {
            $account = $repo->getById($accountname);
        }
        
        if ($account === null) {
            $defaults = null;
            $nameParam = array();
            $add = true;
        } else {
            $defaults = array(
                'name' => $account->getName(),
                'displayName' => $account->getRawDisplayName(),
                'about' => $account->getAbout(),
                'pictureUrl' => $account->getPictureUrl()
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
                ->getForm();
        
        // handle form submission
        $form->handleRequest($request);
        if ($form->isValid()) {
            $data = $form->getData();
            
            if ($add) {
                $account = new Account($data['name']);
            }
            
            $account->setDisplayName($data['displayName'])
                    ->setAbout($data['about'])
                   ->setPictureUrl($data['pictureUrl']);
            
            if ($add) {
                if (! $repo->add($account)) {
                    return $app->redirect($app['url_generator']->generate('admin_account_add', array(
                        'error' => 'Name exists'
                    )));
                }
            } else {
                $repo->update($account);
            }
            
            return $app->redirect($app['url_generator']->generate('admin_account_list'));
        }
        
        return new Response($app['twig']->render('admin/account_edit.html.twig', array(
            'form' => $form->createView(),
            'account' => $account,
            'error' => $error
        )));
    }


}
