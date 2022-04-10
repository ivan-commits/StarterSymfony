<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;

abstract class BaseService
{
    protected $entityManager;

    public function __construct(EntityManagerInterface $entityManager){
        $this->entityManager = $entityManager;
    }
}