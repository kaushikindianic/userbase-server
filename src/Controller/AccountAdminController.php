<?php
namespace UserBase\Server\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Exception;
use UserBase\Server\Model\Account;
use Symfony\Component\HttpFoundation\JsonResponse;

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