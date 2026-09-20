<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\AccountGroup;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Department;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\Permission;
use App\Models\Plant;
use App\Models\Role;
use App\Models\StockStatus;
use App\Models\Supplier;
use App\Models\Uom;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WorkCenter;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Roles
        $roles = ['super_admin', 'company_admin', 'plant_manager', 'production_planner', 'production_supervisor',
            'shop_floor_operator', 'procurement_manager', 'warehouse_manager', 'quality_manager',
            'maintenance_manager', 'sales_manager', 'finance_manager', 'hr_manager'];
        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role], [
                'display_name' => ucwords(str_replace('_', ' ', $role)),
            ]);
        }

        // Permissions
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

        // Assign all permissions to super_admin
        $superAdmin = Role::where('name', 'super_admin')->first();
        $permIds = Permission::pluck('id')->toArray();
        DB::table('role_has_permissions')->where('role_id', $superAdmin->id)->delete();
        foreach ($permIds as $pid) {
            DB::table('role_has_permissions')->insert(['role_id' => $superAdmin->id, 'permission_id' => $pid]);
        }

        // Company
        $company = Company::firstOrCreate(['code' => 'FAC001'], [
            'name' => 'Advanced Manufacturing Corp.',
            'currency' => 'USD', 'country' => 'US',
        ]);

        // Plant
        $plant = Plant::firstOrCreate(['code' => 'PLT001'], [
            'company_id' => $company->id, 'name' => 'Main Factory', 'timezone' => 'America/New_York',
        ]);

        // Departments
        foreach (['Production', 'Procurement', 'Warehouse', 'Quality', 'Maintenance', 'Sales', 'Finance', 'HR'] as $dept) {
            Department::firstOrCreate(['code' => strtoupper(substr($dept, 0, 3))], [
                'company_id' => $company->id, 'plant_id' => $plant->id, 'name' => $dept,
            ]);
        }

        // Warehouse
        Warehouse::firstOrCreate(['code' => 'WH001'], [
            'company_id' => $company->id, 'plant_id' => $plant->id, 'name' => 'Main Warehouse',
        ]);

        // UOMs
        foreach ([['code' => 'EA', 'name' => 'Each'], ['code' => 'KG', 'name' => 'Kilogram'], ['code' => 'L', 'name' => 'Liter'], ['code' => 'M', 'name' => 'Meter']] as $u) {
            Uom::firstOrCreate(['code' => $u['code']], array_merge($u, ['company_id' => $company->id, 'type' => 'primary']));
        }

        // Categories
        foreach ([['code' => 'RM', 'name' => 'Raw Materials'], ['code' => 'FG', 'name' => 'Finished Goods'], ['code' => 'SF', 'name' => 'Semi-Finished']] as $c) {
            ItemCategory::firstOrCreate(['code' => $c['code']], array_merge($c, ['company_id' => $company->id]));
        }

        // Items
        $ea = Uom::where('code', 'EA')->first();
        $kg = Uom::where('code', 'KG')->first();
        foreach ([
            ['code' => 'RM001', 'name' => 'Steel Sheet 3mm', 'type' => 'raw_material', 'category_id' => 1, 'base_uom_id' => $kg->id, 'standard_cost' => 2.50],
            ['code' => 'RM002', 'name' => 'Aluminum Bar', 'type' => 'raw_material', 'category_id' => 1, 'base_uom_id' => $kg->id, 'standard_cost' => 4.20],
            ['code' => 'FG001', 'name' => 'Steel Bracket Assembly', 'type' => 'finished', 'category_id' => 2, 'base_uom_id' => $ea->id, 'standard_cost' => 25.00, 'selling_price' => 45.00],
            ['code' => 'FG002', 'name' => 'Aluminum Housing', 'type' => 'finished', 'category_id' => 2, 'base_uom_id' => $ea->id, 'standard_cost' => 38.00, 'selling_price' => 62.00],
        ] as $item) {
            Item::firstOrCreate(['code' => $item['code']], array_merge($item, ['company_id' => $company->id, 'is_active' => true]));
        }

        // Work Centers
        foreach ([['code' => 'CNC01', 'name' => 'CNC Machine 1'], ['code' => 'WLD01', 'name' => 'Welding Station']] as $wc) {
            WorkCenter::firstOrCreate(['code' => $wc['code']], array_merge($wc, ['company_id' => $company->id]));
        }

        // Stock Statuses
        foreach ([['code' => 'AVL', 'name' => 'Available'], ['code' => 'QUR', 'name' => 'Quarantine']] as $st) {
            StockStatus::firstOrCreate(['code' => $st['code']], $st);
        }

        // Customers
        foreach ([['code' => 'CUST001', 'name' => 'Industrial Solutions Inc.'], ['code' => 'CUST002', 'name' => 'Global Auto Parts']] as $c) {
            Customer::firstOrCreate(['code' => $c['code']], array_merge($c, ['company_id' => $company->id, 'status' => 'active']));
        }

        // Suppliers
        foreach ([['code' => 'SUP001', 'name' => 'SteelCorp International'], ['code' => 'SUP002', 'name' => 'AluTech Materials']] as $s) {
            Supplier::firstOrCreate(['code' => $s['code']], array_merge($s, ['company_id' => $company->id, 'status' => 'active']));
        }

        // Chart of Accounts
        foreach ([['code' => '1000', 'name' => 'Assets', 'type' => 'asset'], ['code' => '4000', 'name' => 'Revenue', 'type' => 'revenue']] as $ag) {
            AccountGroup::firstOrCreate(['code' => $ag['code']], array_merge($ag, ['company_id' => $company->id]));
        }
        foreach ([['code' => '1010', 'name' => 'Cash', 'type' => 'asset'], ['code' => '1200', 'name' => 'Inventory', 'type' => 'asset'], ['code' => '4010', 'name' => 'Sales Revenue', 'type' => 'revenue']] as $a) {
            Account::firstOrCreate(['code' => $a['code']], array_merge($a, ['company_id' => $company->id, 'is_active' => true]));
        }

        // User Types (required by old schema)
        DB::table('user_types')->insertOrIgnore(['id' => 1, 'name' => 'Admin']);

        // Admin User
        User::firstOrCreate(['email' => 'admin@factory.com'], [
            'name' => 'Administrator', 'password' => Hash::make('password'),
            'company_id' => $company->id, 'plant_id' => $plant->id,
            'email_verified_at' => now(), 'user_type_id' => 1,
        ])->assignRole('super_admin');

        // Demo data for every model. Each seeder is idempotent (firstOrCreate) and
        // skips gracefully when its table does not exist in the current schema.
        $this->call([
            // Master data / organization
            CompanySeeder::class,
            PlantSeeder::class,
            DepartmentSeeder::class,
            WarehouseSeeder::class,
            ZoneSeeder::class,
            UomSeeder::class,
            ItemCategorySeeder::class,
            ItemSeeder::class,
            StockStatusSeeder::class,
            WorkCenterSeeder::class,
            ShiftSeeder::class,
            RoleSeeder::class,
            PermissionSeeder::class,
            // Engineering
            RoutingSeeder::class,
            BomSeeder::class,
            BomLineSeeder::class,
            FormulaSeeder::class,
            FormulaLineSeeder::class,
            EngineeringChangeSeeder::class,
            // Procurement
            SupplierSeeder::class,
            PurchaseRequisitionSeeder::class,
            PurchaseOrderSeeder::class,
            PurchaseOrderLineSeeder::class,
            RfqLineSeeder::class,
            RfqResponseSeeder::class,
            GoodsReceiptSeeder::class,
            GoodsReceiptLineSeeder::class,
            SupplierInvoiceSeeder::class,
            LandedCostSeeder::class,
            ThreeWayMatchSeeder::class,
            SupplierScorecardSeeder::class,
            // Inventory
            StockBalanceSeeder::class,
            StockMovementSeeder::class,
            CycleCountSeeder::class,
            CycleCountLineSeeder::class,
            // Planning
            ForecastSeeder::class,
            PlannedOrderSeeder::class,
            MrpRunSeeder::class,
            CapacityLoadSeeder::class,
            // Production
            ProductionOrderSeeder::class,
            ReworkOrderSeeder::class,
            BatchRecordSeeder::class,
            WipBalanceSeeder::class,
            ScrapRecordSeeder::class,
            // Sales
            CustomerSeeder::class,
            QuotationSeeder::class,
            QuotationLineSeeder::class,
            SalesOrderSeeder::class,
            SalesOrderLineSeeder::class,
            ShipmentSeeder::class,
            ShipmentLineSeeder::class,
            SalesInvoiceSeeder::class,
            // Quality
            QualityPlanSeeder::class,
            InspectionSeeder::class,
            InspectionResultSeeder::class,
            NcrSeeder::class,
            ComplaintSeeder::class,
            RecallSeeder::class,
            QualityGateSeeder::class,
            CertificateOfAnalysisSeeder::class,
            CapaActionSeeder::class,
            CalibrationRecordSeeder::class,
            // Maintenance
            AssetSeeder::class,
            MaintenanceOrderSeeder::class,
            MeterReadingSeeder::class,
            PreventiveMaintenanceScheduleSeeder::class,
            SparePartSeeder::class,
            ToolRegistrationSeeder::class,
            OeeRecordSeeder::class,
            // HR
            EmployeeSeeder::class,
            AttendanceSeeder::class,
            ShiftRosterSeeder::class,
            SkillMatrixSeeder::class,
            TimeBookingSeeder::class,
            PayrollRunSeeder::class,
            PayslipSeeder::class,
            SafetyIncidentSeeder::class,
            PermitToWorkSeeder::class,
            // Finance
            AccountGroupSeeder::class,
            AccountSeeder::class,
            TaxRuleSeeder::class,
            JournalSeeder::class,
            JournalLineSeeder::class,
            FixedAssetSeeder::class,
            CostRollupSeeder::class,
            BudgetLineSeeder::class,
            DepreciationEntrySeeder::class,
            PeriodCloseSeeder::class,
            BankAccountSeeder::class,
            BankReconciliationSeeder::class,
            // Workflow / audit
            CommentSeeder::class,
            AuditLogSeeder::class,
            NotificationTemplateSeeder::class,
            DocumentTemplateSeeder::class,
            // Credit / RMA / genealogy
            CustomerCreditExposureSeeder::class,
            CustomerInvoiceSeeder::class,
            ReturnMerchandiseLineSeeder::class,
            SerialGenealogySeeder::class,
        ]);

        echo "✅ Database seeded successfully!\n";
        echo "Login: admin@factory.com / password\n";
    }
}
