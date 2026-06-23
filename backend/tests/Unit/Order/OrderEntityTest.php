<?php

namespace Tests\Unit\Order;

use App\Order\Domain\Entity\Order;
use App\Order\Domain\ValueObject\Diners;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\RestaurantId;
use App\Shared\Domain\ValueObject\TableId;
use App\Shared\Domain\ValueObject\UserId;
use PHPUnit\Framework\TestCase;

class OrderEntityTest extends TestCase
{
    public function test_ddd_create_builds_entity_with_attributes_and_vos(): void
    {
        $order = Order::dddCreate(
            RestaurantId::create('1'),
            TableId::create('table-uuid'),
            UserId::create('user-uuid'),
            Diners::create(2),
        );

        $this->assertInstanceOf(Order::class, $order);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $order->id()->value(),
        );
        $this->assertSame('1', $order->restaurantId()->value());
        $this->assertSame('open', $order->status()->value());
        $this->assertSame('table-uuid', $order->tableId()->value());
        $this->assertSame('user-uuid', $order->openedByUserId()->value());
        $this->assertNull($order->closedByUserId());
        $this->assertSame(2, $order->diners()->value());
        $this->assertInstanceOf(DomainDateTime::class, $order->openedAt());
        $this->assertNull($order->closedAt());
        $this->assertInstanceOf(DomainDateTime::class, $order->createdAt());
        $this->assertInstanceOf(DomainDateTime::class, $order->updatedAt());
    }
}
