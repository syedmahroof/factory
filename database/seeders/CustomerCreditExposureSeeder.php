<?php

namespace Database\Seeders;

use App\Models\CustomerCreditExposure;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class CustomerCreditExposureSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('customer_credit_exposures')) {
            $this->command?->warn('Skipping CustomerCreditExposureSeeder: table "customer_credit_exposures" does not exist in the current schema.');

            return;
        }

        CustomerCreditExposure::create([
            'customer_id' => 1,
            'credit_limit' => 50000,
            'outstanding_balance' => 4650,
            'overdue_balance' => 0,
            'available_credit' => 45350,
            'credit_status' => 'normal',
        ]);
    }
}
