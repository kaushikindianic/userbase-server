<?php

namespace UserBase\Server\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Form\FormError;
use Symfony\Component\Validator\Constraints as Assert;
use Exception;
use JWT;

class PortalController
{

    public function indexAction(Application $app, Request $request)
    {
        $data = array();
        
        $user = $app['currentuser'];
        $accountRepo = $app->getAccountRepository();
        $data['accounts'] = $accountRepo->getByUsername($user->getName());

        return new Response($app['twig']->render(
            'portal/index.html.twig',
            $data
        ));
    }
    
    public function viewAction(Application $app, Request $request, $accountname)
    {   
        $user = $app['currentuser'];
        $accountRepo = $app->getAccountRepository();
        $oAccount = $accountRepo->getByName($accountname);
        
        // -- GENERATE FORM --//
        $form = $app['form.factory']->createBuilder('form')
        ->add('picture', 'file', array(
            'required' => true,
            'read_only' => false,
            'label' => false,
            'trim' => true,
            'error_bubbling' => true,
            'multiple' => false,
            'constraints' => array(
                 new Assert\Image(array(
                     'minWidth' => 80,
                     'maxWidth' => 600,
                     'minHeight' => 80,
                     'maxHeight' => 600,
                     //'mimeTypes' => array('image/jpeg', 'image/png', 'image/gif'),
                     'mimeTypesMessage' => 'Please upload a valid images',
                 )),
                new Assert\NotBlank(array('message' => 'upload picture file.'))
             ),
            'attr' => array(
            'id' => 'attachment',
            'placeholder' => 'upload Picture file',
           // 'class' => 'form-control',
            'autofocus' => '',
            )
        ))
        ->getForm();
        
        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            $formData = $form->getData();
            
            if ($form->isValid()) {
                $file = $form['picture']->getData(); 
                $dir = $app['picturePath'];
                $newName =  $accountname.'_'.$form['picture']->getData()->getClientOriginalName();
                
                $form['picture']->getData()->move($dir, $newName);
                $accountRepo->updatePicture($accountname, $newName);
                
                return $app->redirect($app['url_generator']->generate('portal_index', array()));
            }
        }
        return new Response($app['twig']->render('portal/picture.html.twig', 
            array(
                'form' => $form->createView(),
                'accountname' => $accountname,
                'oAccount' => $oAccount
            )
        ));
    }
    
    public function appLoginAction(Application $app, Request $request, $appname)
    {
        $user = $app['currentuser'];
        $accountRepo = $app->getAccountRepository();
        $account = $accountRepo->getByAppNameAndUsername($appname, $user->getName());

        $key = 'super_secret'; // TODO: make this configurable + support rsa
        $token = array(
            "iss" => 'userbase',
            "aud" => $appname,
            "iat" => time(),
            "exp" => time() + (60*10),
            "sub" => $user->getName(),
            "my_own_thing" => 'this_needs_to_be_something_sensible'
        );
        $jwt = JWT::encode($token, $key);
        
        $url = $account->getApp()->getBaseUrl();
        
        // TODO: The way of passing JWT's should be configurable per app
        $url .= '/login/jwt/' . $jwt;
        return $app->redirect($url);
        
        exit($url);
    }
}
