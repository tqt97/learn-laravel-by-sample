<?php

namespace App\Services\Labs\Core;

use App\DTOs\Labs\LabActionResult;
use Illuminate\Http\JsonResponse;

final class LabResponseFactory
{
    public function action(LabActionResult $result): JsonResponse
    {
        return response()->json([
            'success' => $result->success,
            'message' => $result->message,
            'data' => $result->data,
        ], $result->statusCode);
    }
}
