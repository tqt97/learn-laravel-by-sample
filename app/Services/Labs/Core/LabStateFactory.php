<?php

namespace App\Services\Labs\Core;

final class LabStateFactory
{
    public function metrics(
        int $resultCount,
        int $validLimit,
        array $extra = [],
    ): array {
        return [
            'result_count' => $resultCount,
            'valid_limit' => $validLimit,
            ...$extra,
        ];
    }

    public function invariant(
        string $name,
        bool $ok,
        string $okMessage = 'OK',
        string $brokenMessage = 'Broken',
    ): array {
        return [
            'name' => $name,
            'ok' => $ok,
            'message' => $ok ? $okMessage : $brokenMessage,
        ];
    }

    public function countLimitInvariant(
        string $name,
        int $actual,
        int $limit,
        string $unit,
    ): array {
        return $this->invariant(
            name: $name,
            ok: $actual <= $limit,
            okMessage: "OK: {$actual}/{$limit} {$unit}.",
            brokenMessage: "Broken: {$actual} {$unit} created, but limit is {$limit}.",
        );
    }
}
