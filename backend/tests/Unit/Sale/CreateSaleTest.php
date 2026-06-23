<?php

namespace Tests\Unit\Sale;

use App\Sale\Application\CreateSale\CreateSale;
use App\Sale\Application\CreateSale\CreateSaleResponse;
use App\Sale\Domain\Entity\Sale;
use App\Sale\Domain\Interfaces\SaleRepositoryInterface;
use Mockery;
use PHPUnit\Framework\TestCase;

class CreateSaleTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_invoke_creates_sale_saves_via_repository_and_returns_response(): void
    {
        $repository = Mockery::mock(SaleRepositoryInterface::class);

        $repository->shouldReceive('nextTicketNumberByRestaurant')
            ->once()
            ->with('1')
            ->andReturn(7);

        $repository->shouldReceive('save')
            ->once()
            ->with(Mockery::on(function (Sale $sale) {
                return $sale->restaurantId()->value() === '1'
                    && $sale->orderId()->value() === 'order-uuid'
                    && $sale->userId()->value() === 'user-uuid'
                    && $sale->ticketNumber()?->value() === 7
                    && $sale->total()->value() === 500;
            }));

        $createSale = new CreateSale($repository);
        $response = $createSale('1', 'order-uuid', 'user-uuid', 500);

        $this->assertInstanceOf(CreateSaleResponse::class, $response);
        $this->assertSame('1', $response->restaurantId);
        $this->assertSame('order-uuid', $response->orderId);
        $this->assertSame('user-uuid', $response->userId);
        $this->assertSame(7, $response->ticketNumber);
        $this->assertSame(500, $response->total);
    }
}
