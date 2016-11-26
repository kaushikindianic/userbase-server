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
use UserBase\Server\Repository\PdoUserRepository;
use UserBase\Server\Repository\PdoAppRepository;
use UserBase\Server\Repository\PdoAccountRepository;
use UserBase\Server\Repository\PdoOAuthRepository;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use RuntimeException;
use Service;
use Silex\Provider\ValidatorServiceProvider;
use UserBase\Server\Repository\PdoIdentityRepository;
use Userbser\Server\Repository\PdoEventRepository;
use UserBase\Server\Repository\PdoApikeyRepository;
use UserBase\Server\Repository\PdoAccountPropertyRepository;
use UserBase\Server\Repository\PdoSpaceRepository;
use Xi\Sms\SmsService;
use Xi\Sms\SmsMessage;
use Xi\Sms\Gateway\MessageBirdGateway;
use UserBase\Server\Repository\PdoBlacklistRepository;
use UserBase\Server\Repository\PdoTagRepository;
use UserBase\Server\Repository\PdoPropertyRepository;
use UserBase\Server\Repository\PdoAccountTagRepository;
use UserBase\Server\Repository\PdoAccountConnectionRepository;
use UserBase\Server\Repository\PdoAccountNotificationRepository;
use UserBase\Server\Repository\PdoMobileAliasRepository;
use UserBase\Server\Repository\PdoAccountAddressRepository;
use UserBase\Server\Repository\PdoAccountEmailRepository;
use UserBase\Server\Repository\PdoInviteRepository;
use Ramsey\Uuid\Uuid;

use League\Tactician\CommandBus;
use League\Tactician\Handler\CommandHandlerMiddleware;
use League\Tactician\Handler\Locator\InMemoryLocator;
use League\Tactician\Handler\CommandNameExtractor\ClassNameExtractor;
use League\Tactician\Handler\MethodNameInflector\HandleInflector;
use League\Tactician\Handler\MethodNameInflector\HandleClassNameWithoutSuffixInflector;

class Application extends SilexApplication
{
    private $config;
    private $strings = array();
    private $userRepository;
    private $oauthRepository;
    private $accountRepository;
    private $identityRepository;
    private $eventRepository;
    private $apikeyRepository;
    private $spaceRepository;
    private $blacklistRepository;
    private $tagRepository;
    private $propertyRepository;
    private $accountTagRepository;
    private $accountConnectionRepository;
    private $accountNotificationRepository;
    private $mobileAliasRepository;
    private $accountAddressRepository;
    private $accountEmailRepository;

    public function __construct(array $values = array())
    {
        parent::__construct($values);

        $this->configureParameters();
        $this->configureLogging();
        $this->configureService();
        $this->configureCommandBus();
        $this->configureStrings();
        $this->configureRoutes();
        $this->configureTemplateEngine();
        $this->configureSecurity();
    }
    
    protected $logPath;
    
    private function configureLogging()
    {
        $this->logPath = __DIR__ . '/../var/log';
        if (!file_exists($this->logPath)) {
            mkdir($this->logPath, 0777, true);
        }
    }

    private function configureParameters()
    {
        $parser = new YamlParser();
        $this->config = $parser->parse(file_get_contents(__DIR__.'/../app/config/parameters.yml'));
        if (isset($this->config['debug'])) {
            $this['debug'] = !!$this->config['debug'];
        }

        $this['userbase.baseurl'] = $this->config['userbase']['baseurl'];
        $this['userbase.help_url'] = isset($this->config['userbase']['help_url']) ?
            $this->config['userbase']['help_url'] : null;
            
        $this['userbase.login_url'] = isset($this->config['userbase']['login_url']) ?
            $this->config['userbase']['login_url'] : null;
        $this['userbase.agree_text'] = isset($this->config['userbase']['agree_text']) ?
            $this->config['userbase']['agree_text'] : null;

        if (isset($this->config['userbase']['template_override'])) {
            $this['userbase.template_override'] = $this->config['userbase']['template_override'];
        }
        $this['userbase.signup_tag'] = isset($this->config['userbase']['signup_tag']) ? $this->config['userbase']['signup_tag'] : null;
        $this['userbase.signup_properties'] = isset($this->config['userbase']['signup_properties']) ? $this->config['userbase']['signup_properties'] : null;
        $this['userbase.signup_webhook'] = isset($this->config['userbase']['signup_webhook']) ? $this->config['userbase']['signup_webhook'] : null;
        $this['userbase.verified_webhook'] = isset($this->config['userbase']['verified_webhook']) ? $this->config['userbase']['verified_webhook'] : null;
        $this['userbase.accepted_webhook'] = isset($this->config['userbase']['accepted_webhook']) ? $this->config['userbase']['accepted_webhook'] : null;
        
        $this['userbase.postfix'] = $this->config['userbase']['postfix'];
        $this['userbase.logourl'] = $this->config['userbase']['logourl'];
        $this['userbase.enable_mobile'] = isset($this->config['userbase']['enable_mobile']) ?
            $this->config['userbase']['enable_mobile'] : false;
        $this['userbase.partition'] = $this->config['userbase']['partition'];
        $this['userbase.salt'] = $this->config['userbase']['salt'];

        if (isset($this->config['sms'])) {
            $this['sms.provider'] = $this->config['sms']['provider'];
            $this['sms.sender'] = $this->config['sms']['sender'];
            $this['sms.apikey'] = $this->config['sms']['apikey'];
        }
        
        $this['parameters'] = $this->config;
    }

    private function configureCommandBus()
    {
        $this->before(function (\Symfony\Component\HttpFoundation\Request $request, Application $app) {
            $locator = new InMemoryLocator();
            $handlerClassNames = [
                \UserBase\Server\Domain\Account\CommandHandler::class,
                \UserBase\Server\Domain\AccountProperty\CommandHandler::class
            ];
            
            foreach ($handlerClassNames as $handlerClassName) {
                $handler = new $handlerClassName($this); // TODO: use injector?
                $commandClasses = $handler->subscribe();
                foreach ($commandClasses as $commandClass) {
                    $locator->addHandler(
                        $handler,
                        $commandClass
                    );
                }
            }
            
            $handlerMiddleware = new CommandHandlerMiddleware(
                new ClassNameExtractor(),
                $locator,
                new HandleClassNameWithoutSuffixInflector()
            );
            
            $logger = new Logger('command');
            $logger->pushProcessor(new \Monolog\Processor\WebProcessor());
            $logger->pushProcessor(new \Monolog\Processor\GitProcessor());
            $logger->pushHandler(new StreamHandler($this->logPath . '/command.log', Logger::INFO));
            
            $loggerMiddleware = new \UserBase\Server\Domain\LoggingCommandMiddleware($logger);
            $commandBus = new CommandBus(
                [
                    $loggerMiddleware,
                    $handlerMiddleware
                ]
            );
            $this['commandbus'] = $commandBus;
            
            $subscriber = new \UserBase\Server\Event\MailEventSubscriber($this);
            $this['dispatcher']->addSubscriber($subscriber);
            $subscriber = new \UserBase\Server\Event\WebhookEventSubscriber($this);
            $this['dispatcher']->addSubscriber($subscriber);
            $subscriber = new \UserBase\Server\Event\HipChatEventSubscriber($this);
            $this['dispatcher']->addSubscriber($subscriber);
            $subscriber = new \UserBase\Server\Event\EventLogEventSubscriber($this);
            $this['dispatcher']->addSubscriber($subscriber);
            
            $pdo = Service::pdo();
            $subscriber = new \UserBase\Server\Projector\PdoUserProjector($pdo, 'user');
            $this['dispatcher']->addSubscriber($subscriber);
            $subscriber = new \UserBase\Server\Projector\PdoAccountProjector($pdo, 'account');
            $this['dispatcher']->addSubscriber($subscriber);
            $subscriber = new \UserBase\Server\Projector\PdoAccountPropertyProjector($pdo, 'account_property');
            $this['dispatcher']->addSubscriber($subscriber);
        });
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

        // the form service
        $this->register(new FormServiceProvider());

        $this->register(new RoutingServiceProvider());
        $this->register(new ValidatorServiceProvider());

        // *** Setup Sessions ***
        $this->register(new \Silex\Provider\SessionServiceProvider(), array(
            'session.storage.save_path' => '/tmp/userbase_sessions'
        ));

        $this->register(new SilexSecurityServiceProvider(), array());

        $pdo = Service::pdo();

        $factory = $this['security.encoder_factory'];
        $this->accountRepository = new PdoAccountRepository($pdo);
        $this->oauthRepository = new PdoOAuthRepository($pdo);
        $this->userRepository = new PdoUserRepository(
            $pdo,
            $this->oauthRepository,
            $factory,
            $this->accountRepository,
            $this['userbase.enable_mobile']
        );
        $this->appRepository = new PdoAppRepository($pdo);
        $this->identityRepository = new PdoIdentityRepository($pdo);
        $this->eventRepository = new \UserBase\Server\Repository\PdoEventRepository($pdo);
        $this->apikeyRepository = new PdoApikeyRepository($pdo);
        $this->accountPropertyRepository = new PdoAccountPropertyRepository($pdo);
        $this->spaceRepository = new  PdoSpaceRepository($pdo);
        $this->blacklistRepository = new PdoBlacklistRepository($pdo);
        $this->tagRepository = new PdoTagRepository($pdo);
        $this->propertyRepository = new PdoPropertyRepository($pdo);
        $this->accountTagRepository  = new PdoAccountTagRepository($pdo);
        $this->accountConnectionRepository = new PdoAccountConnectionRepository($pdo);
        $this->accountNotificationRepository = new  PdoAccountNotificationRepository($pdo);
        $this->mobileAliasRepository = new PdoMobileAliasRepository($pdo);
        $this->accountAddressRepository = new PdoAccountAddressRepository($pdo);
        $this->accountEmailRepository = new PdoAccountEmailRepository($pdo);
        $this->inviteRepository = new PdoInviteRepository($pdo);

        $mailer = Service::mailer();

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

        if (isset($this['userbase.template_override'])) {
            if (file_exists($this['userbase.template_override'] . '/strings.yml')) {
                $lines = $parser->parse(file_get_contents($this['userbase.template_override'] . '/strings.yml'));
                foreach ($lines as $key => $value) {
                    $this->strings[$key] = $value;
                    //$this->strings[$key] = "#" . $value . "#";
                }
            }
        }
    }

    private function configureStrings()
    {
        if (!isset($this->config['userbase']['strings'])) {
            //default
            $this->config['userbase']['strings'] = array('app/strings.yml');
        }

        foreach ($this->config['userbase']['strings'] as $filename) {
            if ($filename[0] != '') {
                $filename = __DIR__ . '/../' . $filename;
            }
            $this->loadStrings($filename);
        }

        $this['translator.domains'] = array(
            'messages' => array(
                'en' => $this->strings
            )
        );
    }

    private function configureRoutes()
    {
        $locator = new FileLocator(array(__DIR__.'/../app/config'));
        $loader = new YamlFileLoader($locator);
        $this['routes'] = $loader->load('routes.yml');
    }

    private function configureTemplateEngine()
    {
        $this->register(new TwigServiceProvider(), array(
            'twig.path' => array(
                __DIR__.'/../templates/',
            ),
        ));


        if (isset($this['userbase.template_override'])) {
            $path = $this['userbase.template_override'] . '/preauth';
            if (file_exists($path)) {
                $this['twig.loader.filesystem']->addPath(
                    $path,
                    'PreAuth'
                );
            }
        }

        $path = __DIR__ . '/../templates/preauth';
        $this['twig.loader.filesystem']->addPath(
            $path,
            'PreAuth'
        );


        if (isset($this['userbase.template_override'])) {
            $path = $this['userbase.template_override'] . '/portal';
            if (file_exists($path)) {
                $this['twig.loader.filesystem']->addPath(
                    $path,
                    'Portal'
                );
            }
        }

        $path = __DIR__ . '/../templates/portal';
        $this['twig.loader.filesystem']->addPath(
            $path,
            'Portal'
        );
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

        $baseUrl = $this['userbase.baseurl'];
        $this['security.firewalls'] = array(
            'api' => array(
                'stateless' => true,
                'pattern' => '^/api',
                'http' => true,
                'users' => $this->getUserRepository(),
            ),
            'admin' => array(
                'stateless' => true,
                'pattern' => '^/admin',
                'http' => true,
                'users' => $this->getUserRepository(),
            ),
            'default' => array(
                'anonymous' => true,
                'pattern' => '^/',
                'form' => array(
                    'login_path' => $baseUrl . '/login',
                    'check_path' => '/login_check',
                    'always_use_default_target_path' => true,
                    'default_target_path' => $baseUrl . '/login/success'
                ),
                'logout' => array(
                    'logout_path' => '/logout',
                    'target_url' => $baseUrl . '/logout/success'
                ),
                'users' => $this->getUserRepository(),
            ),
        );
    }

    public function getUserRepository()
    {
        return $this->userRepository;
    }

    public function getOAuthRepository()
    {
        return $this->oauthRepository;
    }

    public function getAppRepository()
    {
        return $this->appRepository;
    }

    public function getAccountRepository()
    {
        return $this->accountRepository;
    }

    public function getIdentityRepository()
    {
        return $this->identityRepository;
    }

    public function getEventRepository()
    {
        return $this->eventRepository;
    }

    public function getApikeyRepository()
    {
        return $this->apikeyRepository;
    }

    public function getAccountPropertyRepository()
    {
        return $this->accountPropertyRepository;
    }

    public function getSpaceRepository()
    {
        return $this->spaceRepository;
    }

    public function getBlacklistRepository()
    {
        return $this->blacklistRepository;
    }

    public function getTagRepository()
    {
        return $this->tagRepository;
    }

    public function getPropertyRepository()
    {
        return $this->propertyRepository;
    }

    public function getAccountTagRepository()
    {
        return $this->accountTagRepository;
    }

    public function getAccountConnectionRepository()
    {
        return $this->accountConnectionRepository;
    }

    public function getAccountNotificationRepository()
    {
        return $this->accountNotificationRepository;
    }

    public function getMobileAliasRepository()
    {
        return $this->mobileAliasRepository;
    }

    public function getAccountAddressRepository()
    {
        return $this->accountAddressRepository;
    }

    public function getAccountEmailRepository()
    {
        return $this->accountEmailRepository;
    }

    public function getInviteRepository()
    {
        return $this->inviteRepository;
    }

    public function sendMail($templateName, $username)
    {
        $userRepo = $this->getUserRepository();
        $accountRepo = $this->getAccountRepository();
        $user = $userRepo->getByName($username);
        $account = $accountRepo->getByName($username);

        $salt = $this['userbase.salt'];
        $stamp = time();
        $baseUrl = $this['userbase.baseurl'];

        $verifyToken = sha1($stamp . ':' . $account->getEmail() . ':' . $salt);
        $link = $baseUrl . '/verify/email/' . $account->getName() . '/' . $stamp . '/' . $verifyToken;

        $data = array();
        $data['link'] = $link;
        $data['username'] = $username;
        $data['display_name'] = $account->getDisplayName();
        $this['mailer']->sendTemplate($templateName, $account, $data);
    }



    public function sendSms($templateName, $username, $data = array())
    {
        if (!$this['sms.provider']) {
            throw new RuntimeException("No SMS provider configured");
        }
        $userRepo = $this->getUserRepository();
        $accountRepo = $this->getAccountRepository();
        $user = $userRepo->getByName($username);
        $account = $accountRepo->getByName($username);
        $mobileAliasRepo = $this->getMobileAliasRepository();

        $stamp = time();

        $data['username'] = $username;

        $apiKey = $this['sms.apikey'];
        $gw = new MessageBirdGateway($apiKey);
        $service = new SmsService($gw);

        $sender = $this['sms.sender'];
        $mobile = $account->getMobile();
        $mobile = $mobileAliasRepo->resolveAlias($mobile);

        $message='code: ' . $data['code'];

        $msg = new SmsMessage($message, $sender, $mobile);
        $service->send($msg);
    }
}
