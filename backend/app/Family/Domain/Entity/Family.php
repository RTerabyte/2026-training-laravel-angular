<?php

namespace App\Family\Domain\Entity;

use App\Family\Domain\ValueObject\FamilyName;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;

class Family
{
   private function __construct(
    private Uuid $id,
    private FamilyName $name,
    private string $restaurantId,
    private bool $active,
    private DomainDateTime $createdAt,
    private DomainDateTime $updatedAt,
    ) {}

    public static function dddcreate(string $name, string $restaurantId): self
    {
        $now = DomainDateTime::now();

        return new self(
            Uuid::generate(),
            FamilyName::create($name),
            $restaurantId,
            true,
            $now,
            $now,
        );
    }

    public static function fromPersistence(
        string $id,
        string $name,
        string $restaurantId,
        bool $active,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            Uuid::create($id),
            FamilyName::create($name),
            $restaurantId,
            $active,
            DomainDateTime::create($createdAt),
            DomainDateTime::create($updatedAt),
        );
    }

    public function id(): Uuid
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name->value();
    }

    public function restaurantId(): string
    {
        return $this->restaurantId;
    }

    public function active(): bool
    {
        return $this->active;
    }

    public function createdAt(): DomainDateTime
    {
        return $this->createdAt;
    }

    public function updatedAt(): DomainDateTime
    {
        return $this->updatedAt;
    }
}