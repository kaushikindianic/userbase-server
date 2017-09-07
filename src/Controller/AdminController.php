<?php

namespace UserBase\Server\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Hackzilla\PasswordGenerator\Generator\RequirementPasswordGenerator;
use Hackzilla\PasswordGenerator\RandomGenerator\Php7RandomGenerator;

class AdminController
{
    public function indexAction(Application $app, Request $request)
    {
        $data = array();

        return new Response($app['twig']->render('admin/index.html.twig', $data));
    }

    public function eventIndexAction(Application $app, Request $request)
    {
        $oEventRepo = $app->getEventRepository();
        $aEvents = $oEventRepo->getAll();

        return new Response($app['twig']->render(
            'admin/event_index.html.twig',
            array('aEvents' => $aEvents)
        ));
    }

    public function addApikeyAction(Application $app, Request $request)
    {
        $generator = new RequirementPasswordGenerator();

        $generator
          ->setLength(32)
          ->setRandomGenerator(new Php7RandomGenerator())
          ->setOptionValue(RequirementPasswordGenerator::OPTION_UPPER_CASE, true)
          ->setOptionValue(RequirementPasswordGenerator::OPTION_LOWER_CASE, true)
          ->setOptionValue(RequirementPasswordGenerator::OPTION_NUMBERS, true)
          ->setOptionValue(RequirementPasswordGenerator::OPTION_SYMBOLS, false)
          ->setMinimumCount(RequirementPasswordGenerator::OPTION_UPPER_CASE, 1)
          ->setMinimumCount(RequirementPasswordGenerator::OPTION_LOWER_CASE, 1)
          ->setMinimumCount(RequirementPasswordGenerator::OPTION_NUMBERS, 1)
        ;

        $password = $generator->generatePassword();

        return new Response($app['twig']->render(
            'admin/password_generator.html.twig',
            array('password' => $password)
        ));
    }
}
