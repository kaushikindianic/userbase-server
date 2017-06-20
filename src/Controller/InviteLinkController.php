<?php
namespace UserBase\Server\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\FormError;
use Symfony\Component\Validator\Constraints as Assert;
use UserBase\Server\Model\Invite;
use Exception;

class InviteLinkController
{
    public function viewAction(Application $app, Request $request, $inviteId, $inviteHash)
    {
        $oInviteRepo = $app->getInviteRepository();
        $invite = $oInviteRepo->getById($inviteId);
        //print_r($invite);
        //exit();

        return new Response($app['twig']->render('preauth/invite-link/view.html.twig', array(
            'invite' => $invite,
            'inviteHash' => $inviteHash
        )));
    }


    public function rejectAction(Application $app, Request $request, $inviteId, $inviteHash)
    {
        $oInviteRepo = $app->getInviteRepository();
        $invite = $oInviteRepo->getById($inviteId);
        $oInviteRepo->reject($invite['id']);

        return new Response($app['twig']->render('preauth/invite-link/rejected.html.twig', array(
            'invite' => $invite,
            'inviteHash' => $inviteHash
        )));
    }

    public function rejectReasonAction(Application $app, Request $request, $inviteId, $inviteHash)
    {
        $oInviteRepo = $app->getInviteRepository();
        $invite = $oInviteRepo->getById($inviteId);
        $reason = $request->request->get('reject_reason');
        $oInviteRepo->statusReason($invite['id'], $reason);
        //exit('sup' . $reason);


        return new Response($app['twig']->render('preauth/invite-link/rejected_thankyou.html.twig', array(
            'invite' => $invite,
            'inviteHash' => $inviteHash
        )));
    }
}
