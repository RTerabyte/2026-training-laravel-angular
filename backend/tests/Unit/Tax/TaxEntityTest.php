<?php

namespace Tests\Unit\Tax;

use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\RestaurantId;
use App\Tax\Domain\Entity\Tax;
use App\Tax\Domain\ValueObject\TaxName;
use App\Tax\Domain\ValueObject\TaxPercentage;
use PHPUnit\Framework\TestCase;

class TaxEntityTest extends TestCase
{
    public function test_ddd_create_builds_entity_with_attributes_and_vos(): void
    {
        $tax = Tax::dddCreate(
            RestaurantId::create('1'),
            TaxName::create('IVA General'),
            TaxPercentage::create(21),
        );

        $this->assertInstanceOf(Tax::class, $tax);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $tax->id()->value(),
        );
        $this->assertSame('1', $tax->restaurantId()->value());
        $this->assertSame('IVA General', $tax->name()->value());
        $this->assertSame(21, $tax->percentage()->value());
        $this->assertInstanceOf(DomainDateTime::class, $tax->createdAt());
        $this->assertInstanceOf(DomainDateTime::class, $tax->updatedAt());
        $this->assertEqualsWithDelta(
            $tax->createdAt()->value()->getTimestamp(),
            $tax->updatedAt()->value()->getTimestamp(),
            1,
        );
    }
}
