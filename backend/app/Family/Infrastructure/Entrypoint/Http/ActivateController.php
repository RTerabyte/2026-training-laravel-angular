<?php

namespace App\Family\Infrastructure\Entrypoint\Http;

use App\Family\Application\ActivateFamily\ActivateFamily;
use App\Family\Domain\Exception\FamilyNotFoundException;
use Illuminate\Http\JsonResponse;

final class ActivateController
{
    public function __construct(
        private ActivateFamily $activateFamily,
    ) {}

    public function __invoke(string $id): JsonResponse
    {
        try {
            $response = ($this->activateFamily)($id);

            return new JsonResponse($response->toArray(), 200);
        } catch (FamilyNotFoundException $exception) {
            return new JsonResponse(['message' => $exception->getMessage()], 404);
        }
    }
}
