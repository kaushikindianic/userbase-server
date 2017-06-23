<?php

namespace UserBase\Server\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Exception;
use UserBase\Server\Model\Account;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Constraints as Assert;
use UserBase\Server\Model\Event;
use UserBase\Server\Model\Apikey;
use UserBase\Server\Model\AccountProperty;
use UserBase\Server\Export\Accounts;
use DataTable\Core\Table;
use DataTable\Core\Reader\Csv as CsvReader;
use UserBase\Server\Model\AccountTag;
use UserBase\Server\Model\AccountEmail;
use UserBase\Server\Domain;

class AccountAdminController
{
    public function accountListAction(Application $app, Request $request)
    {
        $search = $request->request->get('searchText');
        $accountType = $request->get('accountType');

        $accounts = $app->getAccountRepository()->getAll(10, $search, $accountType);

        // Enrich accounts with tagNames
        $accountTags = $app->getAccountTagRepository()->findAll();
        foreach ($accountTags as $accountTag) {
            if (isset($accounts[$accountTag['account_name']])) {
                $accounts[$accountTag['account_name']]->addTagName($accountTag['tag_name']);
            }
        }

        $totalUsers = $app->getAccountRepository()->countBy('user');
        $totalOrganizations = $app->getAccountRepository()->countBy('organization');
        $totalAPIKeys = $app->getAccountRepository()->countBy('apikey');

        return new Response($app['twig']->render('admin/account_list.html.twig', array(
            'accounts' => $accounts,
            'accountCount' => count($accounts),
            'searchText' => $search,
            'totalUsers' => $totalUsers,
            'totalOrganizations' => $totalOrganizations,
            'totalAPIKeys' => $totalAPIKeys,
            'accountType' => $accountType,
        )));
    }

    public function accountAddAction(Application $app, Request $request)
    {
        return $this->accountEditForm($app, $request, null);
    }

    public function accountEditAction(Application $app, Request $request, $accountname)
    {
        return $this->accountEditForm($app, $request, $accountname);
    }

    public function accountDeleteAction(Application $app, Request $request, $accountname)
    {
        $repo = $app->getAccountRepository();
        $repo->delete($accountname);

        //--EVENT LOG --//
        $time = time();
        $sEventData = json_encode(array('accountname' => $accountname, 'time' => $time));

        $oEvent = new Event();
        $oEvent->setName($accountname);
        $oEvent->setEventName('account.delete');
        $oEvent->setOccuredAt($time);
        $oEvent->setData($sEventData);
        $oEvent->setAdminName($request->getUser());

        $oEventRepo = $app->getEventRepository();
        $oEventRepo->add($oEvent);
        //-- END EVENT LOG --//

        return $app->redirect($app['url_generator']->generate('admin_account_list'));
    }

    public function accountUsersAction(Application $app, Request $request, $accountname)
    {
        $error = $request->query->get('error');
        $oAccRepo = $app->getAccountRepository();

        if ($request->isMethod('POST')) {
            $userName = $request->get('delAssignUser');

            if ($userName) {
                $oAccRepo->delAccUsers($accountname, $userName);

                //--EVENT LOG --//
                $time = time();
                $sEventData = json_encode(
                    array('accountname' => $accountname, 'username' => $userName, 'time' => $time)
                );

                $oEvent = new Event();
                $oEvent->setName($userName);
                $oEvent->setEventName('user.unlinktoaccount');
                $oEvent->setOccuredAt($time);
                $oEvent->setData($sEventData);
                $oEvent->setAdminName($request->getUser());

                $oEventRepo = $app->getEventRepository();
                $oEventRepo->add($oEvent);
                //-- END EVENT LOG --//

                return $app->redirect($app['url_generator']->generate('admin_account_users', array(
                    'accountname' => $accountname,
                )));
            }
        }
        $aAccUsers = $oAccRepo->getUsersByAcount($accountname);

        return new Response($app['twig']->render('admin/account_users.html.twig', array(
            'accountName' => $accountname,
            'aAccUsers' => $aAccUsers,
            'error' => $error,
        )));
    }

    public function accountUserUpdateAction(Application $app, Request $request, $accountname)
    {
        $oAccRepo = $app->getAccountRepository();
        $username = $request->get('username');
        $isOwner = (int) $request->get('isOwner');

        if ($request->isMethod('POST') && !empty($username)) {
            $oAccRepo->updateAccUser($accountname, $username, $isOwner);
        }

        return new JsonResponse(array(
            'success' => true,
        ));
    }

    public function accountSearchUserAction(Application $app, Request $request, $accountname)
    {
        $searchUser = $request->get('searchAccUser');
        $oAccRepo = $app->getAccountRepository();

        if ($request->isMethod('POST')) {
            $userName = $request->get('userName');
            if ($userName) {
                $oAccRepo->addAccUser($accountname, $userName, 'group');

                //--EVENT LOG --//
                $time = time();
                $sEventData = json_encode(
                    array('accountname' => $accountname, 'username' => $userName, 'time' => $time)
                );

                $oEvent = new Event();
                $oEvent->setName($userName);
                $oEvent->setEventName('user.linktoaccount');
                $oEvent->setOccuredAt($time);
                $oEvent->setData($sEventData);
                $oEvent->setAdminName($request->getUser());

                $oEventRepo = $app->getEventRepository();
                $oEventRepo->add($oEvent);
                //-- END EVENT LOG --//

                return new JsonResponse(array(
                    'success' => true,
                ));
            }
        }
        $oUserRepo = $app->getUserRepository();
        $aUsers = $oUserRepo->getSearchUsers($searchUser);

        $oRes = new Response($app['twig']->render('admin/account_search_users.html.twig', array(
            'aUsers' => $aUsers,
        )));

        return new JsonResponse(array(
            'html' => $oRes->getContent(),
        ));
    }

    private function accountEditForm(Application $app, Request $request, $accountname)
    {
        $error = $request->query->get('error');
        $repo = $app->getAccountRepository();
        $add = false;

        $account = $repo->getByName($accountname);
        $accountTypes = [
            'organization' => 'Organization',
            'user' => 'User',
            'apikey' => 'API Key',
        ];
        // also support getting template by id
        if (!$account && is_numeric($accountname)) {
            $account = $repo->getById($accountname);
        }
        if ($account === null) {
            $defaults = null;
            $nameParam = array();
            $add = true;
        } else {
            $defaults = array(
                'name' => $account->getName(),
                'displayName' => $account->getRawDisplayName(),
                'about' => $account->getAbout(),
                'email' => $account->getEmail(),
                'mobile' => $account->getMobile(),
                'accountType' => $account->getAccountType(),
                'url' => $account->getUrl(),
                'expire_at' => $account->getExpireAt(),
                'approved_at' => $account->getApprovedAt(),
                'message' => $account->getMessage(),
                'status' => $account->getStatus(),
            );
        }
        //-- GENERATE FORM --//
        $aStatus = ['NEW' => 'NEW', 'ACTIVE' => 'ACTIVE', 'INACTIVE' => 'INACTIVE', 'EXPIRED' => 'EXPIRED'];

        $form = $app['form.factory']->createBuilder('form', $defaults);
        $form->add('name', 'text', array(
            'required' => true,
            'read_only' => ($add) ? false : true,
            'error_bubbling' => true,
            'attr' => array(
                'placeholder' => 'Name',
                (($add) ? 'autofocus' : '') => '',
            ),
        ))
        ->add('accountType', 'choice', array('required' => true,
            'label' => 'Account type',
            'trim' => true,
            'choices' => $accountTypes,
            'read_only' => ($add) ? false : true,
            'empty_data' => null,
            'empty_value' => '-- Select --',
            'attr' => array(
                'placeholder' => 'Account type',
                'class' => 'form-control',
            ),
        ))
        ->add('displayName', 'text', array(
            'required' => false,
            'label' => 'Display name',
            'attr' => array(
                'placeholder' => 'Display name',
                ((!$add) ? 'autofocus' : '') => '',
            ),
        ))
        ->add('email', 'email', array(
            'required' => true,
            'label' => 'E-mail',
            'trim' => true,
            'error_bubbling' => true,
            'constraints' => array(
                new Assert\NotBlank(array('message' => 'E-mail value should not be blank.')),
                new Assert\Email(),
            ),
            'attr' => array(
                'placeholder' => 'E-mail',
                'class' => 'form-control',
            ),
        ));
        if (!$add) {
            $form->add('email_verified', 'checkbox', array(
                'required' => false,
                'trim' => true,
                'label' => 'email verified',
                'data' => ($account->getEmailVerifiedAt()) ? true : false,
            ));
        }

        $form->add('mobile', 'text', array(
            'required' => false,
            'label' => 'Mobile',
            'trim' => true,
            'error_bubbling' => true,
            'constraints' => array(
            ),
            'attr' => array(
                'placeholder' => 'Mobile',
                'class' => 'form-control',
            ),
        ));

        if (!$add) {
            $form->add('mobile_verified', 'checkbox', array(
                'required' => false,
                'trim' => true,
                'label' => 'Mobile verified',
                'data' => ($account->getMobileVerifiedAt()) ? true : false,
            ));
        }

        $form->add('url', 'url', array(
            'required' => false,
            'label' => 'URL',
            'error_bubbling' => true,
            'attr' => array(
                'placeholder' => 'URL',
                'class' => 'form-control',
            ),
        ))
        ->add('about', 'text', array(
            'required' => false,
            'attr' => array(
                'placeholder' => 'About',
            ),
        ))
        ->add('expire_at', 'date', array(
            'required' => false,
            'input' => 'timestamp',
            'widget' => 'single_text',
            'attr' => array(
                'placeholder' => 'Expire date',
            ),
        ))
        ->add('message', 'textarea', array(
            'required' => false,
            'attr' => array(
                'placeholder' => 'Message to user',
            ),
        ))

        ->add('approved_at', 'date', array(
            'required' => false,
            'input' => 'timestamp',
            'widget' => 'single_text',
            'attr' => array(
                'placeholder' => 'Review date',
            ),
        ))

        ->add('status', 'choice', array(
            'required' => true,
            'label' => 'Status',
            'trim' => true,
            'choices' => $aStatus,
            'empty_data' => null,
            'empty_value' => '-- Select --',
            'attr' => array(
                'class' => 'form-control',
            ),
        ));
        $form = $form->getForm();

        // handle form submission
        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            $data = $form->getData();

            if ($form->isValid()) {
                if ($add) {
                    $account = new Account($data['name']);
                    $account->setAccountType($data['accountType']);
                    $userRepo = $app->getUserRepository();

                    switch ($data['accountType']) {
                        case 'user':
                            $user = $userRepo->register($app, $data['name'], $data['email']);
                            $user = $userRepo->getByName($data['name']);
                            $repo->addAccUser($data['name'], $data['name'], 'user');
                            break;
                    }
                    //$userRepo->setPassword($user, $formData['_password']);
                }

                $account
                    ->setDisplayName($data['displayName'])
                    ->setAbout($data['about'])
                    ->setMobile($data['mobile'])
                    ->setEmail($data['email'])
                    ->setUrl($data['url'])
                    ->setApprovedAt($data['approved_at'])
                    ->setMessage($data['message'])
                    ->setExpireAt($data['expire_at'])
                    ->setStatus($data['status'])
                ;

                if ($add) {
                    if (!$repo->add($account)) {
                        return $app->redirect($app['url_generator']->generate('admin_account_add', array(
                            'error' => 'Name exists',
                        )));
                    }
                    //-- ASSIGN MEMEBR TO USER --//
                    //$repo->addAccUser($data['name'], $request->getUser(), 1);

                    //--EVENT LOG --//
                    $time = time();
                    $sEventData = json_encode(
                        array('accountname' => $data['name'], 'displayName' => $data['displayName'], 'time' => $time)
                    );

                    $oEvent = new Event();
                    $oEvent->setName($data['name']);
                    $oEvent->setEventName('account.create');
                    $oEvent->setOccuredAt($time);
                    $oEvent->setData($sEventData);
                    $oEvent->setAdminName($request->getUser());

                    $oEventRepo = $app->getEventRepository();
                    $oEventRepo->add($oEvent);
                    //-- END EVENT LOG --//
                } else {
                    $repo->update($account);
                    $repo->setEmailVerifiedStamp($account, (($data['email_verified']) ? time() : 0));
                    $repo->setMobileVerifiedStamp($account, (($data['mobile_verified']) ? time() : 0));

                    //--EVENT LOG --//
                    $time = time();
                    $sEventData = json_encode(
                        array('accountname' => $data['name'], 'displayName' => $data['displayName'], 'time' => $time)
                    );

                    $oEvent = new Event();
                    $oEvent->setName($data['name']);
                    $oEvent->setEventName('account.update');
                    $oEvent->setOccuredAt($time);
                    $oEvent->setData($sEventData);
                    $oEvent->setAdminName($request->getUser());

                    $oEventRepo = $app->getEventRepository();
                    $oEventRepo->add($oEvent);
                    //-- END EVENT LOG --//
                }

                return $app->redirect($app['url_generator']->
                generate('admin_account_view', ['accountname' => $account->getName()]));
            }
        }

        return new Response($app['twig']->render('admin/account_edit.html.twig', array(
            'form' => $form->createView(),
            'account' => $account,
            'error' => $error,
        )));
    }

    public function accountViewAction(Application $app, Request $request, $accountname)
    {
        $accountRepo = $app->getAccountRepository();
        $account = $accountRepo->getByName($accountname);
        // also support getting template by id
        if (!$account && is_numeric($accountname)) {
            $account = $repo->getById($accountname);
        }

        $apikeys = $accountRepo->getAccountUsersByType($accountname, 'apikey');
        $users = $accountRepo->getAccountUsersByType($accountname, 'user');
        $organizations = $accountRepo->getUserAccountsByType($accountname, 'organization');

        $accountPropertyRepository = $app->getAccountPropertyRepository();
        $accountProperties = $accountPropertyRepository->getByAccountName($accountname);

        $oAccountTagRepo = $app->getAccountTagRepository();
        $aAssignTags = $oAccountTagRepo->findByAccountName($accountname);

        $oAccountConnectionRepo = $app->getAccountConnectionRepository();
        $totalAccountConnect = $oAccountConnectionRepo->totConnection($accountname);

        $oPropertyRepo = $app->getPropertyRepository();
        $properties = $oPropertyRepo->findAll();

        $oEventRepo = $app->getEventRepository();
        $events = $oEventRepo->findByAccountName($accountname);

        if ($request->query->has('email')) {
            $email = $request->query->get('email');
            $app->sendMail($email, $accountname);
        }

        return new Response(
            $app['twig']->render(
                'admin/account_view.html.twig',
                array(
                    'account' => $account,
                    'apikeys' => $apikeys,
                    'users' => $users,
                    'organizations' => $organizations,
                    'accountProperties' => $accountProperties,
                    'aAssignTags' => $aAssignTags,
                    'properties' => $properties,
                    'events' => $events,
                    'totalAccountConnect' => $totalAccountConnect,
                )
            )
        );
    }

    private function generateRandomString($length = 10)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; ++$i) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }

        return $randomString;
    }

    public function addApikeyAction(Application $app, Request $request, $accountname)
    {
        $repo = $app->getUserRepository();
        $accountRepo = $app->getAccountRepository();
        $username = 'apikey-'.$accountname.'-'.$this->generateRandomString(16);
        $email = '';
        $password = $this->generateRandomString(32);

        //--CREATE PERSONAL ACCOUNT--//
        $keyAccount = new Account($username);
        $keyAccount
            ->setDisplayName('')
            ->setAbout('')
            ->setPictureUrl('')
            ->setAccountType('apikey')
            ->setEmail('x@example.web')
            ->setMobile('01234')
            ->setStatus('ACTIVE')
        ;

        $accountRepo->add($keyAccount);

        try {
            $user = $repo->register($app, $username, $email);
        } catch (Exception $e) {
            return $app->redirect(
                $app['url_generator']->generate(
                    'admin_account_view',
                    ['accountname' => $accountname]
                ).'?errorcode=E34'
            );
        }
        $user = $repo->getByName($username);

        $repo->setPassword($user, $password);

        $accountRepo->addAccUser($user->getUsername(), $user->getUsername(), 'apikey');
        $accountRepo->addAccUser($accountname, $user->getUsername(), 'apikey');

        //--EVENT LOG --//
        $time = time();
        $sEventData = json_encode(array('username' => $accountname, 'apikey' => $username, 'time' => $time));

        $oEvent = new Event();
        $oEvent->setName($accountname);
        $oEvent->setEventName('apikey.create');
        $oEvent->setOccuredAt($time);
        $oEvent->setData($sEventData);
        $oEvent->setAdminName('');

        $oEventRepo = $app->getEventRepository();
        $oEventRepo->add($oEvent);

        //echo $username . ':' . $password;
        return new Response(
            $app['twig']->render(
                'admin/account_addapikey.html.twig',
                array(
                    'account' => $accountRepo->getByName($accountname),
                    'apikey' => $username,
                    'secret' => $password,
                )
            )
        );

        //return $this->apikeyForm($app, $request, $accountname, 0);
    }

    public function editApikeyAction(Application $app, Request $request, $accountname, $id)
    {
        return $this->apikeyForm($app, $request, $accountname, $id);
    }

    private function apikeyForm($app, $request, $accountname, $id)
    {
        $error = $request->query->get('error');
        $repo = $app->getAccountRepository();
        $oApiKeyRepo = $app->getApikeyRepository();
        $add = false;

        $account = $repo->getByName($accountname);
        // also support getting template by id
        if (!$account && is_numeric($accountname)) {
            $account = $repo->getById($accountname);
        }

        if ($id) {
            if (!$aApikey = $oApiKeyRepo->getById($id)) {
                return $app->redirect($app['url_generator']->generate('admin_account_view', array(
                        'accountname' => $accountname,
                 )));
            }
            $defaults = [
                'name' => $aApikey['name'],
                'username' => $aApikey['username'],
                'password' => $aApikey['password'],
            ];
            $nameParam = array();
        } else {
            $defaults = null;
            $nameParam = array();
            $add = true;
        }

        $form = $app['form.factory']->createBuilder('form', $defaults)
        ->add('name', 'text', $nameParam)
        ->add('username', 'text', array('required' => false, 'label' => 'username'))
        ->add('password', 'password', array('required' => false, 'always_empty' => false))
        ->getForm();

        // handle form submission
        $form->handleRequest($request);
        if ($form->isValid()) {
            $data = $form->getData();
            $oApikeyModel = new Apikey($data['name']);

            if ($add) {
                $oApikeyModel->setName($data['name'])
                    ->setUserName($data['username'])
                    ->setPassword($data['password'])
                    ->setCreatedAt(date('Y-m-d H:i:s'))
                    ->setAccountName($accountname);
            } else {
                $oApikeyModel->setId($id)
                    ->setName($data['name'])
                    ->setUserName($data['username'])
                    ->setPassword((empty($data['password']) ? $defaults['password'] : $data['password']))
                    ->setAccountName($accountname);
            }
            if ($add) {
                if (!$oApiKeyRepo->add($oApikeyModel)) {
                    return $app->redirect($app['url_generator']->generate('admin_account_view', array(
                        'error' => 'Failed adding Apikey',
                        'accountname' => $accountname,
                    )));
                }
            } else {
                $oApiKeyRepo->update($oApikeyModel);
            }

            return $app->redirect($app['url_generator']->generate('admin_account_view', array(
                'accountname' => $accountname,
            )));
        }

        return new Response($app['twig']->render('admin/account_apikey_add.html.twig', array(
            'form' => $form->createView(),
            'account' => $account,
            'add' => $add,
            'error' => $error,
        )));
    }

    public function apikeysAction(Application $app, Request $request)
    {
        $oApiKeyRepo = $app->getApikeyRepository();
        $aApikeys = $oApiKeyRepo->getAll();

        return new Response($app['twig']->render('admin/account_apikey_list.html.twig', array(
            'account' => $account,
            'aApikeys' => $aApikeys,
        )));
    }

    public function addPropertyAction(Application $app, Request $request, $accountname)
    {
        $command = new Domain\AccountProperty\SetCommand(
            $accountname,
            $request->request->get('property_name'),
            $request->request->get('property_value')
        );
        $bus = $app['commandbus'];
        $bus->handle($command);

        return $app->redirect($app['url_generator']->generate('admin_account_view', ['accountname' => $accountname]));
    }

    public function deletePropertyAction(Application $app, Request $request, $accountname, $propertyName)
    {
        $command = new Domain\AccountProperty\UnsetCommand(
            $accountname,
            $propertyName
        );
        $bus = $app['commandbus'];
        $bus->handle($command);

        return $app->redirect($app['url_generator']->generate('admin_account_view', ['accountname' => $accountname]));
    }

    public function accountExportAction(Application $app, Request $request)
    {
        $export = new Accounts($app);

        return $export->csvExport();
    }

    public function accountImportAction(Application $app, Request $request)
    {
        $error = $request->get('error');
        $accountPropertyRepository = $app->getAccountPropertyRepository();

        // -- GENERATE FORM --//
        $form = $app['form.factory']->createBuilder('form')
            ->add('attachment', 'file', array(
                'required' => true,
                'read_only' => false,
                'label' => false,
                'trim' => true,
                'error_bubbling' => true,
                'multiple' => false,
                'constraints' => array(
                    new Assert\NotBlank(array('message' => 'upload csv file.')),
                ),
                'attr' => array(
                    'id' => 'attachment',
                    'placeholder' => 'upload csv file',
                    //'class' => 'form-control',
                    'autofocus' => '',
                ),
            ))
            ->getForm();

        // -- HANDAL FORM SUBMIT --//
        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            $formData = $form->getData();

            if ($form->isValid()) {
                $file = $form['attachment']->getData();
                $table = new Table();
                $table->setName($file->getPathname());

                // Instantiate a Reader, in this case a .csv file reader
                $reader = new CsvReader();
                $reader->setSeperator(';');
                $reader->loadFile($table, $file->getPathname());

                $accountRepo = $app->getAccountRepository();
                $oAccountTagRepo = $app->getAccountTagRepository();
                $accountPropertyRep = $app->getAccountPropertyRepository();
                $oEventRepo = $app->getEventRepository();
                $oAccountEmailRepo = $app->getAccountEmailRepository();

                $tags = $app->getTagRepository()->findAll();
                $properties = $app->getPropertyRepository()->findAll();

                foreach ($table->getRows() as $row) {
                    $accountname = $row->getValueByColumnName('name');
                    if ($oAccount = $accountRepo->getByName($accountname)) {
                        //-- update account details --//
                        $oAccount->setDisplayName($row->getValueByColumnName('display_name'))
                            ->setAbout($row->getValueByColumnName('about'))
                    //      ->setCreatedAt(strtotime($row->getValueByColumnName('created_at')))
                            ->setStatus($row->getValueByColumnName('status'))
                            ->setUrl($row->getValueByColumnName('url'))
                            ->setMobile($row->getValueByColumnName('mobile'))
                            ;
                        $accountRepo->update($oAccount);

                        $accountRepo->setMobileVerifiedStamp(
                            $oAccount,
                            (($row->getValueByColumnName('mobile_verfied_at')) ? strtotime($row->getValueByColumnName('mobile_verfied_at')) : 0)
                        );

                        //-- add email --//
                        $oAccountEmailModel = new AccountEmail();

                        if ($oAccountEmail = $oAccountEmailRepo->findByEmail($row->getValueByColumnName('email'))) {
                            if ($oAccountEmail[0]['account_name'] == $accountname) {
                                $oAccountEmailModel->setId($oAccountEmail[0]['id'])
                                    ->setEmail($oAccountEmail[0]['email'])
                                    ->setVerifiedAt((($row->getValueByColumnName('email_verified_at')) ? strtotime($row->getValueByColumnName('email_verified_at')) : 0));
                                $oAccountEmailRepo->update($oAccountEmailModel);
                            }
                            if ($oAccount->getEmail() == $row->getValueByColumnName('email')) {
                                $accountRepo->setEmailVerifiedStamp(
                                    $oAccount,
                                    (($row->getValueByColumnName('email_verified_at')) ? strtotime($row->getValueByColumnName('email_verified_at')) : 0)
                                );
                            }
                        } else {
                            $oAccountEmailModel
                                ->setAccountName($accountname)
                                ->setEmail($row->getValueByColumnName('email'))
                                ->setVerifiedAt((($row->getValueByColumnName('email_verified_at')) ? strtotime($row->getValueByColumnName('email_verified_at')) : 0));
                            $oAccountEmailRepo->add($oAccountEmailModel);
                        }

                        //--EVENT LOG --//
                        $time = time();
                        $sEventData = json_encode(
                            array('accountname' => $accountname, 'displayName' => $row->getValueByColumnName('display_name'), 'time' => $time)
                        );
                        $oEvent = new Event();
                        $oEvent->setName($accountname);
                        $oEvent->setEventName('account.update');
                        $oEvent->setOccuredAt($time);
                        $oEvent->setData($sEventData);
                        $oEvent->setAdminName($request->getUser());

                        $oEventRepo = $app->getEventRepository();
                        $oEventRepo->add($oEvent);

                        //-- add/update/remove account tags --//
                        foreach ($tags as $tag) {
                            if (0 == strcasecmp('Y', trim($row->getValueByColumnName('tag.'.$tag['name'])))) {
                                $oAccountTagModel = new AccountTag();
                                $oAccountTagModel->setAccountName($accountname)->setTagId($tag['id']);
                                $oAccountTagRepo->add($oAccountTagModel);
                            } else {
                                $oAccountTagRepo->deleteByAccountNameAndTagId($accountname, $tag['id']);
                            }
                        }
                        //-- add account email --//
                        //-- add/update/remove account property --//
                        foreach ($properties as $property) {
                            $value = trim($row->getValueByColumnName('property.'.$property['name']));
                            if ($value) {
                                $entity = new AccountProperty();
                                $entity->setAccountName($accountname)
                                        ->setName($property['name'])
                                        ->setValue($value);
                                $accountPropertyRep->insertOrUpdate($entity);
                            } else {
                                $accountPropertyRep->deleteByAccountNameAndName($accountname, $property['name']);
                            }
                        }
                    }
                }

                return $app->redirect($app['url_generator']->generate('admin_account_list'));
            }
        }

        return new response($app['twig']->render('admin/account_import.html.twig', array(
            'form' => $form->createView(),
            'form_url' => '#',
            'error' => $error,
        )));
    }
}
