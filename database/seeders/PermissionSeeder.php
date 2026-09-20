<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('permissions')) {
            $this->command?->warn('Skipping PermissionSeeder: table "permissions" not found.');

            return;
        }

        $modules = ['companies', 'plants', 'warehouses', 'users', 'items', 'boms', 'routings', 'work_centers',
            'suppliers', 'purchase_orders', 'purchase_requisitions', 'goods_receipts',
            'stock', 'stock_movements', 'stock_counts',
            'production_orders', 'shop_floor',
            'customers', 'quotations', 'sales_orders', 'shipments',
            'quality_plans', 'inspections', 'ncrs', 'capas',
            'assets', 'maintenance_orders', 'downtime',
            'employees', 'attendance', 'shifts',
            'chart_of_accounts', 'journals', 'budgets', 'fixed_assets',
            'reports', 'settings', 'audit_log'];

        foreach ($modules as $module) {
            foreach (['view', 'create', 'edit', 'delete'] as $action) {
                Permission::firstOrCreate(
                    ['name' => "{$module}.{$action}"],
                    ['display_name' => ucfirst(str_replace('_', ' ', $module)).' '.ucfirst($action), 'module' => $module]
                );
            }
        }
    }
}
