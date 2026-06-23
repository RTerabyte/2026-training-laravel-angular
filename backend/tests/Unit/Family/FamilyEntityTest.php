<?php

namespace Tests\Unit\Family;

use App\Family\Domain\Entity\Family;
use App\Family\Domain\ValueObject\FamilyName;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\RestaurantId;
use PHPUnit\Framework\TestCase;

class FamilyEntityTest extends TestCase
{
    public function test_ddd_create_builds_entity_with_attributes_and_vos(): void
    {
        $family = Family::dddCreate(
            RestaurantId::create('1'),
            FamilyName::create('Entrantes'),
        );

        $this->assertInstanceOf(Family::class, $family);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $family->id()->value(),
        );
        $this->assertSame('1', $family->restaurantId()->value());
        $this->assertSame('Entrantes', $family->name()->value());
        $this->assertTrue($family->active());
        $this->assertInstanceOf(DomainDateTime::class, $family->createdAt());
        $this->assertInstanceOf(DomainDateTime::class, $family->updatedAt());
        $this->assertEqualsWithDelta(
            $family->createdAt()->value()->getTimestamp(),
            $family->updatedAt()->value()->getTimestamp(),
            1,
        );
    }
}
