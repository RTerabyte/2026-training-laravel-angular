<?php

namespace App\Family\Application\GetFamily;

use App\Family\Domain\Exception\FamilyNotFoundException;
use App\Family\Domain\Interfaces\FamilyRepositoryInterface;

final class GetFamily
{
    public function __construct(
        private FamilyRepositoryInterface $familyRepository,
    ) {}

    public function __invoke(string $id): GetFamilyResponse
    {
        $family = $this->familyRepository->findById($id);

        if ($family === null) {
            throw FamilyNotFoundException::withId($id);
        }

        return GetFamilyResponse::create($family);
    }
}
