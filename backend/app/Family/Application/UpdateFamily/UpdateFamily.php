<?php

namespace App\Family\Application\UpdateFamily;

use App\Family\Domain\Exception\FamilyNotFoundException;
use App\Family\Domain\Interfaces\FamilyRepositoryInterface;
use App\Family\Domain\ValueObject\FamilyName;

final readonly class UpdateFamily
{
    public function __construct(
        private FamilyRepositoryInterface $familyRepository,
    ) {}

    public function __invoke(
        string $id,
        ?string $name = null,
        ?bool $active = null
    ): UpdateFamilyResponse {
        $family = $this->familyRepository->findById($id);

        if ($family === null) {
            throw FamilyNotFoundException::withId($id);
        }

        $nameVO = $name !== null
            ? FamilyName::create($name)
            : $family->name();

        $active = $active ?? $family->active();

        $family = $family->update(
            $nameVO,
            $active,
        );

        $this->familyRepository->save($family);

        return UpdateFamilyResponse::create($family);
    }
}
