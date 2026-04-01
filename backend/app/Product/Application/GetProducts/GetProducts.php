<?php

namespace App\Product\Application\GetProducts;

use App\Product\Application\GetProducts\GetProductsResponse;
use App\Product\Domain\Interfaces\ProductRepositoryInterface;

final class GetProducts
{
    public function __construct(
        private ProductRepositoryInterface $productRepository,
    ) {}

    public function __invoke(): GetProductsResponse
    {
        $products = $this->productRepository->findAll();

        $productResponses = array_map(
            fn ($product) => GetProductsResponse::create($products),
            $products,
        );

        return GetProductsResponse::create($productResponses);
    }
}