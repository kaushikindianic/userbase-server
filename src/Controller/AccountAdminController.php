<?php
namespace UserBase\Server\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Exception;
use UserBase\Server\Model\Account;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Form\FormError;
use Symfony\Component\Validator\Constraints as Assert;
use UserBase\Server\Model\Event;
use UserBase\Server\Model\Apikey;
use UserBase\Server\Model\AccountProperty;

class AccountAdminController
{
    
    public function accountListAction(Application $app, Request $request)
    {   
        $search = $request->request->get('searchText');
        $accounts = $app->getAccountRepository()->getAll(10, $search);
        return new Response($app['twig']->render('admin/account_list.html.twig', array(
            'accounts' => $accounts,
            'accountCount' => count($accounts),
            'searchText' => $search
        )));
    }
    
    public function accountAddAction(Application $app, Request $request)
    {
        return $this->accountEditForm($app, $request, null);
    }
    
    public function accountEditAction(Application $app, Request $request, $accountname)
    {
        return $this->accountEditForm($app, $request, $accountname);
    }
    
    public function accountDeleteAction(Application $app, Request $request, $accountname)
    {
        $repo = $app->getAccountRepository();
        $repo->delete($accountname);
 
        //--EVENT LOG --//
        $time = time();
        $sEventData = json_encode(array('accountname' => $accountname, 'time' => $time));
        
        $oEvent = new Event();
        $oEvent->setName($accountname);
        $oEvent->setEventName('account.delete');
        $oEvent->setOccuredAt($time);
        $oEvent->setData($sEventData);
        $oEvent->setAdminName( $request->getUser());
        
        $oEventRepo = $app->getEventRepository();
        $oEventRepo->add($oEvent);
        //-- END EVENT LOG --//        
        
        return $app->redirect($app['url_generator']->generate('admin_account_list'));
    }    
    
    public function accountUsersAction(Application $app, Request $request, $accountname)
    {
        $error = $request->query->get('error');
        $oAccRepo = $app->getAccountRepository();
    
        if ($request->isMethod('POST')) {
            $userName = $request->get('delAssignUser');
    
            if ($userName) {
                $oAccRepo->delAccUsers($accountname, $userName);

                //--EVENT LOG --//
                $time = time();
                $sEventData = json_encode(array('accountname' => $accountname, 'username' => $userName, 'time' => $time));
                
                $oEvent = new Event();
                $oEvent->setName($userName);
                $oEvent->setEventName('user.unlinktoaccount');
                $oEvent->setOccuredAt($time);
                $oEvent->setData($sEventData);
                $oEvent->setAdminName( $request->getUser());
                
                $oEventRepo = $app->getEventRepository();
                $oEventRepo->add($oEvent);
                //-- END EVENT LOG --//
                
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
        $searchUser = $request->get('searchAccUser');
        $oAccRepo = $app->getAccountRepository();
    
        if ($request->isMethod('POST')) {
            $userName = $request->get('userName');
            if ($userName) {
                $oAccRepo->addAccUser($accountname, $userName, 'group');

                //--EVENT LOG --//
                $time = time();
                $sEventData = json_encode(array('accountname' => $accountname, 'username' => $userName, 'time' => $time));
                
                $oEvent = new Event();
                $oEvent->setName($userName);
                $oEvent->setEventName('user.linktoaccount');
                $oEvent->setOccuredAt($time);
                $oEvent->setData($sEventData);
                $oEvent->setAdminName( $request->getUser());
                
                $oEventRepo = $app->getEventRepository();
                $oEventRepo->add($oEvent);
                //-- END EVENT LOG --//                
                
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
            );
        }
        $form = $app['form.factory']->createBuilder('form', $defaults)
        ->add('name', 'text', array(
            'required' => true,
            'read_only' => ($add)? false: true,
            'error_bubbling' => true,
            'attr' => array(
                'placeholder' => 'Name',
                (($add)? 'autofocus' : '' ) => '' ,
            )
        ))
        ->add('displayName', 'text', array(
            'required' => false,
            'label' => 'Display name',
            'attr' => array(
                'placeholder' => 'Display name',
                ((!$add)? 'autofocus' : '' ) => '' ,
            )
        ))
        ->add('email', 'email', array(
            'required' => true,
            'label' => 'E-mail',
            'trim' => true,
            'error_bubbling' => true,
            'constraints' => array(
                new Assert\NotBlank(array('message' => 'E-mail value should not be blank.')),
                new Assert\Email()
            ),
            'attr' => array(
                'placeholder' => 'E-mail',
                'class' => 'form-control'
            )
        ))
        ->add('url', 'url', array(
            'required' => false,
            'label' => 'URL',
            'error_bubbling' => true,
            'attr' => array(
                'placeholder' => 'URL',
                'class' => 'form-control'
            )
        ))
        ->add('about', 'text', array(
            'required' => false,
            'attr' => array(
                'placeholder' => 'About',
            )
        ))      
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
            ->setAccountType('organization');
    
            if ($add) {
                if (! $repo->add($account)) {
                    return $app->redirect($app['url_generator']->generate('admin_account_add', array(
                        'error' => 'Name exists'
                    )));
                }
                //-- ASSIGN MEMEBR TO USER --//
                $repo->addAccUser($data['name'], $request->getUser(), 1);
                
                //--EVENT LOG --//
                $time = time();
                $sEventData = json_encode(array('accountname' => $data['name'],'displayName' => $data['displayName'], 'time' => $time));
                
                $oEvent = new Event();
                $oEvent->setName($data['name']);
                $oEvent->setEventName('account.create');
                $oEvent->setOccuredAt($time);
                $oEvent->setData($sEventData);
                $oEvent->setAdminName( $request->getUser());
                
                $oEventRepo = $app->getEventRepository();
                $oEventRepo->add($oEvent);
                //-- END EVENT LOG --//                
            } else {
                $repo->update($account);
                
                //--EVENT LOG --//
                $time = time();
                $sEventData = json_encode(array('accountname' => $data['name'],'displayName' => $data['displayName'], 'time' => $time));
                
                $oEvent = new Event();
                $oEvent->setName($data['name']);
                $oEvent->setEventName('account.update');
                $oEvent->setOccuredAt($time);
                $oEvent->setData($sEventData);
                $oEvent->setAdminName( $request->getUser());
                
                $oEventRepo = $app->getEventRepository();
                $oEventRepo->add($oEvent);
                //-- END EVENT LOG --//
                
            }
    
            return $app->redirect($app['url_generator']->generate('admin_account_list'));
        }
    
        return new Response($app['twig']->render('admin/account_edit.html.twig', array(
            'form' => $form->createView(),
            'account' => $account,
            'error' => $error
        )));
    }
    
    public function accountViewAction(Application $app, Request $request, $accountname)
    {   
        $repo = $app->getAccountRepository();
        $accountPropertyRepository = $app->getAccountPropertyRepository();
        $account = $repo->getByName($accountname);
        // also support getting template by id
        if (! $account && is_numeric($accountname)) {
            $account = $repo->getById($accountname);
        }
        $oApiKeyRepo  = $app->getApikeyRepository();
        $aApikeys  = $oApiKeyRepo->getByAccountName($accountname);
        $accountProperties  = $accountPropertyRepository->getByAccountName($accountname);
        
        return new Response(
            $app['twig']->render(
                'admin/account_view.html.twig',
                array(
                    'account' => $account,
                    'aApikeys' => $aApikeys,
                    'accountProperties' => $accountProperties
                )
            )
        );
    }
    
    public function addApikeyAction(Application $app, Request $request, $accountname)
    { 
        return $this->apikeyForm($app, $request, $accountname, 0);
    }
    
    public function editApikeyAction(Application $app, Request $request, $accountname, $id)
    {
        return $this->apikeyForm($app, $request, $accountname, $id);
    }
        
    
    private function apikeyForm($app, $request, $accountname, $id)
    {
        $error = $request->query->get('error');
        $repo = $app->getAccountRepository();
        $oApiKeyRepo  = $app->getApikeyRepository();
        $add = false;
        
        $account = $repo->getByName($accountname);
        // also support getting template by id
        if (! $account && is_numeric($accountname)) {
            $account = $repo->getById($accountname);
        }

        if ($id) {
            if (!$aApikey = $oApiKeyRepo->getById($id)) {
                return $app->redirect($app['url_generator']->generate('admin_account_view', array(                       
                        'accountname' => $accountname
                 )));
            }
            $defaults = [
                'name' => $aApikey['name'],
                'username' =>  $aApikey['username'],
                'password' => $aApikey['password']
            ];
            $nameParam = array();
        } else {
            $defaults = null;
            $nameParam = array();
            $add = true;
        }
       
        $form = $app['form.factory']->createBuilder('form', $defaults)
        ->add('name', 'text', $nameParam)
        ->add('username', 'text', array('required' => false, 'label' => 'username'))
        ->add('password', 'password', array('required' => false, 'always_empty' => false ))
        ->getForm();
        
        // handle form submission
        $form->handleRequest($request);
        if ($form->isValid()) {
            $data = $form->getData();           
            $oApikeyModel = new Apikey($data['name']);
            
            if ($add) {
                $oApikeyModel->setName($data['name'])
                    ->setUserName($data['username'])
                    ->setPassword($data['password'])
                    ->setCreatedAt(date('Y-m-d H:i:s'))
                    ->setAccountName($accountname);
            } else {
                $oApikeyModel->setId($id)
                    ->setName($data['name'])
                    ->setUserName($data['username'])
                    ->setPassword((empty($data['password'])?$defaults['password'] :$data['password']))
                    ->setAccountName($accountname);
            }        
            if ($add) {
                if (! $oApiKeyRepo->add($oApikeyModel)) {
                    return $app->redirect($app['url_generator']->generate('admin_account_view', array(
                        'error' => 'Failed adding Apikey',
                        'accountname' => $accountname
                    )));
                }
            } else {
                $oApiKeyRepo->update($oApikeyModel);
            }
            return $app->redirect($app['url_generator']->generate('admin_account_view',array(
                'accountname' => $accountname
            )));
        }
        return new Response($app['twig']->render('admin/account_apikey_add.html.twig', array(
            'form' => $form->createView(),
            'account' => $account,
            'add' => $add,
            'error' => $error
        )));        
    }
    
    public function apikeysAction(Application $app, Request $request)
    {
        $oApiKeyRepo  = $app->getApikeyRepository();
        $aApikeys  = $oApiKeyRepo->getAll();
        
        return new Response($app['twig']->render('admin/account_apikey_list.html.twig', array(
            'account' => $account,
            'aApikeys' => $aApikeys
        )));        
    }

    public function addPropertyAction(Application $app, Request $request, $accountname)
    {
        $accountPropertyRepository = $app->getAccountPropertyRepository();
        $property = new AccountProperty();
        $property->setAccountName($accountname);
        $property->setName($request->request->get('property_name'));
        $property->setValue($request->request->get('property_value'));
        $accountPropertyRepository->add($property);
        return $app->redirect($app['url_generator']->generate('admin_account_view', ['accountname' => $accountname]));
    }
    
    public function deletePropertyAction(Application $app, Request $request, $accountname, $propertyId)
    {
        $accountPropertyRepository = $app->getAccountPropertyRepository();
        $property = $accountPropertyRepository->find($propertyId);
        $accountPropertyRepository->delete($property);
        return $app->redirect($app['url_generator']->generate('admin_account_view', ['accountname' => $accountname]));
    }
}
