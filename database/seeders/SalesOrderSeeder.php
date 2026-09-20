<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Plant;
use App\Models\Quotation;
use App\Models\SalesOrder;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class SalesOrderSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('sales_orders')) {
            $this->command?->warn('Skipping SalesOrderSeeder: table "sales_orders" not found.');

            return;
        }

        $company = Company::where('code', 'FAC001')->first();
        $customer = Customer::where('code', 'CUST001')->first();
        $quotation = Quotation::where('number', 'Q-0001')->first();
        $admin = User::where('email', 'admin@factory.com')->first();
        if (! $company || ! $customer || ! $admin) {
            $this->command?->warn('Skipping SalesOrderSeeder: required records not found.');

            return;
        }

        $plant = Plant::where('code', 'PLT001')->first();
        $warehouse = Warehouse::where('code', 'WH001')->first();

        SalesOrder::firstOrCreate(['number' => 'SO-0001'], [
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'quotation_id' => $quotation?->id,
            'created_by' => $admin->id,
            'plant_id' => $plant?->id,
            'warehouse_id' => $warehouse?->id,
            'order_date' => now()->subDays(6)->toDateString(),
            'requested_delivery_date' => now()->addDays(9)->toDateString(),
            'confirmed_delivery_date' => now()->addDays(9)->toDateString(),
            'po_number' => 'PO-88231',
            'currency' => 'USD',
            'payment_terms' => 'NET30',
            'subtotal' => 4500,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'shipping_cost' => 150,
            'total_amount' => 4650,
            'shipping_address' => '300 Customer Avenue, Cleveland, OH 44101',
            'priority' => 'normal',
            'status' => 'confirmed',
            'approved_by' => $admin->id,
            'approved_at' => now(),
        ]);
    }
}
