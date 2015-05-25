<?php

namespace UserBase\Server;

use Silex\Application as SilexApplication;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\SecurityServiceProvider as SilexSecurityServiceProvider;
use Silex\Provider\FormServiceProvider;
use Silex\Provider\TranslationServiceProvider;
use Silex\Provider\RoutingServiceProvider;
use Silex\Provider\MonologServiceProvider;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Yaml\Parser as YamlParser;
use Symfony\Component\Security\Core\Encoder\PlaintextPasswordEncoder;

use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\MessageSelector;
use Symfony\Component\Translation\Loader\ArrayLoader;

use LinkORB\Component\DatabaseManager\DatabaseManager;
use UserBase\Server\Repository\PdoUserRepository;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use RuntimeException;
use Herald\Client\Client as HeraldClient;
use UserBase\Server\Mailer\HeraldMailer;

class Application extends SilexApplication
{
    private $config;
    private $strings = array();

    public function __construct(array $values = array())
    {
        parent::__construct($values);

        $this->configureParameters();
        $this->configureService();
        $this->configureStrings();
        $this->configureRoutes();
        $this->configureTemplateEngine();
        $this->configureSecurity();
    }

    private function configureParameters()
    {
        $parser = new YamlParser();
        $this->config = $parser->parse(file_get_contents(__DIR__.'/../config.yml'));
        if (isset($this->config['debug'])) {
            $this['debug'] = true;
        }

        $this['userbase.baseurl'] = $this->config['userbase']['baseurl'];
        $this['userbase.postfix'] = $this->config['userbase']['postfix'];
        $this['userbaselogourl'] = $this->config['userbase']['logourl'];
    }


    private function configureService()
    {
        $this->register(
            new TranslationServiceProvider(),
            array(
                'locale' => 'en'
            )
        );
        //  'translation.class_path' =>  __DIR__.'/../vendor/symfony/src',

        /*
        // the form service
        $this->register(new FormServiceProvider());
        */
        $this->register(new RoutingServiceProvider());

        // *** Setup Sessions ***
        $this->register(new \Silex\Provider\SessionServiceProvider(), array(
            'session.storage.save_path' => '/tmp/userbase_sessions'
        ));

        $this->register(new SilexSecurityServiceProvider(), array());


        $dbname = $this->config['userbase']['dbname'];

        $dm = new DatabaseManager();
        $pdo = $dm->getPdo($dbname);
        $this['pdo'] = $pdo;

        $factory = $this['security.encoder_factory'];
        $this->userRepository = new PdoUserRepository($pdo, $factory);




        $herald = new HeraldClient(
            $this->config['herald']['username'],
            $this->config['herald']['password'],
            $this->config['herald']['baseurl'],
            $this->config['herald']['transport']
        );
        $herald->setTemplateNamePrefix($this->config['herald']['prefix']);

        $this['herald'] = $herald;

        $mailer = new HeraldMailer($herald);
        $this['mailer'] = $mailer;
    }

    private function loadStrings($filename)
    {
        if (!file_exists($filename)) {
            throw new RuntimeException("Strings file not found: " . $filename);
        }

        $parser = new YamlParser();
        $lines = $parser->parse(file_get_contents($filename));
        foreach ($lines as $key => $value) {
            $this->strings[$key] = $value;
            //$this->strings[$key] = "#" . $value . "#";
        }
    }

    private function configureStrings()
    {
        $this->loadStrings(__DIR__ . '/../app/strings.yml');

        $this['translator.domains'] = array(
            'messages' => array(
                'en' => $this->strings
            )
        );

    }

    private function configureRoutes()
    {
        $locator = new FileLocator(array(__DIR__.'/../app'));
        $loader = new YamlFileLoader($locator);
        $this['routes'] = $loader->load('routes.yml');
    }

    private function configureTemplateEngine()
    {
        $this->register(new TwigServiceProvider(), array(
            'twig.path' => array(
                __DIR__.'/Resources/views/',
            ),
        ));
    }

    private function configureSecurity()
    {

        /*
        $security = $parameters['security'];

        if ($security['encoder']) {
            // $this['security.encoder.digest'] = new PlaintextPasswordEncoder(true);
            $digest = '\\Symfony\\Component\\Security\\Core\\Encoder\\'.$security['encoder'];
            $this['security.encoder.digest'] = new $digest(true);
        }
        */

        $this['security.firewalls'] = array(
            'default' => array(
                'anonymous' => true,
                'pattern' => '^/',
                'form' => array('login_path' => '/login', 'check_path' => '/login_check'),
                'logout' => array('logout_path' => '/logout'),
                'users' => $this->getUserRepository(),
            ),
            'api' => array(
                'stateless' => true,
                'pattern' => '^/api',
                'http' => true,
                'users' => $this->getUserRepository(),
            ),
        );
    }

    public function getUserRepository()
    {
        return $this->userRepository;
    }
}
