<?php

namespace App\Product\Application\GetProducts;

use App\Product\Application\GetProducts\GetProductResponse;

final readonly class GetProductsResponse
{
    /**
     * @param GetProductsResponse[] $products
     */
    public function __construct(
        public array $products,
    ) {}

    /**
     * @param GetProductsResponse[] $products
     */
    public static function create(array $products): self
    {
        return new self($products);
    }

    /**
     * @return array<string, array<int, array<string, string|bool>>>
     */
    public function toArray(): array
    {
        return [
            'products' => array_map(
                fn (GetProductsResponse $product) => $product->toArray(),
                $this->products,
            ),
        ];
    }
}
