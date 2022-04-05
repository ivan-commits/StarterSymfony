<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\AccountStatusException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserCheckerCustom implements UserCheckerInterface
{

    public function checkPreAuth(UserInterface $user)
    {
        $this->checkAuth($user);
    }

    public function checkPostAuth(UserInterface $user)
    {
        $this->checkAuth($user);
    }

    private function checkAuth(UserInterface $user){
        if(!$user instanceof User){
            return;
        }
        if($user->isBanned()){
            throw new CustomUserMessageAuthenticationException('Pas authorisé');
        }
    }
}