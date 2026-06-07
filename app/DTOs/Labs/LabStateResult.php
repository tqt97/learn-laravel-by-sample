<?php

namespace App\DTOs\Labs;

final readonly class LabStateResult
{
    public function __construct(
        public string $mode,
        public string $title,
        public array $metrics,
        public array $records = [],
        public array $invariants = [],
    ) {}

    public function toArray(): array
    {
        return [
            'mode' => $this->mode,
            'title' => $this->title,
            'metrics' => $this->metrics,
            'records' => $this->records,
            'invariants' => $this->invariants,
        ];
    }
}
