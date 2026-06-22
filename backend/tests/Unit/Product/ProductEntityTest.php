<?php

namespace Tests\Unit\Product;

use App\Product\Domain\Entity\Product;
use App\Product\Domain\ValueObject\ProductImageSrc;
use App\Product\Domain\ValueObject\ProductName;
use App\Product\Domain\ValueObject\ProductPrice;
use App\Product\Domain\ValueObject\ProductStock;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\FamilyId;
use App\Shared\Domain\ValueObject\RestaurantId;
use App\Shared\Domain\ValueObject\TaxId;
use PHPUnit\Framework\TestCase;

class ProductEntityTest extends TestCase
{
    public function test_ddd_create_builds_entity_with_attributes_and_vos(): void
    {
        $product = Product::dddCreate(
            RestaurantId::create('1'),
            FamilyId::create('family-uuid'),
            TaxId::create('tax-uuid'),
            ProductStock::create(10),
            ProductImageSrc::create('/images/coke.png'),
            ProductName::create('Coca Cola'),
            ProductPrice::create(250),
        );

        $this->assertInstanceOf(Product::class, $product);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $product->id()->value(),
        );
        $this->assertSame('1', $product->restaurantId()->value());
        $this->assertSame('family-uuid', $product->familyId()->value());
        $this->assertSame('tax-uuid', $product->taxId()->value());
        $this->assertSame(10, $product->stock()->value());
        $this->assertSame('/images/coke.png', $product->imageSrc()->value());
        $this->assertTrue($product->active());
        $this->assertSame('Coca Cola', $product->name()->value());
        $this->assertSame(250, $product->price()->value());
        $this->assertInstanceOf(DomainDateTime::class, $product->createdAt());
        $this->assertInstanceOf(DomainDateTime::class, $product->updatedAt());
    }
}
