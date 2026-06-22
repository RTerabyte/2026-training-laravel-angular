<?php

namespace Tests\Unit\OrderLine;

use App\OrderLine\Domain\Entity\OrderLine;
use App\OrderLine\Domain\ValueObject\OrderLinePrice;
use App\OrderLine\Domain\ValueObject\OrderLineQuantity;
use App\OrderLine\Domain\ValueObject\OrderLineTaxPercentage;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\OrderId;
use App\Shared\Domain\ValueObject\ProductId;
use App\Shared\Domain\ValueObject\RestaurantId;
use App\Shared\Domain\ValueObject\UserId;
use PHPUnit\Framework\TestCase;

class OrderLineEntityTest extends TestCase
{
    public function test_ddd_create_builds_entity_with_attributes_and_vos(): void
    {
        $orderLine = OrderLine::dddCreate(
            RestaurantId::create('1'),
            OrderId::create('order-uuid'),
            ProductId::create('product-uuid'),
            UserId::create('user-uuid'),
            OrderLineQuantity::create(2),
            OrderLinePrice::create(250),
            OrderLineTaxPercentage::create(21),
        );

        $this->assertInstanceOf(OrderLine::class, $orderLine);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $orderLine->id()->value(),
        );
        $this->assertSame('1', $orderLine->restaurantId()->value());
        $this->assertSame('order-uuid', $orderLine->orderId()->value());
        $this->assertSame('product-uuid', $orderLine->productId()->value());
        $this->assertSame('user-uuid', $orderLine->userId()->value());
        $this->assertSame(2, $orderLine->quantity()->value());
        $this->assertSame(250, $orderLine->price()->value());
        $this->assertSame(21, $orderLine->taxPercentage()->value());
        $this->assertInstanceOf(DomainDateTime::class, $orderLine->createdAt());
        $this->assertInstanceOf(DomainDateTime::class, $orderLine->updatedAt());
    }
}
