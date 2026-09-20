<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Item;
use App\Models\StockStatus;
use App\Models\Uom;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class StockMovementSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('stock_movements')) {
            $this->command?->warn('Skipping StockMovementSeeder: table "stock_movements" not found.');

            return;
        }

        $company = Company::where('code', 'FAC001')->first();
        $warehouse = Warehouse::where('code', 'WH001')->first();
        $avail = StockStatus::where('code', 'AVL')->first();
        $kg = Uom::where('code', 'KG')->first();
        $rm001 = Item::where('code', 'RM001')->first();
        $admin = User::where('email', 'admin@factory.com')->first();
        if (! $company || ! $warehouse || ! $avail || ! $kg || ! $rm001 || ! $admin) {
            $this->command?->warn('Skipping StockMovementSeeder: required records not found.');

            return;
        }

        // StockMovement uses HasUuids which writes the uuid into the bigint id
        // column, so insert through the query builder.
        DB::table('stock_movements')->updateOrInsert(
            [
                'document_type' => 'GR',
                'document_number' => 'GR-0001',
                'item_id' => $rm001->id,
            ],
            [
                'uuid' => (string) Str::uuid(),
                'company_id' => $company->id,
                'to_warehouse_id' => $warehouse->id,
                'quantity' => 500,
                'uom_id' => $kg->id,
                'unit_cost' => 2.50,
                'total_cost' => 1250,
                'stock_status_id' => $avail->id,
                'created_by' => $admin->id,
                'notes' => 'Receipt from PO-0001.',
                'updated_at' => now(),
            ]
        );
    }
}
