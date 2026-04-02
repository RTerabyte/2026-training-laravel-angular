<?php

namespace App\Product\Application\UpdateProduct;

use App\Product\Domain\Exception\ProductNotFoundException;
use App\Product\Domain\Interfaces\ProductRepositoryInterface;

final class UpdateProduct
{
    public function __construct(
        private ProductRepositoryInterface $productRepository,
    ) {}

    public function __invoke(string $id, string $name, string $familyId): UpdateProductResponse
    {
        $product = $this->productRepository->findById($id);

        if ($product === null) {
            throw ProductNotFoundException::withId($id);
        }

        $product = $product->update($name, $familyId, $product->active());
        $this->productRepository->save($product);

        return UpdateProductResponse::create($product);
    }
}