<?php

namespace App\Family\Infrastructure\Entrypoint\Http;

use App\Family\Application\DeactivateFamily\DeactivateFamily;
use App\Family\Domain\Exception\FamilyNotFoundException;
use Illuminate\Http\JsonResponse;

final class DeactivateController
{
    public function __construct(
        private DeactivateFamily $deactivateFamily,
    ) {}

    public function __invoke(string $id): JsonResponse
    {
        try {
            $response = ($this->deactivateFamily)($id);

            return new JsonResponse($response->toArray(), 200);
        } catch (FamilyNotFoundException $exception) {
            return new JsonResponse(['message' => $exception->getMessage()], 404);
        }
    }
}
