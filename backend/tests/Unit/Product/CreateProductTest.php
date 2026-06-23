<?php

namespace Tests\Unit\Product;

use App\Product\Application\CreateProduct\CreateProduct;
use App\Product\Application\CreateProduct\CreateProductResponse;
use App\Product\Domain\Entity\Product;
use App\Product\Domain\Interfaces\ProductRepositoryInterface;
use Mockery;
use PHPUnit\Framework\TestCase;

class CreateProductTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_invoke_creates_product_saves_via_repository_and_returns_response(): void
    {
        $repository = Mockery::mock(ProductRepositoryInterface::class);

        $repository->shouldReceive('save')
            ->once()
            ->with(Mockery::on(function (Product $product) {
                return $product->restaurantId()->value() === '1'
                    && $product->familyId()->value() === 'family-uuid'
                    && $product->taxId()->value() === 'tax-uuid'
                    && $product->stock()->value() === 10
                    && $product->imageSrc()->value() === '/images/coke.png'
                    && $product->name()->value() === 'Coca Cola'
                    && $product->price()->value() === 250
                    && $product->active() === true;
            }));

        $createProduct = new CreateProduct($repository);
        $response = $createProduct(
            '1',
            'family-uuid',
            'tax-uuid',
            10,
            '/images/coke.png',
            'Coca Cola',
            250,
        );

        $this->assertInstanceOf(CreateProductResponse::class, $response);
        $this->assertSame('1', $response->restaurantId);
        $this->assertSame('family-uuid', $response->familyId);
        $this->assertSame('tax-uuid', $response->taxId);
        $this->assertSame(10, $response->stock);
        $this->assertSame('/images/coke.png', $response->imageSrc);
        $this->assertTrue($response->active);
        $this->assertSame('Coca Cola', $response->name);
        $this->assertSame(250, $response->price);
    }
}
