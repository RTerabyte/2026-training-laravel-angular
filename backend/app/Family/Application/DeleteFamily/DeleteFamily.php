<?php

namespace App\Family\Application\DeleteFamily;

use App\Family\Domain\Exception\FamilyNotFoundException;
use App\Family\Domain\Interfaces\FamilyRepositoryInterface;

final class DeleteFamily
{
    public function __construct(
        private FamilyRepositoryInterface $familyRepository,
    ) {}

    public function __invoke(string $id): void
    {
        $family = $this->familyRepository->findById($id);

        if ($family === null) {
            throw FamilyNotFoundException::withId($id);
        }

        $this->familyRepository->delete($family);
    }
}
