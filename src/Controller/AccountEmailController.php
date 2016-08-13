<?php
namespace UserBase\Server\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\FormError;
use UserBase\Server\Model\AccountEmail;
use Exception;

class AccountEmailController
{
    public function indexAction(Application $app, Request $request, $accountName)
    {
        $error = $request->query->get('error');
        $oAccountEmailRepo = $app->getAccountEmailRepository();
        $entities = $oAccountEmailRepo->findByAccountName($accountName);

        return new Response($app['twig']->render('account_email/index.html.twig', array(
            'error' => $error,
            'entities' => $entities,
            'accountName' => $accountName
        )));
    }

    public function viewAction(Application $app, Request $request, $accountName, $id)
    {
        $error = $request->query->get('error');
        $oAccountEmailRepo = $app->getAccountEmailRepository();

        $entity = $oAccountEmailRepo->getById($id);

        return new Response($app['twig']->render('account_email/view.html.twig', array(
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
        $oAccountEmailRepo = $app->getAccountEmailRepository();
        $oAccountEmailRepo->remove($id);

        return $app->redirect($app['url_generator']->generate('account_email_index', array(
            'accountName' => $accountName
        )));
    }
    
    public function defaultAction(Application $app, Request $request, $accountName, $id)
    {
        $accountEmailRepo = $app->getAccountEmailRepository();
        $email = $accountEmailRepo->getById($id);
        
        $accountRepo = $app->getAccountRepository();
        $account = $accountRepo->getByName($accountName);
        $account->setEmail((string)$email['email']);
        $account->setEmailVerifiedAt($email['verified_at']);
        $accountRepo->update($account);

        return $app->redirect($app['url_generator']->generate('admin_account_view', array(
            'accountname' => $accountName
        )));
    }


    public function verifyAction(Application $app, Request $request, $accountName, $id)
    {
        $accountEmailRepo = $app->getAccountEmailRepository();
        $aEmail = $accountEmailRepo->getById($id);
        $email = new AccountEmail();
        $email->setId($id);
        $email->setEmail($aEmail['email']);
        $email->setVerifiedAt(time());
        $accountEmailRepo->update($email);

        return $app->redirect($app['url_generator']->generate('account_email_index', array(
            'accountName' => $accountName
        )));
    }
    

    public function unverifyAction(Application $app, Request $request, $accountName, $id)
    {
        $accountEmailRepo = $app->getAccountEmailRepository();
        $aEmail = $accountEmailRepo->getById($id);
        $email = new AccountEmail();
        $email->setId($id);
        $email->setEmail($aEmail['email']);
        $email->setVerifiedAt(null);
        $accountEmailRepo->update($email);

        return $app->redirect($app['url_generator']->generate('account_email_index', array(
            'accountName' => $accountName
        )));
    }

    protected function getEditForm(Application $app, Request $request, $id)
    {
        $error = $request->query->get('error');
        $accountName = $request->get('accountName');
        $oAccountEmailRepo = $app->getAccountEmailRepository();
        $add = ($id)? false : true;

        $defaults = array();
        if ($id) {
            if ($oAccountEmail = $oAccountEmailRepo->getById($id)) {
                $defaults =  [
                    'email' => $oAccountEmail['email'],
                ];
            }
        }
        
        $form = $app['form.factory']->createBuilder('form', $defaults)
        ->add('email', 'text', array(
            'required' => true,
            'label' => 'email',
            'constraints' => array(new Assert\NotBlank(array('message' => 'Email value should not be blank.')),
            ),
            'attr' => array(
                'autofocus'  => '',
                'placeholder' => 'example@example.com',
            )
        ))
        ->getForm();

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            $formData = $form->getData();

            //--check connection account type --//
            if ($form->isValid()) {
                $oAccountEmailModel = new AccountEmail();

                $oAccountEmailModel
                    ->setAccountName($accountName)
                    ->setEmail($formData['email'])
                ;

                if ($add) {
                    $oAccountEmailRepo->add($oAccountEmailModel);
                } else {
                    $oAccountEmailModel->setId($id);
                    $oAccountEmailRepo->update($oAccountEmailModel);
                }
                return $app->redirect($app['url_generator']->generate('account_email_index', array(
                    'accountName' => $accountName
                )));
            }
        }
        return new Response($app['twig']->render('account_email/edit.html.twig', array(
            'form' => $form->createView(),
            'error' => $error,
            'add' => $add,
            'acountName' => $accountName
        )));
    }
}
