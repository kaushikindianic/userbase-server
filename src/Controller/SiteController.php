<?php

namespace UserBase\Server\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Service;
use Exception;
use UserBase\Server\Model\Event;
use UserBase\Server\Model\Account;
use RunMyBusiness\Initialcon\Initialcon;

class SiteController
{
    public function helpAction(Application $app, Request $request)
    {
        $url = $app['userbase.help_url'];
        return $app->redirect($url);
    }

    public function pictureAction(Application $app, Request $request, $accountname)
    {
        $repo = $app->getAccountRepository();
        $account = $repo->getByName($accountname);
        $fileName = $accountname . '.png';


        if (is_file('account_picture'.'/'.$fileName)) {
            //echo '/'.$app['picturePath'].'/'.$account->getPictureUrl();exit;
            header("Expires: Sat, 26 Jul 2020 05:00:00 GMT");
            return $app->redirect('/account_picture/'.$fileName);
        } else {
            if ($account) {
                $value = $account->getEmail();
                if (!$value) {
                    $value = $account->getName();
                }
                $initials = $account->getInitials();
            } else {
                $initials = '?';
            }
            /*
            $url = "https://www.gravatar.com/avatar/" . md5(strtolower(trim($value))) . "?d=retro";
            return $app->redirect($url);
            */
            //$url = $account->getPictureUrl();

            $size = 128;
            if ($request->query->has('s')) {
                $size = (int)$request->query->get('s');
                if ($size>512) {
                    $size = 512;
                }
            }
            $initialcon = new Initialcon();
            $img = $initialcon->getImageObject($initials, $accountname, $size);
            header("Expires: Sat, 26 Jul 2020 05:00:00 GMT");
            echo $img->response('png');
            exit();
        }
    }
}
