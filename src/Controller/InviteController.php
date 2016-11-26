<?php
namespace UserBase\Server\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\FormError;
use Symfony\Component\Validator\Constraints as Assert;
use UserBase\Server\Model\Invite;
use Exception;

class InviteController
{
    public function indexAction(Application $app, Request $request)
    {
        $error = $request->query->get('error');
        $oInviteRepo = $app->getInviteRepository();
        $entities = $oInviteRepo->findAll();
        $entities = array_reverse($entities);

        return new Response($app['twig']->render('invite/index.html.twig', array(
            'error' => $error,
            'entities' => $entities
        )));
    }

    public function deleteAction(Application $app, Request $request, $id)
    {
        $oInviteRepo = $app->getInviteRepository();

        $oInviteRepo->remove($id);
        return $app->redirect($app['url_generator']->generate('admin_invite_index', array()));
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
        $oInviteRepo = $app->getInviteRepository();
        $add = ($id)? false : true;
        $defaults = array();
        $inviteId = null;
        if ($id) {
            if (!$oInvite = $oInviteRepo->getById($id)) {
                return $app->redirect($app['url_generator']->generate('admin_invite_index'));
            }
            $defaults = [
                'inviter' => $oInvite['inviter'],
                'inviter_org' => $oInvite['inviter_org'],
                'display_name' => $oInvite['display_name'],
                'email' => $oInvite['email'],
                'payload' => $oInvite['payload'],
                'account_name' => $oInvite['account_name']
            ];
            $inviteId = $oInvite['id'];
        }

        // GENERATE FORM --//
        $form = $app['form.factory']->createBuilder('form', $defaults)
            ->add('inviter', 'text', array(
                'required' => true,
                'label' => 'Inviter',
                'read_only' => false,
                'trim' => true,
                'constraints' =>  new Assert\NotBlank(array('message' => 'Inviter value should not be blank.')),
                'attr' => array(
                    'autofocus' => '',
                )
            ))
            ->add('inviter_org', 'text', array(
                'required' => false,
                'label' => 'Inviter org',
                'read_only' => false,
                'trim' => true
            ))
            ->add('display_name', 'text', array(
                'required' => true,
                'label' => 'Display name',
                'read_only' => false,
                'trim' => true,
                'constraints' =>  new Assert\NotBlank(array('message' => 'Should not be blank.')),
                'attr' => array(
                    'autofocus' => '',
                )
            ))
            ->add('email', 'text', array(
                'required' => true,
                'label' => 'Email',
                'read_only' => false,
                'trim' => true,
                'constraints' =>  new Assert\NotBlank(array('message' => 'Should not be blank.')),
                'attr' => array(
                    'autofocus' => '',
                )
            ))
            ->add('payload', 'textarea', array('required' => false, 'label' => 'Payload'))
            ->add('account_name', 'text', array(
                'required' => false,
                'label' => 'Account name',
                'read_only' => false,
                'trim' => true,
                'attr' => array(
                    'autofocus' => '',
                )
            ))
            ->getForm();

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            $formData = $form->getData();

            if ($form->isValid()) {
                $oInviteModel = new Invite();

                $oInviteModel
                    ->setInviter($formData['inviter'])
                    ->setInviterOrg($formData['inviter_org'])
                    ->setDisplayName($formData['display_name'])
                    ->setEmail($formData['email'])
                    ->setPayload($formData['payload'])
                    ->setAccountName($formData['account_name'])
                ;

                if ($add) {
                    $oInviteRepo->add($oInviteModel);
                } else {
                    $oInviteModel->setId($id);
                    $oInviteRepo->update($oInviteModel);
                }

                return $app->redirect($app['url_generator']->generate('admin_invite_index', array()));
            }
        }

        return new Response($app['twig']->render('invite/edit.html.twig', array(
            'form' => $form->createView(),
            'error' => $error,
            'inviteId' => $inviteId,
            'add' => $add
        )));
    }
}
