<?php
namespace UserBase\Server\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\FormError;
use UserBase\Server\Model\AccountTag;
use Exception;

class AccountTagController
{
    public function tagAction(Application $app, Request $request, $accountName)
    {
        $error = $request->query->get('error');
        $oTagRepo = $app->getTagRepository();
        $oAccountTagRepo = $app->getAccountTagRepository();

        $defaults = array();
        $add = false;
        // GENERATE FORM --//
        $aTags = [];
        $aTagsData = $oTagRepo->findAll();

        foreach ($aTagsData as $aTemp) {
            $aTags[$aTemp['id']] = $aTemp['name'];
        }

        $aAssignTags = [];
        $aAssignTagsData = $oAccountTagRepo->getByAccountName($accountName);

        foreach ($aAssignTagsData as $aTemp) {
            $aAssignTags [] = $aTemp['tag_id'];
        }
        $defaults['tag_id'] = $aAssignTags;

        $form = $app['form.factory']->createBuilder('form', $defaults)
            ->add('tag_id', 'choice', array(
                'required' => false,
                'label' => 'Select Tags',
                'read_only' => false,
                'trim' => true,
                'multiple'=> true,
                'expanded' => true,
                'choices' => $aTags,
                'attr' => array(
                    'autofocus' => '',
                //    'class' => 'form-control'
                )
            ))
            ->getForm();

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            $formData = $form->getData();

            if ($form->isValid()) {
                $oAccountTagModel = new AccountTag();
                $oAccountTagRepo->removeAccountTag($accountName);
                foreach ($formData['tag_id'] as $tagId) {
                    $oAccountTagModel->setAccountName($accountName)
                            ->setTagId($tagId);
                    $oAccountTagRepo->add($oAccountTagModel);
                }
                return $app->redirect($app['url_generator']->generate('admin_account_view', array(
                    'accountname' => $accountName
                )));
            }
        }

        return new Response($app['twig']->render('tag/account.html.twig', array(
            'form' => $form->createView(),
            'error' => $error,
            'add' => $add,
            'accountName' => $accountName
        )));
    }
}
