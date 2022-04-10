<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class SecurityService extends BaseService
{
    private $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager){
        parent::__construct($entityManager);
        $this->passwordHasher = $passwordHasher;
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
}