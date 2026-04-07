<?php

namespace App\Product\Application\UpdateProduct;

use App\Product\Domain\Exception\ProductNotFoundException;
use App\Product\Domain\Interfaces\ProductRepositoryInterface;
use App\Product\Domain\ValueObject\ProductImageSrc;
use App\Product\Domain\ValueObject\ProductName;
use App\Product\Domain\ValueObject\ProductPrice;
use App\Product\Domain\ValueObject\ProductStock;
use App\Shared\Domain\ValueObject\FamilyId;
use App\Shared\Domain\ValueObject\TaxId;

final class UpdateProduct
{
    public function __construct(
        private ProductRepositoryInterface $productRepository,
    ) {}

    public function __invoke(
        string $id,
        ?string $familyId = null,
        ?string $taxId = null,
        ?int $stock = null,
        ?string $imageSrc = null,
        ?string $name = null,
        ?int $price = null,
        ?bool $active = null,
    ): UpdateProductResponse {
        $product = $this->productRepository->findById($id);

        if ($product === null) {
            throw ProductNotFoundException::withId($id);
        }

        $familyIdVO = $familyId !== null
            ? FamilyId::create($familyId)
            : $product->familyId();

        $taxIdVO = $taxId !== null
            ? TaxId::create($taxId)
            : $product->taxId();

        $stockVO = $stock !== null
            ? ProductStock::create($stock)
            : $product->stock();

        $imageSrcVO = $imageSrc !== null
            ? ProductImageSrc::create($imageSrc)
            : $product->imageSrc();

        $nameVO = $name !== null
            ? ProductName::create($name)
            : $product->name();

        $priceVO = $price !== null
            ? ProductPrice::create($price)
            : $product->price();

        $active = $active ?? $product->active();

        $product = $product->update(
            $familyIdVO,
            $taxIdVO,
            $stockVO,
            $imageSrcVO,
            $nameVO,
            $priceVO,
            $active,
        );

        $this->productRepository->save($product);

        return UpdateProductResponse::create($product);
    }
}