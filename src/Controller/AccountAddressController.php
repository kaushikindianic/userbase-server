<?php
namespace UserBase\Server\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\FormError;
use UserBase\Server\Model\AccountAddress;
use Exception;

class AccountAddressController
{
    public function indexAction(Application $app, Request $request, $accountName)
    {
        $error = $request->query->get('error');
        $oAccountAddressRepo = $app->getAccountAddressRepository();
        $entities = $oAccountAddressRepo->findByAccountName($accountName);

        return new Response($app['twig']->render('account_address/index.html.twig', array(
            'error' => $error,
            'entities' => $entities,
            'accountName' => $accountName
        )));
    }

    public function viewAction(Application $app, Request $request, $accountName, $id)
    {
        $error = $request->query->get('error');
        $oAccountAddressRepo = $app->getAccountAddressRepository();

        $entity = $oAccountAddressRepo->getById($id);

        return new Response($app['twig']->render('account_address/view.html.twig', array(
            'error' => $error,
            'entity' => $entity,
            'accountName' => $accountName
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

    public function deleteAction(Application $app, Request $request, $accountName, $id)
    {
        $oAccountAddressRepo = $app->getAccountAddressRepository();
        $oAccountAddressRepo->remove($id);

        return $app->redirect($app['url_generator']->generate('account_address_index', array(
            'accountName' => $accountName
        )));
    }

    protected function getEditForm(Application $app, Request $request, $id)
    {
        $error = $request->query->get('error');
        $accountName = $request->get('accountName');
        $oAccountAddressRepo = $app->getAccountAddressRepository();
        $add = ($id)? false : true;

        $defaults = array();
        if ($id) {
            if ($oAccoutAddress = $oAccountAddressRepo->getById($id)) {
                $defaults =  [
                    'addressline1' => $oAccoutAddress['addressline1'],
                    'addressline2' => $oAccoutAddress['addressline2'],
                    'postalcode' => $oAccoutAddress['postalcode'],
                    'country' => $oAccoutAddress['country'],
                    'city' => $oAccoutAddress['city']
                ];
            }
        }

        //-- GENERATE FROM --//
        $aCountry = [ 'NLD' => 'Netherlands','IND' => 'India', 'FRA' => 'France'];

        $form = $app['form.factory']->createBuilder('form', $defaults)
        ->add('addressline1', 'text', array(
            'required' => true,
            'label' => 'Address',
            'constraints' => array(new Assert\NotBlank(array('message' => 'Address value should not be blank.')),
            ),
            'attr' => array(
                'autofocus'  => '',
                'placeholder' => 'Address Line 1',
            )
        ))
        ->add('addressline2', 'text', array(
            'required' => false,
            'label' => false,
            'attr' => array(
                'placeholder' => 'Address Line 2',
            )
        ))
        ->add('postalcode', 'text', array(
            'required' => true,
            'trim' => true,
            'constraints' => array(new Assert\NotBlank(array('message' => 'postalcode should not be blank.'))),
        ))
        ->add('city', 'text', array(
            'required' => true,
            'trim' => true,
            'constraints' => array(new Assert\NotBlank(array('message' => 'city value should not be blank.'))),
        ))
        ->add('country', 'choice', array(
            'required' => true,
            'trim' => true,
            'choices' => $aCountry,
            'empty_data'  => null,
            'empty_value' => "-- Select Country --",
            'constraints' => array(new Assert\NotBlank(array('message' => ' Select country'))),
        ))
        ->getForm();

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            $formData = $form->getData();

            //--check connection account type --//
            if ($form->isValid()) {
                $oAccountAddressModel = new AccountAddress();

                $oAccountAddressModel->setAccountName($accountName)
                    ->setAddressline1($formData['addressline1'])
                    ->setAddressline2($formData['addressline2'])
                    ->setCity($formData['city'])
                    ->setPostalcode($formData['postalcode'])
                    ->setCountry($formData['country']);

                if ($add) {
                    $oAccountAddressRepo->add($oAccountAddressModel);
                } else {
                    $oAccountAddressModel->setId($id);
                    $oAccountAddressRepo->update($oAccountAddressModel);
                }
                return $app->redirect($app['url_generator']->generate('account_address_index', array(
                    'accountName' => $accountName
                )));
            }
        }
        return new Response($app['twig']->render('account_address/edit.html.twig', array(
            'form' => $form->createView(),
            'error' => $error,
            'add' => $add,
            'acountName' => $accountName
        )));
    }
}
