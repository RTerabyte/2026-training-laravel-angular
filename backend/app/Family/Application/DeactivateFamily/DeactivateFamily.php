<?php

namespace App\Family\Application\DeactivateFamily;

use App\Family\Application\DeactivateFamily\DeactivateFamilyResponse;
use App\Family\Domain\Exception\FamilyNotFoundException;
use App\Family\Domain\Interfaces\FamilyRepositoryInterface;

final class DeactivateFamily
{
    public function __construct(
        private FamilyRepositoryInterface $familyRepository,
    ) {}

    public function __invoke(string $id): DeactivateFamilyResponse
    {
        $family = $this->familyRepository->findById($id);

        if ($family === null) {
            throw FamilyNotFoundException::withId($id);
        }

        $family = $family->deactivate();
        $this->familyRepository->save($family);

        return DeactivateFamilyResponse::create($family);
    }
}
