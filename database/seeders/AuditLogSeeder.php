<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Item;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class AuditLogSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('audit_logs')) {
            $this->command?->warn('Skipping AuditLogSeeder: table "audit_logs" not found.');

            return;
        }

        $company = Company::where('code', 'FAC001')->first();
        $fg001 = Item::where('code', 'FG001')->first();
        $admin = User::where('email', 'admin@factory.com')->first();
        if (! $company || ! $fg001 || ! $admin) {
            $this->command?->warn('Skipping AuditLogSeeder: required records not found.');

            return;
        }

        AuditLog::firstOrCreate([
            'event' => 'seeded',
            'auditable_type' => Item::class,
            'auditable_id' => $fg001->id,
        ], [
            'company_id' => $company->id,
            'user_id' => $admin->id,
            'old_values' => null,
            'new_values' => ['name' => $fg001->name],
            'url' => null,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'seeder',
            'tags' => 'demo-data',
        ]);
    }
}
