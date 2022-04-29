<?php

namespace App\Service;

use App\Constant\SecurityConstant;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

final class SecurityService extends BaseService
{
    /** Property */
    private $passwordHashed;
    private $emailVerifier;

    /** Public function */
    public function __construct(UserPasswordHasherInterface $passwordHashed, EntityManagerInterface $entityManager, ResultService $result, EmailVerifier $emailVerifier){
        parent::__construct($entityManager,$result);
        $this->passwordHashed = $passwordHashed;
        $this->emailVerifier = $emailVerifier;
    }

    public function authenticateUser(array $credentials) : ?User
    {
        //check payload
        $result = !empty($credentials['email']) && !empty($credentials['password']);
        $user = null;
        //check exist user by email
        if($result){
            $user = $this->entityManager->getRepository(User::class)->findOneBy(['email'=>$credentials['email']]);
            $result = $user !== null;
        }

        //check password is valid
        if($result){
            $isValidPassword = $this->passwordHashed->isPasswordValid($user,$credentials['password']);
            $result = $isValidPassword;
        }

        if($result){
            return $user;
        }

        return null;
    }

    public function register(Request $request, FormInterface $form) : ResultService
    {
        $user = $form->getData();

        $this->result->setError(!$user instanceof User);

        $messageFlash = SecurityConstant::error_unknown;
        $messageType = SecurityConstant::flash_type_error;

        if(!$this->result->hasError()) {
            $form->handleRequest($request);
            $formIsSubmitted = $form->isSubmitted();
            $this->result->setError(!$formIsSubmitted);
        }

        if(!$this->result->hasError()){
            $formIsValid = $form->isValid();
            $this->result->setError(!$formIsValid);
            if($this->result->hasError()){
                $messageFlash = $form->getErrors(true)->__toString();
            }
        }

        if (!$this->result->hasError()) {
            $user->setPassword($this->passwordHashed->hashPassword($user, $form->get('plainPassword')->getData()));

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            try {
                $this->sendEmailConfirmation($user);
                $messageFlash = SecurityConstant::success_register;
                $messageType = SecurityConstant::flash_type_success;
            }catch (\Throwable $throwable){
                $this->entityManager->remove($user);
                $this->entityManager->flush();
                $this->result->setError(true);
            }
        }

        $this->result->setData(compact('messageType','messageFlash'));

        return $this->result;
    }

    public function verifyUserEmail(Request $request) : ResultService
    {
        $user = $this->entityManager->getRepository(User::class)->find($request->query->get('id',''));

        $this->result->setError(!$user instanceof User);

        $messageFlash = SecurityConstant::error_user_unknown;
        $messageType = SecurityConstant::flash_type_error;

        if (!$this->result->hasError()) {
            if($user->isBanned() || $user->isVerified()){
                $messageFlash = $user->isBanned() ? SecurityConstant::error_user_banned : SecurityConstant::error_email_already_verified;
                $this->result->setError(true);
            }
        }

        if (!$this->result->hasError()) {
            try {
                $this->emailVerifier->handleEmailConfirmation($request, $user);
                $messageFlash = SecurityConstant::success_register;
                $messageType = SecurityConstant::flash_type_success;
            } catch (VerifyEmailExceptionInterface $exception) {
                $this->result->setError(true);
                $messageFlash = $exception->getReason() ?? SecurityConstant::error_unknown;
            }
        }

        $this->result->setData(compact('messageType','messageFlash'));

        return $this->result;
    }

    public function resendConfirmationEmail(Request $request, FormInterface $form) : ResultService
    {
        $form->handleRequest($request);
        $this->result->setError(!$form->isSubmitted());

        $messageFlash = SecurityConstant::error_unknown;
        $messageType = SecurityConstant::flash_type_error;

        if (!$this->result->hasError()) {
            $formIsValid = $form->isValid();
            $this->result->setError(!$formIsValid);
            if($this->result->hasError()){
                $messageFlash = $form->getErrors(true)->__toString();
            }
        }

        if(!$this->result->hasError()){
            $credentials = ['email'=>$form->getData()['email'],'password'=>$form->getData()['password']];
            $user = $this->authenticateUser($credentials);
            $canAskEmailConfirmation = $this->canAskEmailConfirmation($user);

            if($user === null){
                $messageFlash = SecurityConstant::error_user_unknown;
            }elseif (!$canAskEmailConfirmation){
                $messageFlash = SecurityConstant::error_email_already_verified;
            }

            $this->result->setError($user === null || !$canAskEmailConfirmation);
        }

        if(!$this->result->hasError()){
            try {
                $this->sendEmailConfirmation($user);
                $messageFlash = SecurityConstant::success_send_confirmation_email;
                $messageType = SecurityConstant::flash_type_success;
            }catch (\Throwable $throwable){
                $this->result->setError(true);
            }
        }

        $this->result->setData(compact('messageType','messageFlash'));

        return $this->result;
    }

    public function forgotPassword(Request $request, FormInterface $form) : ResultService
    {
        $form->handleRequest($request);
        $this->result->setError(!$form->isSubmitted());

        $messageFlash = SecurityConstant::error_unknown;
        $messageType = SecurityConstant::flash_type_error;

        if (!$this->result->hasError()) {
            $formIsValid = $form->isValid();
            $this->result->setError(!$formIsValid);
            if($this->result->hasError()){
                $messageFlash = $form->getErrors(true)->__toString();
            }
        }

        if(!$this->result->hasError()){
            $email = $form->getData()['email'];
            $user = $this->entityManager->getRepository(User::class)->findOneBy(['email'=>$email]);

            if($user === null){
                $messageFlash = SecurityConstant::error_user_unknown;
                $this->result->setError(true);
            }
        }

        if(!$this->result->hasError()){
            try {
                $this->sendEmailRetrievePassword($user);
                $messageFlash = SecurityConstant::success_send_retrieve_password_email;
                $messageType = SecurityConstant::flash_type_success;
            }catch (\Throwable $throwable){
                dd($throwable);
                $this->result->setError(true);
            }
        }

        $this->result->setData(compact('messageType','messageFlash'));

        return $this->result;

    }

    public function retrievePassword(Request $request, FormInterface $form) : ResultService
    {
        $form->handleRequest($request);
        $this->result->setError(!$form->isSubmitted());

        $messageFlash = SecurityConstant::error_unknown;
        $messageType = SecurityConstant::flash_type_error;

        if (!$this->result->hasError()) {
            $formIsValid = $form->isValid();
            $this->result->setError(!$formIsValid);
            if($this->result->hasError()){
                $messageFlash = $form->getErrors(true)->__toString();
            }
        }

        if(!$this->result->hasError()){
            $user = $form->getData();
            $this->result->setError(!$this->isFullyAuthorizedUser($user));
        }


        if (!$this->result->hasError()) {
            $user->setPassword($this->passwordHashed->hashPassword($user,$user->getPassword()));
            try {
                $this->emailVerifier->handleEmailConfirmation($request, $user);
                $messageFlash = SecurityConstant::success_retrieve_password;
                $messageType = SecurityConstant::flash_type_success;
            } catch (VerifyEmailExceptionInterface $exception) {
                $this->result->setError(true);
                $messageFlash = $exception->getReason() ?? SecurityConstant::error_unknown;
            }

        }

        $this->result->setData(compact('messageType','messageFlash'));

        return $this->result;
    }

    /** Private function */
    private function sendEmailConfirmation($user) : void
    {
        $this->emailVerifier->sendEmailConfirmation('app_verify_email', $user,
            (new TemplatedEmail())
                ->from(new Address('registration@tchat.com', 'ivan'))
                ->to($user->getEmail())
                ->subject('Please Confirm your Email')
                ->htmlTemplate('registration/confirmation_email.html.twig')
        );
    }

    private function sendEmailRetrievePassword($user) : void
    {
        $this->emailVerifier->sendEmailConfirmation('app_retrieve_password', $user,
            (new TemplatedEmail())
                ->from(new Address('registration@tchat.com', 'ivan'))
                ->to($user->getEmail())
                ->subject('Retrieve Password')
                ->htmlTemplate('registration/retrieve_password_email.html.twig')
        );
    }

    private function isFullyAuthorizedUser(?User $user) : bool
    {
        return $user!==null && !$user->isBanned() && $user->isVerified();
    }

    private function canAskEmailConfirmation(?User $user) : bool
    {
        return $user!==null && !$user->isBanned() && !$user->isVerified();
    }
}