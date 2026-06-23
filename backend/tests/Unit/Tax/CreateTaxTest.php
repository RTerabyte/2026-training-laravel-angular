<?php

namespace Tests\Unit\Tax;

use App\Tax\Application\CreateTax\CreateTax;
use App\Tax\Application\CreateTax\CreateTaxResponse;
use App\Tax\Domain\Entity\Tax;
use App\Tax\Domain\Interfaces\TaxRepositoryInterface;
use Mockery;
use PHPUnit\Framework\TestCase;

class CreateTaxTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_invoke_creates_tax_saves_via_repository_and_returns_response(): void
    {
        $repository = Mockery::mock(TaxRepositoryInterface::class);

        $repository->shouldReceive('save')
            ->once()
            ->with(Mockery::on(function (Tax $tax) {
                return $tax->restaurantId()->value() === '1'
                    && $tax->name()->value() === 'IVA General'
                    && $tax->percentage()->value() === 21;
            }));

        $createTax = new CreateTax($repository);
        $response = $createTax('1', 'IVA General', 21);

        $this->assertInstanceOf(CreateTaxResponse::class, $response);
        $this->assertSame('1', $response->restaurantId);
        $this->assertSame('IVA General', $response->name);
        $this->assertSame(21, $response->percentage);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $response->id,
        );

        $array = $response->toArray();
        $this->assertArrayHasKey('created_at', $array);
        $this->assertArrayHasKey('updated_at', $array);
    }
}
