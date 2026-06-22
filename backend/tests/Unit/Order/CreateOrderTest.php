<?php

namespace Tests\Unit\Order;

use App\Order\Application\CreateOrder\CreateOrder;
use App\Order\Application\CreateOrder\CreateOrderResponse;
use App\Order\Domain\Entity\Order;
use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use Mockery;
use PHPUnit\Framework\TestCase;

class CreateOrderTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_invoke_creates_order_saves_via_repository_and_returns_response(): void
    {
        $repository = Mockery::mock(OrderRepositoryInterface::class);

        $repository->shouldReceive('save')
            ->once()
            ->with(Mockery::on(function (Order $order) {
                return $order->restaurantId()->value() === '1'
                    && $order->tableId()->value() === 'table-uuid'
                    && $order->openedByUserId()->value() === 'user-uuid'
                    && $order->diners()->value() === 2
                    && $order->status()->value() === 'open'
                    && $order->closedByUserId() === null
                    && $order->closedAt() === null;
            }));

        $createOrder = new CreateOrder($repository);
        $response = $createOrder('1', 'table-uuid', 'user-uuid', 2);

        $this->assertInstanceOf(CreateOrderResponse::class, $response);
        $this->assertSame('1', $response->restaurantId);
        $this->assertSame('open', $response->status);
        $this->assertSame('table-uuid', $response->tableId);
        $this->assertSame('user-uuid', $response->openedByUserId);
        $this->assertNull($response->closedByUserId);
        $this->assertSame(2, $response->diners);
        $this->assertNull($response->closedAt);
    }
}
