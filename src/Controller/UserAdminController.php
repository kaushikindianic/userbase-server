<?php
namespace UserBase\Server\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\FormError;

class UserAdminController
{
    
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
    
    public function userAddAction(Application $app, Request $request)
    {
        $oUserRepo = $app->getUserRepository();
        $error = $request->query->get('error');
    
        /*
         if ($request->isMethod('POST')) {
         $username = $request->request->get('_username');
         $email = $request->request->get('_email');
         $password = $request->request->get('_password');
         $password2 = $request->request->get('_password2');
    
    
         if ($oUserRepo->getByName($username)) {
         $error .= 'username already exist'.'<br/>';
         }
    
         if ($password != $password2) {
         $error .= 'Password not match.';
    
         }
         if (!$error) {
         $user = $oUserRepo->register($app, $username, $email);
         $user = $oUserRepo->getByName($username);
         $oUserRepo->setPassword($user, $password);
    
         $baseUrl = $app['userbase.baseurl'];
    
         $stamp = time();
         $validatetoken = sha1($stamp . ':' . $user->getEmail() . ':' . $app['userbase.salt']);
         $link = $baseUrl . '/validate/' . $user->getUsername() . '/' . $stamp . '/' . $validatetoken;
         $data = array();
         $data['link'] = $link;
         $data['username'] = $username;
         $app['mailer']->sendTemplate('welcome', $user, $data);
    
         return $app->redirect($app['url_generator']->generate('admin_user_list'));
         }
         }
         */
    
        // GENERATE FORM
        $form = $app['form.factory']->createBuilder('form')
            ->add('_username', 'text', array(
            'required' => true,
            'label' => false,
            'trim' => true,
            'error_bubbling' => true,
            'constraints' =>  new Assert\NotBlank(array('message' => 'Username value should not be blank.')),
            'attr' => array(
            'id' => '_username',
            'placeholder' => 'Username',
            'class' => 'form-control'
                )
            ))
            ->add('_email', 'email', array(
            'required' => true,
            'label' => false,
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
            ->add('_password', 'password', array(
            'required' => true,
            'label' => false,
            'error_bubbling' => true,
            'constraints' => new Assert\NotBlank(array('message' => 'Password value should not be blank.')),
            'attr' => array(
            'placeholder' => 'Password',
            'class' => 'form-control'
                )
            ))
            ->add('_password2', 'password', array(
            'required' => true,
            'label' => false,
            'error_bubbling' => true,
            'constraints' =>  new Assert\NotBlank(array('message' => 'Password (repeat) value should not be blank.')),
            'attr' => array(
            'placeholder' => 'Password (repeat)',
            'class' => 'form-control'
                )
            ))
            ->getForm();
         
        //-- HANDAL FORM SUBMIT --//
        $form->handleRequest($request);
    
        $formData = $form->getData();
    
        if ($oUserRepo->getByName($formData['_username'])) {
            $form->get('_username')->addError(new FormError('username already exist'));
        }
    
        if ($formData['_password'] != $formData['_password2']) {
            $form->get('_password')->addError(new FormError('Password not match.'));
        }
    
        if ($form->isValid()) {
    
            $user = $oUserRepo->register($app, $formData['_username'], $formData['_email']);
            $user = $oUserRepo->getByName($formData['_username']);
            $oUserRepo->setPassword($user, $formData['_password']);
    
            $baseUrl = $app['userbase.baseurl'];
    
            $stamp = time();
            $validatetoken = sha1($stamp . ':' . $user->getEmail() . ':' . $app['userbase.salt']);
            $link = $baseUrl . '/validate/' . $user->getUsername() . '/' . $stamp . '/' . $validatetoken;
            $data = array();
            $data['link'] = $link;
            $data['username'] = $username;
            $app['mailer']->sendTemplate('welcome', $user, $data);
    
            return $app->redirect($app['url_generator']->generate('admin_user_list'));
        }
    
        return new Response($app['twig']->render('admin/user_add.html.twig',
            array(
                'error' => $error,
                'form' => $form->createView()
            )));
    }
    
    public function chkUserNameAction(Application $app, Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $username =  $request->request->get('username');
            $oUserRepo = $app->getUserRepository();
    
            if ($oUserRepo->getByName($username)) {
                return new JsonResponse(array(
                    'success' => false,
                    'html' => 'username already exist'
                ));
            } else {
                return new JsonResponse(array(
                    'success' => true,
                    'html' => null
                ));
            }
    
        } else {
            return $app->redirect($app['url_generator']->generate('admin_user_list'));
        }
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
    
}