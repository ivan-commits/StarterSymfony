<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserCheckerCustom implements UserCheckerInterface
{

    public function checkPreAuth(UserInterface $user)
    {
        $this->checkAuth($user);
        $this->checkIsBanned($user);
        $this->checkIsVerified($user);
    }

    public function checkPostAuth(UserInterface $user)
    {
        $this->checkAuth($user);
        $this->checkIsBanned($user);
        $this->checkIsVerified($user);
    }

    private function checkAuth(UserInterface $user){
        if(!$user instanceof User){
            return;
        }
    }

    private function checkIsBanned(UserInterface $user){
        if(!$user instanceof User){
            return;
        }
        if($user->isBanned()){
            throw new CustomUserMessageAuthenticationException('You are not verified!');
        }
    }

    private function checkIsVerified(UserInterface $user){
        if(!$user instanceof User){
            return;
        }
        if(!$user->isVerified()){
            throw new CustomUserMessageAuthenticationException('You are not verified!');
        }
    }
}