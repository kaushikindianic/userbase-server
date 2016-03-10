<?php
namespace UserBase\Server\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\FormError;
use UserBase\Server\Model\AccountConnection;
use Exception;

class AccountConnectionController
{

    public function indexAction(Application $app, Request $request, $accountName)
    {
        $error = $request->query->get('error');
        $oAccountConnectionRepo = $app->getAccountConnectionRepository();
        $entities = $oAccountConnectionRepo->findByAccountName($accountName);

        return new Response($app['twig']->render('account_connection/account.html.twig', array(
            'error' => $error,
            'entities' => $entities,
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

    protected function getEditForm(Application $app, Request $request, $id)
    {
        $error = $request->query->get('error');
        $accountName = $request->get('accountName');
        $oAccountConnectionRepo = $app->getAccountConnectionRepository();
        $oAccountRepo = $app->getAccountRepository();
        $add = ($id)? false : true;
        $defaults = array();

        //-- CHECK CURRENT ACCOUTN TYPE --//
        if ($oAccount = $oAccountRepo->getByName($accountName)) {
            if ($oAccount->getAccountType() != 'user') {
                return $app->redirect($app['url_generator']->generate('admin_account_view', array(
                    'accountname' => $accountName
                )));
            }
        } else {
            return $app->redirect($app['url_generator']->generate('admin_account_index', array(
                'error' => 'Account not exist'
            )));
        }


        if ($id) {
            if (!$oConnection = $oAccountConnectionRepo->getById($id)) {
                return $app->redirect($app['url_generator']->generate('admin_account_view', array(
                    'accountname' => $accountName
                )));
            }
            $defaults = ['connection_name' => $oTag['connection_name'],
                        'connection_type' => $oTag['connection_type']
                    ];
        }
        // GENERATE FORM --//
        $connectionType = ['colleague' => 'colleague', 'classmate' => 'classmate',
                    'work partner' => 'work partner', 'friend' => 'friend', 'other' => 'other'];


        $form = $app['form.factory']->createBuilder('form', $defaults)
            ->add('connection_name', 'text', array(
                'required' => true,
                'label' => 'name',
                'read_only' => false,
                'trim' => true,
                'constraints' =>  new Assert\NotBlank(array('message' => 'Name value should not be blank.')),
                'attr' => array(
                    'autofocus' => '',
                )
            ))
            ->add('connection_type', 'choice', array(
                'required' => true,
                'label' => 'Select Connection Type',
                'read_only' => false,
                'trim' => true,
                'empty_data' => null,
                'empty_value' => '-- Select --',
                'choices' => $connectionType,
                'attr' => array(
                    'autofocus' => '',
                //    'class' => 'form-control'
                )
            ))
            ->getForm();

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            $formData = $form->getData();

            //--check connection account type --//
            if ($oConnectAccount = $oAccountRepo->getByName($formData['connection_name'])) {
                if ($oConnectAccount->getAccountType() != 'user') {
                    $form->get('connection_name')->addError(new FormError('Allow only User type account'));
                }
            } else {
                $form->get('connection_name')->addError(new FormError('Account not exist'));
            }

            if ($form->isValid()) {
                $oAccountConnectionModel = new AccountConnection();

                if ($add) {
                    $oAccountConnectionModel->setAccountName($accountName)
                        ->setConnectionName($formData['connection_name'])
                        ->setConnectionType($formData['connection_type'])
                        ->setCreatedAt(date('Y-m-d H:i:s'));

                    $oAccountConnectionRepo->add($oAccountConnectionModel);

                    //-- SWAPPED RECORD --//
                    $oAccountConnectionModel->setAccountName($formData['connection_name'])
                        ->setConnectionName($accountName)
                        ->setConnectionType($formData['connection_type'])
                        ->setCreatedAt(date('Y-m-d H:i:s'));
                    $oAccountConnectionRepo->add($oAccountConnectionModel);
                } else {
                    $oAccountConnectionModel->setId($id);
                    $oAccountConnectionRepo->update($oAccountConnectionModel);
                }
                return $app->redirect($app['url_generator']->generate('admin_account_connection_index', array(
                    'accountName' => $accountName
                )));
            }
        }

        return new Response($app['twig']->render('account_connection/edit.html.twig', array(
            'form' => $form->createView(),
            'error' => $error,
            'add' => $add,
            'acountName' => $accountName
        )));
    }
}
