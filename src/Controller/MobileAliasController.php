<?php
namespace UserBase\Server\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\FormError;
use Symfony\Component\Validator\Constraints as Assert;
use UserBase\Server\Model\MobileAlias;
use Exception;

class MobileAliasController
{
    public function indexAction(Application $app, Request $request)
    {
        $error = $request->query->get('error');
        $oMobileAliasRepo = $app->getMobileAliasRepository();
        $entities = $oMobileAliasRepo->findAll();

        return new Response($app['twig']->render('mobile_alias/index.html.twig', array(
            'error' => $error,
            'entities' => $entities
        )));
    }

    public function deleteAction(Application $app, Request $request, $id)
    {
        $oMobileAliasRepo = $app->getMobileAliasRepository();

        $oMobileAliasRepo->remove($id);
        return $app->redirect($app['url_generator']->generate('mobile_alias_index', array()));
    }

    public function addAction(Application $app, Request $request)
    {
        return $this->getEditForm($app, $request, null);
    }

    public function editAction(Application $app, Request $request, $id)
    {
        return $this->getEditForm($app, $request, $id);
    }

    protected function getEditForm(Application $app, Request $request, $id)
    {
        $error = $request->query->get('error');
        $oMobileAliasRepo = $app->getMobileAliasRepository();
        $add = ($id)? false : true;
        $defaults = array();

        if ($id) {
            if (!$oMobileAlias = $oMobileAliasRepo->getById($id)) {
                return $app->redirect($app['url_generator']->generate('admin_blacklist_index'));
            }
            $defaults = ['mobile' => $oMobileAlias['mobile'],
                        'mobile_alias' => $oMobileAlias['mobile_alias'],
                        'description'=> $oMobileAlias['description']
                    ];
        }

        // GENERATE FORM --//
        $form = $app['form.factory']->createBuilder('form', $defaults)
            ->add('mobile', 'text', array(
                'required' => true,
                'label' => 'Virtual mobile',
                'trim' => true,
                'constraints' =>  new Assert\NotBlank(array('message' => 'Virtual mobile should not be blank.')),
                'attr' => array(
                    'autofocus' => '',
                )
            ))
            ->add('mobile_alias', 'text', array(
                'required' => true,
                'label' => 'Target mobile',
                'trim' => true,
                'constraints' =>  new Assert\NotBlank(array('message' => 'Target mobile should not be blank.')),
            ))
            ->add('description', 'textarea', array('required' => false, 'label' => 'Description'))
            ->getForm();

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            $formData = $form->getData();

            if ($form->isValid()) {
                $oMobileAliasModel = new MobileAlias();
                $oMobileAliasModel->setMobile($formData['mobile'])
                    ->setMobileAlias($formData['mobile_alias'])
                    ->setDescription($formData['description']);
                if ($add) {
                    $oMobileAliasRepo->add($oMobileAliasModel);
                } else {
                    $oMobileAliasModel->setId($id);
                    $oMobileAliasRepo->update($oMobileAliasModel);
                }

                return $app->redirect($app['url_generator']->generate('mobile_alias_index', array()));
            }
        }

        return new Response($app['twig']->render('mobile_alias/edit.html.twig', array(
            'form' => $form->createView(),
            'error' => $error,
            'add' => $add
        )));
    }
}
