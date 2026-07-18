<?php

declare(strict_types=1);

namespace Chasqui\Canal\Infrastructure\Exceptions;

use Illuminate\Http\JsonResponse;

final class WebhookInvalidoException extends \Exception
{
    public function render(): JsonResponse
    {
        return response()->json(['message' => $this->getMessage()], 403);
    }
}
