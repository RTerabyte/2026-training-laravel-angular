<?php

namespace Tests\Unit\OrderLine;

use App\OrderLine\Application\CreateOrderLine\CreateOrderLine;
use App\OrderLine\Application\CreateOrderLine\CreateOrderLineResponse;
use App\OrderLine\Domain\Entity\OrderLine;
use App\OrderLine\Domain\Interfaces\OrderLineRepositoryInterface;
use App\OrderLineLog\Application\CreateOrderLineLog\CreateOrderLineLog;
use App\OrderLineLog\Domain\Entity\OrderLineLog;
use App\OrderLineLog\Domain\Interfaces\OrderLineLogRepositoryInterface;
use Mockery;
use PHPUnit\Framework\TestCase;

class CreateOrderLineTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_invoke_creates_order_line_saves_logs_and_returns_response(): void
    {
        $repository = Mockery::mock(OrderLineRepositoryInterface::class);
        $logRepository = Mockery::mock(OrderLineLogRepositoryInterface::class);
        $createOrderLineLog = new CreateOrderLineLog($logRepository);

        $repository->shouldReceive('save')
            ->once()
            ->with(Mockery::on(function (OrderLine $orderLine) {
                return $orderLine->restaurantId()->value() === '1'
                    && $orderLine->orderId()->value() === 'order-uuid'
                    && $orderLine->productId()->value() === 'product-uuid'
                    && $orderLine->userId()->value() === 'user-uuid'
                    && $orderLine->quantity()->value() === 2
                    && $orderLine->price()->value() === 250
                    && $orderLine->taxPercentage()->value() === 21;
            }));

        $logRepository->shouldReceive('save')
            ->once()
            ->with(Mockery::on(function (OrderLineLog $log) {
                return $log->restaurantId()->value() === '1'
                    && $log->orderId()->value() === 'order-uuid'
                    && $log->userId()->value() === 'user-uuid'
                    && $log->action() === 'created'
                    && $log->oldQuantity() === null
                    && $log->newQuantity() === 2
                    && $log->oldPrice() === null
                    && $log->newPrice() === 250;
            }));

        $createOrderLine = new CreateOrderLine($repository, $createOrderLineLog);
        $response = $createOrderLine(
            '1',
            'order-uuid',
            'product-uuid',
            'user-uuid',
            2,
            250,
            21,
        );

        $this->assertInstanceOf(CreateOrderLineResponse::class, $response);
        $this->assertSame('1', $response->restaurantId);
        $this->assertSame('order-uuid', $response->orderId);
        $this->assertSame('product-uuid', $response->productId);
        $this->assertSame('user-uuid', $response->userId);
        $this->assertSame(2, $response->quantity);
        $this->assertSame(250, $response->price);
        $this->assertSame(21, $response->taxPercentage);
    }
}
