<?php

namespace App\DTOs\Labs;

final readonly class LabActionResult
{
    public function __construct(
        public bool $success,
        public string $message,
        public array $data = [],
        public int $statusCode = 200,
    ) {}

    public static function success(string $message, array $data = []): self
    {
        return new self(
            success: true,
            message: $message,
            data: $data,
            statusCode: 200,
        );
    }

    public static function failed(
        string $message,
        array $data = [],
        int $statusCode = 409,
    ): self {
        return new self(
            success: false,
            message: $message,
            data: $data,
            statusCode: $statusCode,
        );
    }
}
