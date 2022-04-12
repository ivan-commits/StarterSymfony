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

final class SecurityService extends BaseService
{
    private $passwordHasher;
    private $emailVerifier;

    public function __construct(UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager, ResultService $result, EmailVerifier $emailVerifier){
        parent::__construct($entityManager,$result);
        $this->passwordHasher = $passwordHasher;
        $this->emailVerifier = $emailVerifier;
    }

    public function authenticateUser(array $credentials) : bool
    {
        //check payload
        $result = !empty($credentials['email']) && !empty($credentials['password']);

        //check exist user by email
        if($result){
            $user = $this->entityManager->getRepository(User::class)->findOneBy(['email'=>$credentials['email']]);
            $result = $user !== null && !$user->isVerified() && !$user->isBanned();
        }

        //check password is valid
        if($result){
            $isValidPassword = $this->passwordHasher->isPasswordValid($user,$credentials['password']);
            $result = $isValidPassword;
        }

        return $result;
    }

    public function register(Request $request, FormInterface $form) : ResultService
    {
        $user = $form->getData();

        $this->result->setError(!$user instanceof User);

        $messageFlash = "";
        $messageType = "";
        
        if(!$this->result->hasError()) {
            $form->handleRequest($request);

            $formIsSubmitted = $form->isSubmitted();
            $formIsValid = $form->isValid();
            $this->result->setError(!$formIsSubmitted || !$formIsValid);

            $messageFlash = !$this->result->hasError() ? SecurityConstant::success_register : $form['email']->getErrors()->__toString() ?? 'error' ;
            $messageType = !$this->result->hasError() ? 'success' : 'error';
        }

        if (!$this->result->hasError()) {
            $user->setPassword($this->passwordHasher->hashPassword($user, $form->get('plainPassword')->getData()));

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $this->sendEmailConfirmation($user);
        }

        $this->result->setData(compact('messageType','messageFlash'));

        return $this->result;
    }

    private function sendEmailConfirmation($user){
        $this->emailVerifier->sendEmailConfirmation('app_verify_email', $user,
            (new TemplatedEmail())
                ->from(new Address('registration@tchat.com', 'ivan'))
                ->to($user->getEmail())
                ->subject('Please Confirm your Email')
                ->htmlTemplate('registration/confirmation_email.html.twig')
        );
    }
}