<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Plant;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class PurchaseOrderSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('purchase_orders')) {
            $this->command?->warn('Skipping PurchaseOrderSeeder: table "purchase_orders" not found.');

            return;
        }

        $company = Company::where('code', 'FAC001')->first();
        $supplier = Supplier::where('code', 'SUP001')->first();
        $admin = User::where('email', 'admin@factory.com')->first();
        if (! $company || ! $supplier || ! $admin) {
            $this->command?->warn('Skipping PurchaseOrderSeeder: company, supplier or admin user not found.');

            return;
        }

        $plant = Plant::where('code', 'PLT001')->first();
        $warehouse = Warehouse::where('code', 'WH001')->first();

        PurchaseOrder::firstOrCreate(['number' => 'PO-0001'], [
            'company_id' => $company->id,
            'type' => 'standard',
            'supplier_id' => $supplier->id,
            'created_by' => $admin->id,
            'plant_id' => $plant?->id,
            'warehouse_id' => $warehouse?->id,
            'order_date' => now()->subDays(5)->toDateString(),
            'expected_delivery_date' => now()->addDays(9)->toDateString(),
            'payment_terms' => 'NET30',
            'currency' => 'USD',
            'subtotal' => 2090,
            'tax_amount' => 0,
            'shipping_cost' => 150,
            'total_amount' => 2240,
            'terms_and_conditions' => 'Standard purchase terms apply.',
            'status' => 'approved',
            'approved_by' => $admin->id,
            'approved_at' => now(),
        ]);
    }
}
