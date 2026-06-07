<?php

namespace App\Enums\Labs;

enum LabMode: string
{
    case Naive = 'naive';
    case Production = 'production';

    public function label(): string
    {
        return match ($this) {
            self::Naive => 'Naive',
            self::Production => 'Production',
        };
    }
}
