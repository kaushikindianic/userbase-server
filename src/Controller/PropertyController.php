<?php
namespace UserBase\Server\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\FormError;
use UserBase\Server\Model\Property;
use Exception;

class PropertyController
{
    public function indexAction(Application $app, Request $request)
    {
        $error = $request->query->get('error');
        $oPropertyRepo = $app->getPropertyRepository();
        $entities = $oPropertyRepo->findAll();

        return new Response($app['twig']->render('property/index.html.twig', array(
            'error' => $error,
            'entities' => $entities
        )));
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
        $oPropertyRepo = $app->getPropertyRepository();
        $add = ($id)? false : true;
        $defaults = array();

        if ($id) {
            if (!$oProperty = $oPropertyRepo->getById($id)) {
                return $app->redirect($app['url_generator']->generate('admin_property_index'));
            }
            $defaults = ['name' => $oProperty['name'],
                        'description' => $oProperty['description']
                    ];
        } else {
        }

        // GENERATE FORM --//
        $form = $app['form.factory']->createBuilder('form', $defaults)
            ->add('name', 'text', array(
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
            if ($oPropertyRepo->checkExist($formData['name'], $id)) {
                $form->get('name')->addError(new FormError('Name already exist'));
            }

            if ($form->isValid()) {
                $oPropertyModel = new Property();

                $oPropertyModel->setName($formData['name'])
                    ->setDescription($formData['description']);

                if ($add) {
                    $oPropertyRepo->add($oPropertyModel);
                } else {
                    $oPropertyModel->setId($id);
                    $oPropertyRepo->update($oPropertyModel);
                }
                return $app->redirect($app['url_generator']->generate('admin_property_index', array()));
            }
        }

        return new Response($app['twig']->render('property/edit.html.twig', array(
            'form' => $form->createView(),
            'error' => $error,
            'add' => $add
        )));
    }
}
