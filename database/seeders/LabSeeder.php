<?php

namespace Database\Seeders;

use App\Services\Labs\Core\LabDatabaseResetService;
use Illuminate\Database\Seeder;

final class LabSeeder extends Seeder
{
    public function run(): void
    {
        app(LabDatabaseResetService::class)->resetInventoryOversell();
    }
}
