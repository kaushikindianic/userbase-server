<?php

namespace UserBase\Server\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Form\FormError;
use Symfony\Component\Validator\Constraints as Assert;
use UserBase\Server\Model\Account;
use Exception;
use JWT;
use UserBase\Server\Model\Space;

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

    public function pictureAction(Application $app, Request $request, $accountname)
    {

        $accountRepo = $app->getAccountRepository();
        $oAccount = $accountRepo->getByName($accountname);

        // -- GENERATE FORM --//
        $form = $app['form.factory']->createBuilder('form')
            ->add(
                'picture',
                'file',
                array(
                    'required' => true,
                    'read_only' => false,
                    'label' => false,
                    'trim' => true,
                    'error_bubbling' => true,
                    'multiple' => false,
                    'constraints' => array(
                        new Assert\Image(
                            array(
                                'minWidth' => 110,
                                'maxWidth' =>  800,
                                'minHeight' => 110,
                                'maxHeight' => 800,
                                //'mimeTypes' => array('image/jpeg', 'image/png', 'image/gif'),
                                'mimeTypesMessage' => 'Please upload a valid images',
                            )
                        ),
                        new Assert\NotBlank(
                            array(
                                'message' => 'upload picture file.'
                            )
                        )
                    ),
                    'attr' => array(
                        'id' => 'attachment',
                        'placeholder' => 'upload Picture file',
                        // 'class' => 'form-control',
                        'autofocus' => '',
                    )
                )
            )->getForm();

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            $formData = $form->getData();

            if ($form->isValid()) {
                $file = $form['picture']->getData();
                $dir = $app['picturePath'];
                $tmpDir =  $app['tmpDirPath'];
                $newName = $accountname.'.tmp.png';
                $newResizeName = $accountname.'.png';
                $newWidth = 100;
                $newHeight = 100;

                $tmpName =  $accountname.'.tmp.'.$form['picture']->getData()->getClientOriginalName();
                $form['picture']->getData()->move($tmpDir, $tmpName);

                //CONVERT IMAGE TO PNG //
                $imgPath =  $tmpDir.'/'.$tmpName;

                $info = new \SplFileInfo( $imgPath);

                switch (strtolower($info->getExtension())) {
                    case 'gif':
                            $img = @imagecreatefromgif($imgPath);
                        break;
                    case 'jpeg':
                    case 'jpg':
                        $img = @imagecreatefromjpeg($imgPath);
                        break;
                    case 'png':
                            $img = @imagecreatefrompng($imgPath);
                        break;
                }
                if ($img) {
                    list($width, $height) = getimagesize($imgPath);

                    $im = imagecreatetruecolor($width, $height);
                    $white = imagecolorallocate($im, 255, 255, 255);
                    imagefilledrectangle($im, 0, 0, $width, $height, $white);

                    //--resize image --//
                    $resizeImag = imagecreatetruecolor($newWidth, $newHeight);
                    imagecopyresized($resizeImag, $img, 0, 0, 0, 0,$newWidth, $newHeight, $width, $height);
                    imagepng($resizeImag, $dir.'/'.$newResizeName);
                    //---------//
                    imagecopy($im, $img, 0, 0, 0, 0, $width, $height);
                    imagepng($im, $dir.'/'.$newName);

                    return $app->redirect(
                        $app['url_generator']->generate(
                            'portal_cropimag',
                            array(
                                'accountname' => $accountname
                            )
                        )
                    );
                }
            }
        }
        
        return new Response(
            $app['twig']->render(
                'portal/account/picture.html.twig',
                array(
                    'form' => $form->createView(),
                    'accountname' => $accountname,
                    'oAccount' => $oAccount
                )
            )
        );
    }

    public function viewAction(Application $app, Request $request, $accountname)
    {
        $user = $app['currentuser'];
        $accountRepo = $app->getAccountRepository();
        $oAccount = $accountRepo->getByName($accountname);

        //-- GET ACCOUNT USER LIST --//
        $aAccUsers = $accountRepo->getAccountUsers($accountname);

        //--GET ACCOUNT SPACES --//
        $oSpaceRepo = $app->getSpaceRepository();
        $aSpaces = $oSpaceRepo->getAccountSpaces($accountname);

        return new Response(
            $app['twig']->render(
                'portal/account/view.html.twig',
                array(
                    'accountname' => $accountname,
                    'oAccount' => $oAccount,
                    'aAccUsers' => $aAccUsers,
                    'aSpaces' => $aSpaces
                )

            )
        );
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

    public function cropImageAction(Application $app, Request $request, $accountname)
    {
        $accountRepo = $app->getAccountRepository();
        $oAccount = $accountRepo->getByName($accountname);

        $dir = $app['picturePath'];
        $imgName = $accountname.'.tmp.png';
        $newResizePath = $dir.'/'.$accountname.'.png';
        $imgPath = $dir.'/'.$imgName;

        if ($request->isMethod('POST')) {
            $newWidth = 100;
            $newHeight = 100;
            $formData = array();
            $formData['x'] = $request->get('x');
            $formData['y'] = $request->get('y');
            $formData['w'] = $request->get('w');
            $formData['h'] = $request->get('h');

            $imgSrc = imagecreatefrompng($imgPath);
            $dstSrc = ImageCreateTrueColor( $newWidth, $newHeight);
            imagecolortransparent($dstSrc, imagecolorallocatealpha($dstSrc, 0, 0, 0, 127));
            imagealphablending($dstSrc, false);
            imagesavealpha($dstSrc, true);
            imagecopyresampled($dstSrc, $imgSrc,0,0, $formData['x'], $formData['y'], $newWidth,$newHeight, $formData['w'],$formData['h']);
            @imagepng($dstSrc, $newResizePath);

            return $app->redirect($app['url_generator']->generate('portal_index', array()));
        }
        return new Response($app['twig']->render('portal/picture-crop.html.twig',
            array(
                'accountname' => $accountname,
                'oAccount' => $oAccount,
                 'tmpImgPath' => '/'.$dir.'/'.$imgName
            )
         ));
    }

    public  function accountAddAction(Application $app, Request $request)
    {
       return $this->accountForm($app, $request, null );
    }

    public function  accountEditAction(Application $app, Request $request, $accountname)
    {
       return $this->accountForm($app, $request, $accountname );
    }
    
    public function accountMembersAction(Application $app, Request $request, $accountname)
    {
        $accountRepo = $app->getAccountRepository();
        $oAccount = $accountRepo->getByName($accountname);
        $aAccUsers = $accountRepo->getAccountMembers($accountname);
        
        return new Response($app['twig']->render('portal/account/members.html.twig', array(
            'accountname' => $accountname,
            'oAccount' => $oAccount,
            'aAccUsers' => $aAccUsers
        )));        
    }

    public function accountUserAddAction(Application $app, Request $request, $accountname)
    {
        $oAccRepo = $app->getAccountRepository();
        $oUserRepo = $app->getUserRepository();

        if ($request->isMethod('POST')) {
            $userName = $request->get('userName');

            if ($oUserRepo->getByName($userName)) {
                $oAccRepo->addAccUser($accountname, $userName, 'group');
            }
        }
        return $app->redirect($app['url_generator']->generate('protal_account_members', array(
            'accountname' => $accountname
        )));
    }

    private function accountForm(Application $app, Request $request, $accountname)
    {
        $error = $request->query->get('error');
        $repo = $app->getAccountRepository();
        $user = $app['currentuser'];
        $add = false;

        if (!empty($accountname)) {

            //CHECK USER ASSING TO ACCOUNT
            if (!$repo->userAssignToAccount($accountname, $user->getName())) {
                return $app->redirect($app['url_generator']->generate('portal_view', array(
                    'accountname' => $accountname
                )));
            }
            $account = $repo->getByName($accountname);
            // also support getting template by id
            if (! $account && is_numeric($accountname)) {
                $account = $repo->getById($accountname);
            }

            $defaults = array(
                'name' => $account->getName(),
                'displayName' => $account->getRawDisplayName(),
                'about' => $account->getAbout(),
            );
            $nameParam = array(
                'read_only' => true
            );
        } else {
            $add = true;
            $defaults = array();
            $nameParam = array();
            $account = array();
        }

        $form = $app['form.factory']->createBuilder('form', $defaults)
            ->add('name', 'text',  $nameParam)
            ->add('displayName', 'text', array('required' => false, 'label' => 'Display name'))
            ->add('about', 'text', array('required' => false))
            ->getForm();

        // handle form submission
        $form->handleRequest($request);
        if ($form->isValid()) {
            $data = $form->getData();
            $oAccModel = new Account( (($add)? $data['name'] : $accountname));

            if ($add) {
                $oAccModel->setDisplayName($data['displayName'])
                    ->setAbout($data['about'])
                    ->setAccountType('organization');

               if (! $repo->add($oAccModel)) {
                   $error = 'Name exists';
                   return $app->redirect($app['url_generator']->generate('portal_add', array(
                       'error' => 'Name exists'
                   )));

               } else {
                   //-- ASSIGN USER
                   $repo->addAccUser($data['name'], $user->getName(), 'user');
                   return $app->redirect($app['url_generator']->generate('portal_index'));
               }

            } else {
                $oAccModel->setDisplayName($data['displayName'])
                    ->setAbout($data['about'])
                    ->setAccountType($account->getAccountType());
                $repo->update($oAccModel);
            }

            return $app->redirect($app['url_generator']->generate('portal_index'));
        }

        return new Response($app['twig']->render('portal/account_edit.html.twig', array(
            'form' => $form->createView(),
            'account' => $account,
            'error' => $error,
            'add' => $add
        )));
    }

    public function addSpaceAction(Application $app, Request $request, $accountname)
    {
        return $this->spaceFormAction($app, $request, $accountname, 0);
    }

    public function editSpaceAction(Application $app, Request $request,$id)
    {
        return $this->spaceFormAction($app, $request, null, $id);
    }

    private function spaceFormAction($app, $request, $accountname, $id)
    {
        $error = $request->query->get('error');
        $oSpaceRepo = $app->getSpaceRepository();
        $add = false;

        if ($id) {
            if (!$aSpace = $oSpaceRepo->getById($id)) {
                return $app->redirect($app['url_generator']->generate('portal'));
            }
            $defaults['name'] = $aSpace['name'];
            $defaults['description'] = $aSpace['description'];
            $accountname = $aSpace['account_name'];
        } else {
            $nameParam = array();
            $add = true;
            $defaults = array();
        }

        $form = $app['form.factory']->createBuilder('form', $defaults)
            ->add('name', 'text', array(
                'required' => true,
                'label' => 'name',
                'read_only' => ($add)? false : true,
                'trim' => true,
                'constraints' =>  new Assert\NotBlank(array('message' => 'Name value should not be blank.')),

            ))
            ->add('description', 'textarea', array('required' => false, 'label' => 'Description'))
            ->getForm();

        // -- HANDAL FORM SUBMIT --//
        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            $formData = $form->getData();

            //CHECK NAME EXIST
            if ($oSpaceRepo->checkExist($formData['name'], $accountname, $id)) {
                $form->get('name')->addError(new FormError('Name already exist'));
            }
            if ($form->isValid()) {
                $oSpaceModel = new Space();

                if ($add) {
                    $oSpaceModel->setAccountName($accountname)
                        ->setName($formData['name'])
                        ->setDescription($formData['description']);

                    if (!$oSpaceRepo->add($oSpaceModel)) {
                        return $app->redirect($app['url_generator']->generate('portal_view', array(
                            'accountname' => $accountname,
                            'error' => 'Failed adding Space'
                        )));
                    }
                    return $app->redirect($app['url_generator']->generate('portal_view', array(
                        'accountname' => $accountname,
                    )));
                } else {
                    $oSpaceModel->setId($id)
                        ->setDescription($formData['description']);
                    $oSpaceRepo->update($oSpaceModel);

                    return $app->redirect($app['url_generator']->generate('portal_spaces_view', array(
                        'id' => $id,
                    )));
                }
            }
        }
        return new Response($app['twig']->render('portal/space_edit.html.twig', array(
            'form' => $form->createView(),
            'error' => $error,
            'add' => $add
        )));
    }

    public function spaceViewAction(Application $app, Request $request, $id)
    {
        $oSpaceRepo = $app->getSpaceRepository();
        $aSpace = $oSpaceRepo->getById($id);

        return new Response($app['twig']->render('portal/space_view.html.twig', array(
                'aSpace' => $aSpace
            )
        ));
    }

    public function deleteSpaceAction(Application $app, Request $request, $id)
    {
        $oSpaceRepo = $app->getSpaceRepository();
        $accountRepo = $app->getAccountRepository();
        $aSpace = $oSpaceRepo->getById($id);
        $user = $app['currentuser'];

        if ($accountRepo->userAssignToAccount($aSpace['account_name'], $user->getName())) {
            $oSpaceRepo->delete($id);
        } else {
            return $app->redirect($app['url_generator']->generate('portal'));
        }

        return $app->redirect($app['url_generator']->generate('portal_view', array(
            'accountname' => $aSpace['account_name']
        )));
    }
}
