<?php
namespace UserBase\Server\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\FormError;
use UserBase\Server\Model\AccountNotification;
use Exception;

class AccountNotificationController
{
    public function indexAction(Application $app, Request $request, $accountName)
    {
        $error = $request->query->get('error');
        $oAccountNotificationRepo = $app->getAccountNotificationRepository();
        $entities = $oAccountNotificationRepo->findByAccountName($accountName);

        return new Response($app['twig']->render('account_notification/index.html.twig', array(
            'error' => $error,
            'entities' => $entities,
            'accountName' => $accountName
        )));
    }

    public function viewAction(Application $app, Request $request, $accountName, $id)
    {
        $error = $request->query->get('error');
        $oAccountNotificationRepo = $app->getAccountNotificationRepository();

        $entity = $oAccountNotificationRepo->getById($id);

        return new Response($app['twig']->render('account_notification/view.html.twig', array(
            'error' => $error,
            'entity' => $entity,
            'accountName' => $accountName
        )));
    }


    public function seenAction(Application $app, Request $request, $accountName)
    {
        $oAccountNotificationRepo = $app->getAccountNotificationRepository();
        $notificationXuid = $request->attributes->get('notificationXuid');
        $entity = $oAccountNotificationRepo->setSeenByXuid($notificationXuid);

        return $app->redirect($app['url_generator']->generate('admin_account_notification_index', array(
            'accountName' => $accountName
        )));
    }
    
    public function unseenAction(Application $app, Request $request, $accountName)
    {
        $oAccountNotificationRepo = $app->getAccountNotificationRepository();
        $notificationXuid = $request->attributes->get('notificationXuid');
        $entity = $oAccountNotificationRepo->setUnseenByXuid($notificationXuid);

        return $app->redirect($app['url_generator']->generate('admin_account_notification_index', array(
            'accountName' => $accountName
        )));
    }

    public function addAction(Application $app, Request $request)
    {
        return $this->getEditForm($app, $request, null);
    }

    protected function getEditForm(Application $app, Request $request, $id)
    {
        $error = $request->query->get('error');
        $accountName = $request->get('accountName');
        $oAccountNotificationRepo = $app->getAccountNotificationRepository();
        $add = ($id)? false : true;

        $defaults = array();

        // GENERATE FORM --//
        $form = $app['form.factory']->createBuilder('form', $defaults)
            ->add('notification_type', 'text', array(
                'required' => true,
                'trim' => true,
                'constraints' => new Assert\NotBlank(
                    array('message' => 'Notification Type value should not be blank.')
                ),
                'attr' => array(
                    'autofocus' => '',
                )
            ))
            ->add('source_account_name', 'text', array(
                'required' => false,
                'trim' => true
            ))
            ->add('subject', 'text', array(
                'required' => true,
                'trim' => true,
                'constraints' => new Assert\NotBlank(
                    array('message' => 'Subject value should not be blank.')
                ),
            ))
            ->add('link', 'url', array(
                'required' => true,
                'trim' => true,
                'constraints' => new Assert\NotBlank(
                    array('message' => 'link value should not be blank.'),
                    new Assert\Url(array(
                        'message' => 'The link "{{ value }}" is not a valid url.',
                    ))
                ),
            ))
            ->add('body', 'textarea', array(
                'required' => false,
            ))
            ->add('seen_at', 'datetime', array(
                'required' => false,
                'input'  => 'timestamp',
                'widget' => 'single_text',
                'attr' => array(
                    'placeholder' => 'Seen date',
                )
            ))

            ->getForm();

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            $formData = $form->getData();

            //--check connection account type --//
            if ($form->isValid()) {
                $oAccountnotificationModel = new AccountNotification();

                $oAccountnotificationModel->setAccountName($accountName)
                    ->setSourceAccountName($formData['source_account_name'])
                    ->setNotificationType($formData['notification_type'])
                    ->setLink($formData['link'])
                    ->setSubject($formData['subject'])
                    ->setBody($formData['body'])
                    ->setCreatedAt(date('Y-m-d H:i:s'));

                if ($add) {
                    $oAccountNotificationRepo->add($oAccountnotificationModel);
                }
                return $app->redirect($app['url_generator']->generate('admin_account_notification_index', array(
                    'accountName' => $accountName
                )));
            }
        }
        return new Response($app['twig']->render('account_notification/edit.html.twig', array(
            'form' => $form->createView(),
            'error' => $error,
            'add' => $add,
            'acountName' => $accountName
        )));
    }
}
