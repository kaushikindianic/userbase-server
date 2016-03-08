<?php
namespace UserBase\Server\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints as Assert;
use UserBase\Server\Model\Blacklist;
use Exception;

class BlacklistController
{
    public function indexAction(Application $app, Request $request)
    {
        $error = $request->query->get('error');
        $oBlacklistRepo = $app->getBlacklistRepository();
        $entities = $oBlacklistRepo->findAll();

        return new Response($app['twig']->render('blacklist/index.html.twig', array(
            'error' => $error,
            'entities' => $entities
        )));
    }

    public function deleteAction(Application $app, Request $request, $id)
    {
        $oBlacklistRepo = $app->getBlacklistRepository();

        $oBlacklistRepo->remove($id);
        return $app->redirect($app['url_generator']->generate('admin_blacklist_index', array()));
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
        $oBlacklistRepo = $app->getBlacklistRepository();
        $add = ($id)? false : true;
        $defaults = array();

        if ($id) {
            if (!$oBlacklist = $oBlacklistRepo->getById($id)) {
                return $app->redirect($app['url_generator']->generate('admin_blacklist_index'));
            }
            $defaults = ['account_name' => $oBlacklist['account_name'],
                        'description' => $oBlacklist['description']
                    ];
        } else {
        }

        // GENERATE FORM --//
        $form = $app['form.factory']->createBuilder('form', $defaults)
            ->add('account_name', 'text', array(
                'required' => true,
                'label' => 'name',
                'read_only' => false,
                'trim' => true,
                'constraints' =>  new Assert\NotBlank(array('message' => 'Name value should not be blank.')),
                'attr' => array(
                    'autofocus' => '',
                )
            ))
            ->add('description', 'textarea', array('required' => false, 'label' => 'Description'))
            ->getForm();

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            $formData = $form->getData();

            //CHECK NAME EXIST
            if ($oBlacklistRepo->checkExist($formData['account_name'], $id)) {
                $form->get('account_name')->addError(new FormError('Name already exist'));
            }

            if ($form->isValid()) {
                $oBlacklistModel = new Blacklist();

                $oBlacklistModel->setAccountName($formData['account_name'])
                    ->setDescription($formData['description']);

                if ($add) {
                    $oBlacklistRepo->add($oBlacklistModel);
                } else {
                    $oBlacklistModel->setId($id);
                    $oBlacklistRepo->update($oBlacklistModel);
                }

                return $app->redirect($app['url_generator']->generate('admin_blacklist_index', array()));
            }
        }

        return new Response($app['twig']->render('blacklist/edit.html.twig', array(
            'form' => $form->createView(),
            'error' => $error,
            'add' => $add
        )));
    }
}
