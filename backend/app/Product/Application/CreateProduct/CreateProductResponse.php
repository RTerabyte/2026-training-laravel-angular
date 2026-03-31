<?php

namespace App\Product\Application\CreateProduct;

use App\Product\Domain\Entity\Product;

final readonly class CreateProductResponse
{
    public function __construct(
        public string $id,
        public string $restaurantId,
        public string $familyId,
        public string $taxId,
        public int $stock,
        public ?string $imageSrc,
        public bool $active,
        public string $name,
        public int $price,
        public string $createdAt,
        public string $updatedAt,
    ) {}

    public static function create(Product $product): self
    {
        return new self(
            id: $product->id()->value(),
            restaurantId: $product->restaurantId()->value(),
            familyId: $product->familyId()->value(),
            taxId: $product->taxId()->value(),
            stock: $product->stock()->value(),
            imageSrc: $product->imageSrc(),
            active: $product->active(),
            name: $product->name()->value(),
            price: $product->price()->value(),
            createdAt: $product->createdAt()->format(\DateTimeInterface::ATOM),
            updatedAt: $product->updatedAt()->format(\DateTimeInterface::ATOM),
        );
    }
}