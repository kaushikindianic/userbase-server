<?php
namespace UserBase\Server\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\FormError;
use UserBase\Server\Model\Event;
use UserBase\Server\Model\Account;

class UserAdminController
{
    public function userListAction(Application $app, Request $request)
    {
        $search = $request->request->get('searchText');
        $data = array();
        $repo = $app->getUserRepository();
        $users = $repo->getAll(10, $search);
        $data['usercount'] = count($users);
        $data['users'] = $users;
        $data['searchText'] = $search;
        return new Response($app['twig']->render('admin/user_list.html.twig', $data));
    }

    public function userViewAction(Application $app, Request $request, $username)
    {
        $data = array();
        $userRepo = $app->getUserRepository();
        $oAccRepo = $app->getAccountRepository();
        $oAppRepo  = $app->getAppRepository();
        $oIdentRepo = $app->getIdentityRepository();
        $oEventRepo = $app->getEventRepository();

        $viewuser = $userRepo->getByName($username);
        $aUserAccounts = $oAccRepo->getByUserName($username);
        $aUserApps =  $oAppRepo->getByUserName($username);
        $aUserIdentities = $oIdentRepo->getByUserName($username);

        $aUserEvents = $oEventRepo->getUserEvents($username);

        return new Response($app['twig']->render(
            'admin/user_view.html.twig',
            array(
                'username' => $username,
                'viewuser' => $viewuser,
                'aUserAccounts' => $aUserAccounts,
                'aUserApps' => $aUserApps,
                'aUserIdentities' => $aUserIdentities,
                'aUserEvents' => $aUserEvents
            )
        ));
    }

    public function userAddAction(Application $app, Request $request)
    {
        $oUserRepo = $app->getUserRepository();
        $error = $request->query->get('error');

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
            $data['username'] =$formData['_username'];
            $app['mailer']->sendTemplate('welcome', $user, $data);

            //--CREATE PERSONAL ACCOUNT--//
            $oAccunt = new Account($formData['_username']);
            $oAccunt->setDisplayName($formData['_username'])
                    ->setAbout('Personal account')
                    ->setPictureUrl('')
                    ->setAccountType('user')
                    ;

            $oAccRepo = $app->getAccountRepository();
            if ($oAccRepo->add($oAccunt)) {
                $oAccRepo->addAccUser($formData['_username'], $formData['_username'], 'user');

                //--EVENT LOG --//
                $time = time();
                $sEventData = json_encode(array(
                    'accountname' => $formData['_username'],
                    'displayName' => $formData['_username'],
                    'time' => $time
                ));

                $oEvent = new Event();
                $oEvent->setName($formData['_username']);
                $oEvent->setEventName('account.create');
                $oEvent->setOccuredAt($time);
                $oEvent->setData($sEventData);
                $oEvent->setAdminName($request->getUser());

                $oEventRepo = $app->getEventRepository();
                $oEventRepo->add($oEvent);
                //-- END EVENT LOG --//

                //--EVENT LOG --//
                $time = time();
                $sEventData = json_encode(array(
                    'accountname' => $formData['_username'],
                    'username' =>$formData['_username'],
                    'time' => $time
                ));

                $oEvent = new Event();
                $oEvent->setName($formData['_username']);
                $oEvent->setEventName('user.linktoaccount');
                $oEvent->setOccuredAt($time);
                $oEvent->setData($sEventData);
                $oEvent->setAdminName($request->getUser());

                $oEventRepo = $app->getEventRepository();
                $oEventRepo->add($oEvent);
                //-- END EVENT LOG --//
            }

            //--EVENT LOG --//
            $time = time();
            $sEventData = json_encode(array(
                'username' => $formData['_username'],
                'email' => $formData['_email'],
                 'time' => $time
            ));

            $oEvent = new Event();
            $oEvent->setName($formData['_username']);
            $oEvent->setEventName('user.create');
            $oEvent->setOccuredAt($time);
            $oEvent->setData($sEventData);
            $oEvent->setAdminName($request->getUser());

            $oEventRepo = $app->getEventRepository();
            $oEventRepo->add($oEvent);
            //-- END EVENT LOG --//

            return $app->redirect($app['url_generator']->generate('admin_user_list'));
        }

        return new Response($app['twig']->render(
            'admin/user_add.html.twig',
            array(
                'error' => $error,
                'form' => $form->createView()
            )
        ));
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

        //--EVENT LOG --//
        $time = time();
        $sEventData = json_encode(array(
            'username' => $viewuser->getName(),
            'email' => $viewuser->getEmail(),
            'time' => $time
        ));

        $oEvent = new Event();
        $oEvent->setName($viewuser->getName());
        $oEvent->setEventName('user.update.password');
        $oEvent->setOccuredAt($time);
        $oEvent->setData($sEventData);
        $oEvent->setAdminName($request->getUser());

        $oEventRepo = $app->getEventRepository();
        $oEventRepo->add($oEvent);
        //-- END EVENT LOG --//

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

        //--EVENT LOG --//
        $time = time();
        $sEventData = json_encode(array(
            'username' => $viewuser->getName(),
            'email' => $viewuser->getEmail(),
            'newEmail' => $newEmail,
            'time' => $time
        ));

        $oEvent = new Event();
        $oEvent->setName($viewuser->getName());
        $oEvent->setEventName('user.update.email');
        $oEvent->setOccuredAt($time);
        $oEvent->setData($sEventData);
        $oEvent->setAdminName($request->getUser());

        $oEventRepo = $app->getEventRepository();
        $oEventRepo->add($oEvent);
        //-- END EVENT LOG --//

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

        //--EVENT LOG --//
        $time = time();
        $sEventData = json_encode(array(
            'username' => $viewuser->getName(),
            'email' => $viewuser->getEmail(),
            'newDisplayName' => $newDisplayName,
            'time' => $time
        ));

        $oEvent = new Event();
        $oEvent->setName($viewuser->getName());
        $oEvent->setEventName('user.update.displayname');
        $oEvent->setOccuredAt($time);
        $oEvent->setData($sEventData);
        $oEvent->setAdminName($request->getUser());

        $oEventRepo = $app->getEventRepository();
        $oEventRepo->add($oEvent);
        //-- END EVENT LOG --//

        return $app->redirect($app['url_generator']->generate('admin_user_view', array(
            'username' => $viewuser->getUsername()
        )));
    }
}
