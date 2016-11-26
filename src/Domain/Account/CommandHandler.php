<?php

namespace UserBase\Server\Domain\Account;

use UserBase\Server\Domain;
use UserBase\Server\Application;
use UserBase\Server\Model\Account;
use UserBase\Server\Model\AccountProperty;
use UserBase\Server\Model\AccountTag;
use UserBase\Server\Model\AccountEmail;
use RuntimeException;
use Ramsey\Uuid\Uuid;

class CommandHandler
{
    protected $app; // bad! needed for user register
    protected $dispatcher;
    protected $accountRepo;
    protected $inviteRepo;
    protected $userRepo;
    protected $accountEmailRepo;
    
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->dispatcher = $app['dispatcher'];
        $this->accountRepo = $app->getAccountRepository();
        $this->userRepo = $app->getUserRepository();
        $this->inviteRepo = $app->getInviteRepository();
        $this->accountEmailRepo = $app->getAccountEmailRepository();
    }
    
    public function subscribe()
    {
        return [
            CreateCommand::class,
            ChangeEmailCommand::class,
            SignupCommand::class,
            VerifyCommand::class
        ];
    }
    
    public function handleCreate(CreateCommand $command)
    {
        // validations here, throw on failure
        $event = new CreatedEvent(
            $command->getAccountName(),
            $command->getAccountType()
        );
        $this->dispatcher->dispatch(CreatedEvent::class, $event);
    }
    
    public function handleChangeEmail(ChangeEmailCommand $command)
    {
        $event = new ChangedEmailEvent(
            $command->getAccountName(),
            $command->getEmail()
        );
        $this->dispatcher->dispatch(ChangedEmailEvent::class, $event);
    }
    
    public function handleSignup(SignupCommand $command)
    {
        //--REGISTER THE EMAIL--//
        $accountEmail = new AccountEmail();
        $accountEmail->setAccountName($command->getUsername());
        $accountEmail->setEmail($command->getEmail());
        $this->accountEmailRepo->add($accountEmail);
        
        //--REGISTER THE ACCOUNT--//
        $account = new Account($command->getUsername());
        $account
            ->setDisplayName($command->getDisplayName())
            ->setAbout('')
            ->setPictureUrl('')
            ->setAccountType('user')
            ->setEmail($command->getEmail())
            ->setMobile($command->getMobile())
            ->setStatus('NEW')
        ;

        if (!$this->accountRepo->add($account)) {
            throw new RuntimeException("Register failed: " . $command->getUsername());
        }

        
        try {
            $user = $this->userRepo->register($this->app, $command->getUsername(), $command->getEmail());
        } catch (Exception $e) {
            throw new RuntimeException("Register failed: " . $command->getUsername());
        }
        $user = $this->userRepo->getByName($command->getUsername());

        // Set selected password
        $this->userRepo->setPassword($user, $command->getPassword());
        // Link user to account
        $this->accountRepo->addAccUser($command->getUsername(), $command->getUsername(), 'user');
        
        // TAGS //
        if ($this->app['userbase.signup_tag']) {
            $tagRepo = $this->app->getTagRepository();
            $accountTagRepo = $this->app->getAccountTagRepository();

            $tagNames = explode(",", $this->app['userbase.signup_tag']);
            foreach ($tagNames as $tagName) {
                $tagData = $tagRepo->getByName($tagName);
                if (!$tagData) {
                    throw new RuntimeException("No such tag! " . $tagName);
                }
                $tagId = $tagData['id'];
                $accountTag = new AccountTag();
                $accountTag->setTagId($tagId);
                $accountTag->setAccountName($command->getUsername());
                $accountTagRepo->add($accountTag);
            }
        }
        
        // PROPERTIES //
        if ($this->app['userbase.signup_properties']) {
            foreach ($this->app['userbase.signup_properties'] as $name => $value) {
                if ($value=='{uuid}') {
                    $value = Uuid::uuid4();
                }
                $accountPropertyRepo = $this->app->getAccountPropertyRepository();
                $accountProperty = new AccountProperty();
                $accountProperty->setAccountName($command->getUsername());
                $accountProperty->setName($name);
                $accountProperty->setValue($value);
                $accountPropertyRepo->add($accountProperty);
            }
        }
        
        $event = new Domain\Account\SignedUpEvent(
            $command->getUsername(),
            $command->getEmail()
        );
        $this->dispatcher->dispatch(Domain\Account\SignedUpEvent::class, $event);
    }
    
    
    public function handleVerify(VerifyCommand $command)
    {
        $account = $this->accountRepo->getByName($command->getAccountName());
        $account->setStatus('ACTIVE');
        $this->accountRepo->update($account);
        
        // check if there's pending invites, tag them as activated
        $invites = $this->inviteRepo->findByEmail($account->getEmail());
        foreach ($invites as $invite) {
            $inviteId = $invite['id'];
            // update the invite with the selected username
            $this->inviteRepo->accept($inviteId, $command->getAccountName());
            // trigger new event? InviteAccepted
            
            $event = new Domain\Invite\AcceptedEvent(
                $inviteId,
                $command->getAccountName()
            );
            $this->dispatcher->dispatch(Domain\Invite\AcceptedEvent::class, $event);
        }
        
        $event = new VerifiedEvent(
            $command->getAccountName()
        );
        $this->dispatcher->dispatch(VerifiedEvent::class, $event);
    }
}
