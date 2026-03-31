<?php

namespace App\Product\Application\CreateProduct;

use App\Product\Domain\Entity\Product;
use App\Product\Domain\Interfaces\ProductRepositoryInterface;

class CreateProduct
{
    public function __construct(
        private ProductRepositoryInterface $productRepository,
    ) {}

    public function __invoke(
        string $restaurantId,
        string $familyId,
        string $taxId,
        int $stock,
        string $imageSrc,
        string $name,
        int $price
    ): CreateProductResponse {
        $product = Product::dddCreate(
            $restaurantId,
            $familyId,
            $taxId,
            $stock,
            $imageSrc,
            $name,
            $price
        );
        $this->productRepository->save($product);

        return CreateProductResponse::create($product);
    }
}