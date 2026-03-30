<?php

namespace App\Family\Application\ActivateFamily;

use App\Family\Application\ActivateFamily\ActivateFamilyResponse;
use App\Family\Domain\Exception\FamilyNotFoundException;
use App\Family\Domain\Interfaces\FamilyRepositoryInterface;

final class ActivateFamily
{
    public function __construct(
        private FamilyRepositoryInterface $familyRepository,
    ) {}

    public function __invoke(string $id): ActivateFamilyResponse
    {
        $family = $this->familyRepository->findById($id);

        if ($family === null) {
            throw FamilyNotFoundException::withId($id);
        }

        $family = $family->activate();
        $this->familyRepository->save($family);

        return ActivateFamilyResponse::create($family);
    }
}
