<?php

namespace App\Services\Labs\Core;

use App\Models\Labs\Naive\NaiveInventoryOrder;
use App\Models\Labs\Naive\NaiveInventoryProduct;
use App\Models\Labs\Production\ProductionInventoryMovement;
use App\Models\Labs\Production\ProductionInventoryOrder;
use App\Models\Labs\Production\ProductionInventoryProduct;
use Illuminate\Support\Facades\Schema;

final class LabDatabaseResetService
{
    public function resetNaiveInventory(): void
    {
        Schema::disableForeignKeyConstraints();

        NaiveInventoryOrder::truncate();
        NaiveInventoryProduct::truncate();

        Schema::enableForeignKeyConstraints();

        NaiveInventoryProduct::factory()->oneStock()->create();
    }

    public function resetProductionInventory(): void
    {
        Schema::disableForeignKeyConstraints();

        ProductionInventoryMovement::truncate();
        ProductionInventoryOrder::truncate();
        ProductionInventoryProduct::truncate();

        Schema::enableForeignKeyConstraints();

        ProductionInventoryProduct::factory()->oneStock()->create();
    }

    public function resetInventoryOversell(): void
    {
        Schema::disableForeignKeyConstraints();

        NaiveInventoryOrder::truncate();
        NaiveInventoryProduct::truncate();

        ProductionInventoryMovement::truncate();
        ProductionInventoryOrder::truncate();
        ProductionInventoryProduct::truncate();

        Schema::enableForeignKeyConstraints();

        NaiveInventoryProduct::factory()->oneStock()->create();
        ProductionInventoryProduct::factory()->oneStock()->create();
    }
}
