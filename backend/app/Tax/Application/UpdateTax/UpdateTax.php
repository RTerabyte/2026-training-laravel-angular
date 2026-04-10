<?php

namespace App\Tax\Application\UpdateTax;

use App\Tax\Domain\Exception\TaxNotFoundException;
use App\Tax\Domain\Interfaces\TaxRepositoryInterface;
use App\Tax\Domain\ValueObject\TaxName;
use App\Tax\Domain\ValueObject\TaxPercentage;

final class UpdateTax
{
    public function __construct(
        private TaxRepositoryInterface $taxRepository,
    ) {}

    public function __invoke(
        string $id,
        ?string $name = null,
        ?int $percentage = null,
    ): UpdateTaxResponse {
        $tax = $this->taxRepository->findById($id);

        if ($tax === null) {
            throw TaxNotFoundException::withId($id);
        }

        $nameVO = $name !== null
            ? TaxName::create($name)
            : $tax->name();

        $percentageVO = $percentage !== null
            ? TaxPercentage::create($percentage)
            : $tax->percentage();

        $tax = $tax->update(
            $nameVO,
            $percentageVO,
        );

        $this->taxRepository->save($tax);

        return UpdateTaxResponse::create($tax);
    }
}
