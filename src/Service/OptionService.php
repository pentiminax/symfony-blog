<?php

namespace App\Service;

use App\Entity\Option;
use App\Repository\OptionRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;

class OptionService
{
    public function __construct(
        private OptionRepository $optionRepo
    ) {}

    public function findAll(): array
    {
        return $this->optionRepo->findAllForTwig();
    }

    public function getValue(string $name): mixed
    {
        return $this->optionRepo->getValue($name);
    }
}