<?php

namespace App\Family\Application\UpdateFamily;

use App\Family\Domain\Entity\Family;

final readonly class UpdateFamilyResponse
{
    public function __construct(
        public string $id,
        public string $name,
        public string $restaurantId,
        public bool $active,
        public string $createdAt,
        public string $updatedAt,
    ) {}

    public static function create(Family $family): self
    {
        return new self(
            id: $family->id()->value(),
            name: $family->name()->value(),
            restaurantId: $family->restaurantId()->value(),
            active: $family->active(),
            createdAt: $family->createdAt()->format(\DateTimeInterface::ATOM),
            updatedAt: $family->updatedAt()->format(\DateTimeInterface::ATOM),
        );
    }

    
}
