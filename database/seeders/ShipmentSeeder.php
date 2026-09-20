<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\SalesOrder;
use App\Models\Shipment;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class ShipmentSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('shipments')) {
            $this->command?->warn('Skipping ShipmentSeeder: table "shipments" not found.');

            return;
        }

        $company = Company::where('code', 'FAC001')->first();
        $so = SalesOrder::where('number', 'SO-0001')->first();
        $warehouse = Warehouse::where('code', 'WH001')->first();
        $admin = User::where('email', 'admin@factory.com')->first();
        if (! $company || ! $so || ! $warehouse || ! $admin) {
            $this->command?->warn('Skipping ShipmentSeeder: required records not found.');

            return;
        }

        Shipment::firstOrCreate(['number' => 'SH-0001'], [
            'company_id' => $company->id,
            'sales_order_id' => $so->id,
            'warehouse_id' => $warehouse->id,
            'shipped_by' => $admin->id,
            'shipment_date' => now()->addDays(3)->toDateString(),
            'carrier' => 'UPS',
            'tracking_number' => '1Z999AA10123456784',
            'shipping_address' => '300 Customer Avenue, Cleveland, OH 44101',
            'shipping_cost' => 150,
            'status' => 'packed',
        ]);
    }
}
