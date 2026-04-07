<?php

namespace App\Family\Infrastructure\Entrypoint\Http;

use App\Family\Application\CreateFamily\CreateFamily;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PostController
{
    public function __construct(
        private CreateFamily $createFamily,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'restaurant_id' => ['required', 'integer'],
        ]);

        $response = ($this->createFamily)(
            $validated['name'],
            (string) $validated['restaurant_id'],
        );

        return new JsonResponse($response->toArray(), 201);
    }
}