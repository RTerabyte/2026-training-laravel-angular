<?php

namespace App\Family\Application\CreateFamily;

use App\Family\Domain\Entity\Family;
use App\Family\Domain\Interfaces\FamilyRepositoryInterface;

class CreateFamily
{
    public function __construct(
        private FamilyRepositoryInterface $familyRepository,
    ) {}

    public function __invoke(string $name, string $restaurantId): CreateFamilyResponse
    {
        $family = Family::dddCreate($name, $restaurantId);
        $this->familyRepository->save($family);

        return CreateFamilyResponse::create($family);
    }
}