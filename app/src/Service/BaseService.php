<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;

abstract class BaseService
{
    protected $entityManager;
    protected $result;

    public function __construct(EntityManagerInterface $entityManager, ResultService $result){
        $this->entityManager = $entityManager;
        $this->result = $result;
    }
}