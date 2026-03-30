<?php

namespace App\Family\Application\UpdateFamily;

use App\Family\Domain\Exception\FamilyNotFoundException;
use App\Family\Domain\Interfaces\FamilyRepositoryInterface;

final class UpdateFamily
{
    public function __construct(
        private FamilyRepositoryInterface $familyRepository,
    ) {}

    public function __invoke(string $id, string $name): UpdateFamilyResponse
    {
        $family = $this->familyRepository->findById($id);

        if ($family === null) {
            throw FamilyNotFoundException::withId($id);
        }

        $family = $family->update($name, $family->active());
        $this->familyRepository->save($family);

        return UpdateFamilyResponse::create($family);
    }
}
