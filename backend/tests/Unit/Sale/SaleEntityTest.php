<?php

namespace Tests\Unit\Sale;

use App\Sale\Domain\Entity\Sale;
use App\Sale\Domain\ValueObject\SaleTicketNumber;
use App\Sale\Domain\ValueObject\SaleTotal;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\OrderId;
use App\Shared\Domain\ValueObject\RestaurantId;
use App\Shared\Domain\ValueObject\UserId;
use PHPUnit\Framework\TestCase;

class SaleEntityTest extends TestCase
{
    public function test_ddd_create_builds_entity_with_attributes_and_vos(): void
    {
        $valueDate = DomainDateTime::now();

        $sale = Sale::dddCreate(
            RestaurantId::create('1'),
            OrderId::create('order-uuid'),
            UserId::create('user-uuid'),
            SaleTicketNumber::create(7),
            $valueDate,
            SaleTotal::create(500),
        );

        $this->assertInstanceOf(Sale::class, $sale);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $sale->id()->value(),
        );
        $this->assertSame('1', $sale->restaurantId()->value());
        $this->assertSame('order-uuid', $sale->orderId()->value());
        $this->assertSame('user-uuid', $sale->userId()->value());
        $this->assertSame(7, $sale->ticketNumber()?->value());
        $this->assertSame($valueDate->value(), $sale->valueDate()->value());
        $this->assertSame(500, $sale->total()->value());
        $this->assertInstanceOf(DomainDateTime::class, $sale->createdAt());
        $this->assertInstanceOf(DomainDateTime::class, $sale->updatedAt());
    }
}
