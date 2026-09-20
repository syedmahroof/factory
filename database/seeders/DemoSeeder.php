<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * A working factory's worth of demo data.
 *
 * Run it with `php artisan db:seed --class=DemoSeeder`.
 *
 * Three things it is careful about:
 *
 * 1. It is safe to re-run. Every row is matched on the natural key a person would
 *    use — an item code, an order number — so a second run updates nothing it did
 *    not create and never duplicates. Records you made by hand are left alone.
 *
 * 2. Transactions are spread across the last nine months rather than stamped with
 *    today. That is what makes the dashboard mean anything: the output chart has a
 *    shape, "last 30 days" differs from "this year", and the prior-period deltas
 *    compare two periods that both have data in them.
 *
 * 3. It does not touch the MCA tables (merchants, investors, lenders, ACH). Those
 *    belong to the half-migrated application described in CLAUDE.md and are not
 *    part of this ERP.
 *
 * The randomness is seeded, so two runs on two machines produce the same factory.
 */
class DemoSeeder extends Seeder
{
    private int $companyId;

    private int $userId;

    private int $plantId;

    /** @var list<int> */
    private array $warehouseIds = [];

    /** @var array<string, int> */
    private array $uom = [];

    private int $stockStatusId;

    /** @var list<array{id: int, code: string, type: string, cost: float, price: float}> */
    private array $items = [];

    /** @var list<int> */
    private array $supplierIds = [];

    /** @var list<int> */
    private array $customerIds = [];

    /** @var list<int> */
    private array $workCenterIds = [];

    /** @var list<int> */
    private array $employeeIds = [];

    /** @var list<int> */
    private array $assetIds = [];

    /** @var array<string, int> */
    private array $accounts = [];

    /** @var array<string, array<string, bool>> table => column => nullable, memoised. */
    private array $schema = [];

    /** How far back the history runs. */
    private const MONTHS = 9;

    public function run(): void
    {
        mt_srand(20260906);

        $this->anchors();
        $this->masterData();
        $this->people();
        $this->finance();
        $this->procurement();
        $this->sales();
        $this->production();
        $this->inventory();
        $this->quality();
        $this->maintenance();
        $this->workforce();

        $this->command?->info('Demo data ready. Sign in and open the dashboard — try "This year".');
    }

    /* ── the fixed points everything else hangs off ───────────────────────── */

    private function anchors(): void
    {
        $company = DB::table('companies')->first();

        if (! $company) {
            $this->command?->error('No company exists — run `php artisan db:seed` first.');

            exit(1);
        }

        $this->companyId = $company->id;
        $this->userId = DB::table('users')->value('id');
        $this->plantId = DB::table('plants')->where('company_id', $this->companyId)->value('id')
            ?? DB::table('plants')->value('id');

        $this->warehouseIds = DB::table('warehouses')->pluck('id')->all();
        $this->stockStatusId = DB::table('stock_statuses')->value('id');
    }

    /* ── master data ──────────────────────────────────────────────────────── */

    private function masterData(): void
    {
        foreach ([
            'PCS' => 'Pieces', 'KG' => 'Kilogram', 'L' => 'Litre',
            'M' => 'Metre', 'BOX' => 'Box', 'HR' => 'Hour', 'SET' => 'Set',
        ] as $code => $name) {
            $this->uom[$code] = $this->upsert('uoms', ['company_id' => $this->companyId, 'code' => $code], [
                'name' => $name,
                'type' => 'count',
                'is_base' => true,
                'is_active' => true,
            ]);
        }

        $categories = [];

        foreach (['Raw Material', 'Machined Parts', 'Assemblies', 'Consumables', 'Packaging'] as $i => $name) {
            $categories[$name] = $this->upsert('item_categories', ['company_id' => $this->companyId, 'name' => $name], [
                'code' => 'CAT'.str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT),
                'is_active' => true,
            ]);
        }

        // A drivetrain plant: bar and castings in, machined housings and shafts in
        // the middle, gearboxes and couplings out.
        $catalogue = [
            ['RM-3301', 'Steel bar, 40mm EN8', 'raw_material', 'KG', 4.20, 0, 'Raw Material', 1200, 4000],
            ['RM-3302', 'Steel bar, 25mm EN24', 'raw_material', 'KG', 5.10, 0, 'Raw Material', 900, 3000],
            ['RM-3310', 'Cast iron housing blank', 'raw_material', 'PCS', 38.00, 0, 'Raw Material', 300, 900],
            ['RM-3318', 'Bearing 6205-2RS', 'raw_material', 'PCS', 3.80, 0, 'Raw Material', 500, 2000],
            ['RM-3319', 'Bearing 6308-2RS', 'raw_material', 'PCS', 7.40, 0, 'Raw Material', 400, 1500],
            ['RM-3325', 'Oil seal 45x62x8', 'raw_material', 'PCS', 1.90, 0, 'Raw Material', 800, 2500],
            ['RM-3330', 'Hardened dowel pin 8mm', 'raw_material', 'PCS', 0.45, 0, 'Raw Material', 2000, 8000],
            ['RM-3340', 'Aluminium plate 10mm', 'raw_material', 'KG', 6.80, 0, 'Raw Material', 400, 1200],
            ['RM-3345', 'Gear blank, 18T', 'raw_material', 'PCS', 12.60, 0, 'Raw Material', 600, 2000],
            ['RM-3350', 'Gear blank, 42T', 'raw_material', 'PCS', 19.40, 0, 'Raw Material', 400, 1200],
            ['CS-4102', 'Cutting fluid, 20L', 'consumable', 'L', 4.10, 0, 'Consumables', 24, 60],
            ['CS-4110', 'Carbide insert CNMG', 'consumable', 'PCS', 6.90, 0, 'Consumables', 200, 800],
            ['CS-4115', 'Grinding wheel 200mm', 'consumable', 'PCS', 22.00, 0, 'Consumables', 30, 100],
            ['CS-4120', 'Degreaser, 5L', 'consumable', 'L', 3.40, 0, 'Consumables', 40, 120],
            ['SP-4501', 'Spindle belt', 'spare', 'PCS', 34.00, 0, 'Consumables', 12, 40],
            ['SP-4508', 'Hydraulic filter', 'spare', 'PCS', 18.50, 0, 'Consumables', 20, 60],
            ['PK-5001', 'Export carton, 600x400', 'packing', 'PCS', 1.15, 0, 'Packaging', 150, 1000],
            ['PK-5006', 'Pallet, heat treated', 'packing', 'PCS', 9.80, 0, 'Packaging', 60, 250],
            ['PK-5010', 'Stretch wrap, 500mm', 'packing', 'PCS', 7.20, 0, 'Packaging', 40, 150],
            ['SF-2210', 'Housing, machined', 'semi_finished', 'PCS', 74.00, 0, 'Machined Parts', 120, 400],
            ['SF-2214', 'Bearing carrier', 'semi_finished', 'PCS', 41.50, 0, 'Machined Parts', 150, 500],
            ['SF-2220', 'Input shaft, hardened', 'semi_finished', 'PCS', 58.00, 0, 'Machined Parts', 140, 450],
            ['SF-2224', 'Output shaft, hardened', 'semi_finished', 'PCS', 63.00, 0, 'Machined Parts', 120, 400],
            ['SF-2230', 'Pinion, 18T ground', 'semi_finished', 'PCS', 46.00, 0, 'Machined Parts', 160, 500],
            ['SF-2234', 'Gear wheel, 42T ground', 'semi_finished', 'PCS', 71.00, 0, 'Machined Parts', 130, 420],
            ['SF-2240', 'End cover, machined', 'semi_finished', 'PCS', 22.00, 0, 'Machined Parts', 200, 700],
            ['FG-1001', 'Gearbox assembly, 40:1', 'finished', 'PCS', 384.00, 640.00, 'Assemblies', 40, 150],
            ['FG-1002', 'Gearbox assembly, 25:1', 'finished', 'PCS', 352.00, 590.00, 'Assemblies', 40, 150],
            ['FG-1004', 'Drive shaft, hardened', 'finished', 'PCS', 118.00, 205.00, 'Assemblies', 60, 220],
            ['FG-1006', 'Right-angle drive unit', 'finished', 'PCS', 468.00, 780.00, 'Assemblies', 25, 90],
            ['FG-1009', 'Coupling, flexible', 'finished', 'PCS', 62.00, 112.00, 'Assemblies', 90, 300],
            ['FG-1012', 'Bearing housing unit', 'finished', 'PCS', 96.00, 168.00, 'Assemblies', 70, 240],
        ];

        foreach ($catalogue as [$code, $name, $type, $uom, $cost, $price, $category, $reorder, $reorderQty]) {
            $id = $this->upsert('items', ['company_id' => $this->companyId, 'code' => $code], [
                'name' => $name,
                'type' => $type,
                'category_id' => $categories[$category],
                'base_uom_id' => $this->uom[$uom],
                'purchase_uom_id' => $this->uom[$uom],
                'sales_uom_id' => $this->uom[$uom],
                'standard_cost' => $cost,
                'purchase_price' => $cost,
                'selling_price' => $price ?: round($cost * 1.7, 2),
                'reorder_level' => $reorder,
                'reorder_quantity' => $reorderQty,
                'minimum_stock' => (int) round($reorder * 0.6),
                'lead_time_days' => $this->pick([7, 10, 14, 21, 28]),
                'valuation_method' => 'standard',
                'is_active' => true,
            ]);

            $this->items[] = ['id' => $id, 'code' => $code, 'type' => $type, 'cost' => $cost, 'price' => $price ?: round($cost * 1.7, 2)];
        }

        $suppliers = [
            ['SUP-1001', 'Northfield Steel & Bar', 'purchasing@northfieldsteel.example', 'Sheffield'],
            ['SUP-1002', 'Castworks Foundry', 'sales@castworks.example', 'Wolverhampton'],
            ['SUP-1003', 'Precision Bearings Co', 'orders@precisionbearings.example', 'Leeds'],
            ['SUP-1004', 'SealTech Components', 'info@sealtech.example', 'Coventry'],
            ['SUP-1005', 'Midland Tooling', 'sales@midlandtooling.example', 'Birmingham'],
            ['SUP-1006', 'Alloy Plate Supplies', 'trade@alloyplate.example', 'Rotherham'],
            ['SUP-1007', 'Gearblank Industries', 'contact@gearblank.example', 'Derby'],
            ['SUP-1008', 'Lubrimax Fluids', 'orders@lubrimax.example', 'Hull'],
            ['SUP-1009', 'Cartonline Packaging', 'sales@cartonline.example', 'Bradford'],
            ['SUP-1010', 'Pallet Direct', 'hello@palletdirect.example', 'Doncaster'],
            ['SUP-1011', 'Hydro Spares Ltd', 'support@hydrospares.example', 'Stoke'],
            ['SUP-1012', 'Abrasive Solutions', 'sales@abrasivesolutions.example', 'Manchester'],
        ];

        foreach ($suppliers as [$code, $name, $email, $city]) {
            $this->supplierIds[] = $this->upsert('suppliers', ['company_id' => $this->companyId, 'code' => $code], [
                'name' => $name,
                'legal_name' => $name.' Limited',
                'email' => $email,
                'phone' => '01'.mt_rand(100, 999).' '.mt_rand(100000, 999999),
                'city' => $city,
                'country' => 'GB',
                'currency' => 'USD',
                'payment_terms' => $this->pick(['NET30', 'NET45', 'NET60']),
                'credit_limit' => $this->pick([50000, 100000, 150000, 250000]),
                'rating' => round(mt_rand(30, 50) / 10, 1),
                'status' => 'active',
            ]);
        }

        $customers = [
            ['CUS-2001', 'Harbour Machinery Group', 'Rotterdam'],
            ['CUS-2002', 'Vulcan Drives AB', 'Gothenburg'],
            ['CUS-2003', 'Anderton Conveyors', 'Manchester'],
            ['CUS-2004', 'Delta Marine Systems', 'Southampton'],
            ['CUS-2005', 'Nordwind Turbines', 'Hamburg'],
            ['CUS-2006', 'Cascade Mining Equipment', 'Perth'],
            ['CUS-2007', 'Ironbridge Handling', 'Telford'],
            ['CUS-2008', 'Meridian Robotics', 'Eindhoven'],
            ['CUS-2009', 'Southgate Packaging Lines', 'Bristol'],
            ['CUS-2010', 'Baltic Agri Machines', 'Riga'],
            ['CUS-2011', 'Cedar Valley Foods', 'Cork'],
            ['CUS-2012', 'Pinnacle Lifts', 'Glasgow'],
            ['CUS-2013', 'Orion Paper Mills', 'Turku'],
            ['CUS-2014', 'Traverse Rail Services', 'Crewe'],
        ];

        foreach ($customers as [$code, $name, $city]) {
            $this->customerIds[] = $this->upsert('customers', ['company_id' => $this->companyId, 'code' => $code], [
                'name' => $name,
                'legal_name' => $name,
                'email' => 'purchasing@'.Str::slug($name).'.example',
                'phone' => '01'.mt_rand(100, 999).' '.mt_rand(100000, 999999),
                'city' => $city,
                'country' => 'GB',
                'currency' => 'USD',
                'payment_terms' => $this->pick(['NET30', 'NET45']),
                'credit_limit' => $this->pick([75000, 120000, 200000, 300000]),
                'status' => 'active',
            ]);
        }

        $workCenters = [
            ['WC-101', 'CNC Turning Cell 1', 'machine', 12, 96, 78.00, true],
            ['WC-102', 'CNC Turning Cell 2', 'machine', 12, 96, 78.00, false],
            ['WC-201', 'CNC Machining Centre', 'machine', 8, 64, 92.00, true],
            ['WC-301', 'Gear Hobbing', 'machine', 6, 48, 105.00, true],
            ['WC-302', 'Gear Grinding', 'machine', 5, 40, 118.00, false],
            ['WC-401', 'Heat Treatment', 'machine', 20, 160, 64.00, false],
            ['WC-501', 'Assembly Line A', 'both', 10, 80, 56.00, false],
            ['WC-601', 'Test & Pack', 'labor', 14, 112, 42.00, false],
        ];

        foreach ($workCenters as [$code, $name, $type, $perHour, $perDay, $rate, $bottleneck]) {
            $this->workCenterIds[] = $this->upsert('work_centers', ['company_id' => $this->companyId, 'code' => $code], [
                'plant_id' => $this->plantId,
                'name' => $name,
                'type' => $type,
                'capacity_per_hour' => $perHour,
                'capacity_per_day' => $perDay,
                'cost_per_hour' => $rate,
                'is_bottleneck' => $bottleneck,
                'is_active' => true,
            ]);
        }

        // Bins, so the warehouse screens have somewhere to put things.
        $zoneIds = DB::table('zones')->pluck('id')->all();

        foreach ($zoneIds as $z => $zoneId) {
            foreach (['A', 'B', 'C', 'D'] as $row) {
                foreach ([1, 2] as $level) {
                    $code = sprintf('Z%d-%s%02d', $z + 1, $row, $level);
                    $this->upsert('bins', ['zone_id' => $zoneId, 'code' => $code], [
                        'name' => 'Zone '.($z + 1)." row {$row} level {$level}",
                        'max_capacity' => 500,
                        'uom_id' => $this->uom['PCS'],
                        'is_active' => true,
                    ]);
                }
            }
        }
    }

    private function people(): void
    {
        $departmentIds = DB::table('departments')->pluck('id')->all();

        $staff = [
            ['Alan', 'Whitcombe', 'Production Manager'], ['Priya', 'Raman', 'Production Planner'],
            ['Tomasz', 'Nowak', 'CNC Setter'], ['Grace', 'Okafor', 'CNC Operator'],
            ['Daniel', 'Fitzgerald', 'CNC Operator'], ['Mei', 'Chen', 'Quality Engineer'],
            ['Robert', 'Ainsley', 'Quality Inspector'], ['Sofia', 'Marchetti', 'Quality Inspector'],
            ['Hassan', 'Karim', 'Maintenance Engineer'], ['Ellie', 'Brookes', 'Maintenance Technician'],
            ['Victor', 'Duarte', 'Warehouse Supervisor'], ['Nadia', 'Petrova', 'Storesperson'],
            ['Callum', 'Reid', 'Storesperson'], ['Ayesha', 'Iqbal', 'Buyer'],
            ['Martin', 'Ledger', 'Senior Buyer'], ['Freya', 'Lindqvist', 'Sales Engineer'],
            ['Oliver', 'Nkemelu', 'Sales Engineer'], ['Hannah', 'Boyle', 'Customer Service'],
            ['Peter', 'Vasquez', 'Assembly Technician'], ['Lucia', 'Romano', 'Assembly Technician'],
            ['Ibrahim', 'Sow', 'Heat Treatment Operator'], ['Katie', 'Sullivan', 'Finance Analyst'],
            ['Marcus', 'Bell', 'Cost Accountant'], ['Yuki', 'Tanaka', 'HR Officer'],
        ];

        foreach ($staff as $i => [$first, $last, $title]) {
            $code = 'EMP'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT);

            $this->employeeIds[] = $this->upsert('employees', ['company_id' => $this->companyId, 'employee_code' => $code], [
                'plant_id' => $this->plantId,
                'department_id' => $departmentIds ? $departmentIds[$i % count($departmentIds)] : null,
                'first_name' => $first,
                'last_name' => $last,
                'designation' => $title,
                'email' => strtolower($first.'.'.$last).'@factory.example',
                'phone' => '07'.mt_rand(100000000, 999999999),
                'hire_date' => Carbon::today()->subDays(mt_rand(120, 2600))->toDateString(),
                'employment_type' => $this->pick(['full_time', 'full_time', 'full_time', 'contract']),
                'status' => 'active',
            ]);
        }
    }

    /* ── finance ──────────────────────────────────────────────────────────── */

    private function finance(): void
    {
        $groups = [
            ['1000', 'Current Assets', 'asset'],
            ['2000', 'Current Liabilities', 'liability'],
            ['3000', 'Equity', 'equity'],
            ['4000', 'Revenue', 'revenue'],
            ['5000', 'Cost of Goods Sold', 'expense'],
            ['6000', 'Operating Expenses', 'expense'],
        ];

        $groupIds = [];

        foreach ($groups as [$code, $name, $type]) {
            $groupIds[$code] = $this->upsert('account_groups', ['company_id' => $this->companyId, 'code' => $code], [
                'name' => $name,
                'type' => $type,
                'level' => 1,
            ]);
        }

        $chart = [
            ['1010', 'Cash at Bank', 'asset', '1000'], ['1020', 'Petty Cash', 'asset', '1000'],
            ['1100', 'Accounts Receivable', 'asset', '1000'], ['1200', 'Raw Material Inventory', 'asset', '1000'],
            ['1210', 'WIP Inventory', 'asset', '1000'], ['1220', 'Finished Goods Inventory', 'asset', '1000'],
            ['1500', 'Plant & Machinery', 'asset', '1000'], ['1510', 'Accumulated Depreciation', 'asset', '1000'],
            ['2010', 'Accounts Payable', 'liability', '2000'], ['2020', 'Accrued Expenses', 'liability', '2000'],
            ['2100', 'VAT Payable', 'liability', '2000'],
            ['3010', 'Share Capital', 'equity', '3000'], ['3020', 'Retained Earnings', 'equity', '3000'],
            ['4010', 'Product Sales', 'revenue', '4000'], ['4020', 'Service Revenue', 'revenue', '4000'],
            ['5010', 'Material Cost', 'expense', '5000'], ['5020', 'Direct Labour', 'expense', '5000'],
            ['5030', 'Manufacturing Overhead', 'expense', '5000'],
            ['6010', 'Salaries & Wages', 'expense', '6000'], ['6020', 'Rent & Rates', 'expense', '6000'],
            ['6030', 'Utilities', 'expense', '6000'], ['6040', 'Repairs & Maintenance', 'expense', '6000'],
            ['6050', 'Depreciation', 'expense', '6000'],
        ];

        foreach ($chart as [$code, $name, $type, $group]) {
            $this->accounts[$code] = $this->upsert('accounts', ['company_id' => $this->companyId, 'code' => $code], [
                'name' => $name,
                'type' => $type,
                'account_group_id' => $groupIds[$group],
                'currency' => 'USD',
                'opening_balance' => 0,
                'is_bank_account' => $code === '1010',
                'is_active' => true,
            ]);
        }

        foreach ([
            ['Northbank Commercial', '40218866', $this->accounts['1010']],
            ['Northbank Deposit', '40218874', $this->accounts['1010']],
            ['Continental FX (EUR)', 'DE8937040044', $this->accounts['1010']],
            ['Payroll Account', '40218882', $this->accounts['1010']],
        ] as $i => [$bank, $number, $accountId]) {
            $this->upsert('bank_accounts', ['account_number' => $number], [
                'account_id' => $accountId,
                'bank_name' => $bank,
                'routing_number' => '20'.mt_rand(1000, 9999),
                'currency' => $i === 2 ? 'EUR' : 'USD',
                'balance' => mt_rand(40000, 480000),
                'is_active' => true,
            ]);
        }

        foreach ([
            ['FA-0001', 'CNC Lathe — Okuma LB3000', 420000, 120],
            ['FA-0002', 'CNC Lathe — Okuma LB2000', 360000, 120],
            ['FA-0003', 'Machining Centre — DMG DMU50', 510000, 144],
            ['FA-0004', 'Gear Hobber — Liebherr LC180', 620000, 180],
            ['FA-0005', 'Gear Grinder — Reishauer RZ60', 780000, 180],
            ['FA-0006', 'Heat Treatment Furnace', 240000, 180],
            ['FA-0007', 'CMM — Zeiss Contura', 185000, 120],
            ['FA-0008', 'Overhead Crane 10t', 96000, 240],
            ['FA-0009', 'Forklift — Linde H30', 42000, 84],
            ['FA-0010', 'Compressor Plant', 68000, 120],
        ] as $i => [$number, $name, $cost, $life]) {
            $this->upsert('fixed_assets', ['company_id' => $this->companyId, 'number' => $number], [
                'name' => $name,
                'account_id' => $this->accounts['1500'],
                'depreciation_account_id' => $this->accounts['6050'],
                'acquisition_date' => Carbon::today()->subMonths(mt_rand(8, 90))->toDateString(),
                'acquisition_cost' => $cost,
                'useful_life_months' => $life,
                'depreciation_method' => 'straight_line',
                'salvage_value' => round($cost * 0.05, 2),
                'status' => 'active',
            ]);
        }

        $this->journals();
    }

    private function journals(): void
    {
        $periodId = DB::table('fiscal_periods')->value('id');

        if (! $periodId) {
            return;
        }

        $pairs = [
            ['sales', 'Sales invoicing', '1100', '4010'],
            ['purchase', 'Material purchases', '5010', '2010'],
            ['payment', 'Supplier payment', '2010', '1010'],
            ['receipt', 'Customer receipt', '1010', '1100'],
            ['general', 'Payroll accrual', '6010', '2020'],
            ['general', 'Utilities', '6030', '2010'],
            ['adjustment', 'Depreciation charge', '6050', '1510'],
        ];

        for ($i = 1; $i <= 64; $i++) {
            [$type, $description, $debit, $credit] = $pairs[($i - 1) % count($pairs)];
            $date = $this->dateInHistory();
            $amount = round(mt_rand(90000, 4800000) / 100, 2);
            $number = 'JV-'.str_pad((string) $i, 5, '0', STR_PAD_LEFT);

            $journalId = $this->upsert('journals', ['company_id' => $this->companyId, 'number' => $number], [
                'fiscal_period_id' => $periodId,
                'date' => $date->toDateString(),
                'type' => $type,
                'description' => $description,
                'total_debit' => $amount,
                'total_credit' => $amount,
                'status' => $i % 9 === 0 ? 'draft' : 'posted',
                'created_by' => $this->userId,
                'posted_by' => $i % 9 === 0 ? null : $this->userId,
                'posted_at' => $i % 9 === 0 ? null : $date,
            ], $date);

            if (DB::table('journal_lines')->where('journal_id', $journalId)->exists()) {
                continue;
            }

            DB::table('journal_lines')->insert(array_map(fn ($r) => $this->onlyRealColumns('journal_lines', $r), [
                [
                    'journal_id' => $journalId, 'line_number' => 1, 'account_id' => $this->accounts[$debit],
                    'debit' => $amount, 'credit' => 0, 'currency' => 'USD', 'exchange_rate' => 1,
                    'debit_local' => $amount, 'credit_local' => 0, 'description' => $description,
                    'created_at' => $date, 'updated_at' => $date,
                ],
                [
                    'journal_id' => $journalId, 'line_number' => 2, 'account_id' => $this->accounts[$credit],
                    'debit' => 0, 'credit' => $amount, 'currency' => 'USD', 'exchange_rate' => 1,
                    'debit_local' => 0, 'credit_local' => $amount, 'description' => $description,
                    'created_at' => $date, 'updated_at' => $date,
                ],
            ]));
        }
    }

    /* ── procurement ──────────────────────────────────────────────────────── */

    private function procurement(): void
    {
        $buyables = array_values(array_filter(
            $this->items,
            fn ($i) => in_array($i['type'], ['raw_material', 'consumable', 'spare', 'packing'], true),
        ));

        $statuses = ['received', 'received', 'received', 'partially_received', 'approved', 'approved', 'submitted', 'draft', 'closed'];

        for ($i = 1; $i <= 96; $i++) {
            $date = $this->dateInHistory();
            $number = 'PO-'.$date->format('y').str_pad((string) $i, 4, '0', STR_PAD_LEFT);
            $status = $statuses[$i % count($statuses)];
            $supplierId = $this->pick($this->supplierIds);

            $lines = [];
            $subtotal = 0.0;

            foreach ($this->sample($buyables, mt_rand(1, 4)) as $n => $item) {
                $qty = mt_rand(20, 600);
                $price = round($item['cost'] * (mt_rand(94, 108) / 100), 4);
                $total = round($qty * $price, 2);
                $subtotal += $total;

                $received = match ($status) {
                    'received', 'closed' => $qty,
                    'partially_received' => (int) round($qty * 0.6),
                    default => 0,
                };

                $lines[] = [
                    'line_number' => $n + 1,
                    'item_id' => $item['id'],
                    'quantity' => $qty,
                    'received_quantity' => $received,
                    'uom_id' => $this->uom['PCS'],
                    'unit_price' => $price,
                    'tax_rate' => 20,
                    'line_total' => $total,
                    'required_date' => $date->copy()->addDays(mt_rand(7, 30))->toDateString(),
                    'status' => $received >= $qty ? 'received' : ($received > 0 ? 'partially_received' : 'pending'),
                ];
            }

            $tax = round($subtotal * 0.2, 2);

            $poId = $this->upsert('purchase_orders', ['company_id' => $this->companyId, 'number' => $number], [
                'type' => 'standard',
                'supplier_id' => $supplierId,
                'created_by' => $this->userId,
                'plant_id' => $this->plantId,
                'warehouse_id' => $this->pick($this->warehouseIds),
                'order_date' => $date->toDateString(),
                'expected_delivery_date' => $date->copy()->addDays(mt_rand(7, 35))->toDateString(),
                'payment_terms' => 'NET30',
                'currency' => 'USD',
                'subtotal' => $subtotal,
                'tax_amount' => $tax,
                'total_amount' => round($subtotal + $tax, 2),
                'status' => $status,
                'approved_by' => in_array($status, ['draft', 'submitted'], true) ? null : $this->userId,
                'approved_at' => in_array($status, ['draft', 'submitted'], true) ? null : $date,
            ], $date);

            $this->lines('purchase_order_lines', 'purchase_order_id', $poId, $lines, $date);

            // Anything received gets a goods receipt against it.
            if (in_array($status, ['received', 'partially_received', 'closed'], true)) {
                $grnDate = $date->copy()->addDays(mt_rand(5, 25));

                if ($grnDate->isFuture()) {
                    $grnDate = Carbon::today();
                }

                $grnNumber = 'GRN-'.$date->format('y').str_pad((string) $i, 4, '0', STR_PAD_LEFT);

                $grnId = $this->upsert('goods_receipts', ['company_id' => $this->companyId, 'number' => $grnNumber], [
                    'purchase_order_id' => $poId,
                    'supplier_id' => $supplierId,
                    'warehouse_id' => $this->pick($this->warehouseIds),
                    'receipt_date' => $grnDate->toDateString(),
                    'received_by' => $this->userId,
                    'status' => $this->pick(['received', 'inspected', 'put_away']),
                ], $grnDate);

                $grnLines = [];

                foreach ($lines as $line) {
                    if ($line['received_quantity'] <= 0) {
                        continue;
                    }

                    $grnLines[] = [
                        'item_id' => $line['item_id'],
                        'quantity' => $line['received_quantity'],
                        'accepted_quantity' => $line['received_quantity'],
                        'rejected_quantity' => 0,
                        'uom_id' => $line['uom_id'],
                        'unit_cost' => $line['unit_price'],
                    ];
                }

                $this->lines('goods_receipt_lines', 'goods_receipt_id', $grnId, $grnLines, $grnDate);
            }
        }

        $this->supplierInvoices();
    }

    private function supplierInvoices(): void
    {
        $orders = DB::table('purchase_orders')
            ->whereIn('status', ['received', 'closed', 'partially_received'])
            ->inRandomOrder()
            ->limit(56)
            ->get();

        foreach ($orders as $n => $po) {
            $date = Carbon::parse($po->order_date)->addDays(mt_rand(10, 40));

            if ($date->isFuture()) {
                $date = Carbon::today();
            }

            $number = 'SI-'.str_pad((string) ($n + 1), 5, '0', STR_PAD_LEFT);

            $this->upsert('supplier_invoices', ['company_id' => $this->companyId, 'number' => $number], [
                'supplier_id' => $po->supplier_id,
                'purchase_order_id' => $po->id,
                'supplier_invoice_number' => 'INV-'.mt_rand(10000, 99999),
                'invoice_date' => $date->toDateString(),
                'due_date' => $date->copy()->addDays(30)->toDateString(),
                'currency' => 'USD',
                'subtotal' => $po->subtotal,
                'tax_amount' => $po->tax_amount,
                'total_amount' => $po->total_amount,
                'status' => $this->pick(['paid', 'paid', 'approved', 'matched', 'partially_paid', 'overdue']),
            ], $date);
        }
    }

    /* ── sales ────────────────────────────────────────────────────────────── */

    private function sales(): void
    {
        $sellable = array_values(array_filter($this->items, fn ($i) => $i['type'] === 'finished'));
        $statuses = ['invoiced', 'delivered', 'shipped', 'shipped', 'packed', 'picked', 'allocated', 'confirmed', 'confirmed', 'draft', 'closed'];

        for ($i = 1; $i <= 128; $i++) {
            $date = $this->dateInHistory();
            $number = 'SO-'.$date->format('y').str_pad((string) $i, 4, '0', STR_PAD_LEFT);
            $status = $statuses[$i % count($statuses)];
            $customerId = $this->pick($this->customerIds);

            $lines = [];
            $subtotal = 0.0;

            foreach ($this->sample($sellable, mt_rand(1, 3)) as $n => $item) {
                $qty = mt_rand(4, 90);
                $price = round($item['price'] * (mt_rand(92, 106) / 100), 4);
                $total = round($qty * $price, 2);
                $subtotal += $total;

                $shipped = in_array($status, ['shipped', 'delivered', 'invoiced', 'closed'], true) ? $qty : 0;

                $lines[] = [
                    'line_number' => $n + 1,
                    'item_id' => $item['id'],
                    'quantity' => $qty,
                    'picked_quantity' => $shipped,
                    'shipped_quantity' => $shipped,
                    'uom_id' => $this->uom['PCS'],
                    'unit_price' => $price,
                    'tax_rate' => 20,
                    'line_total' => $total,
                    'requested_date' => $date->copy()->addDays(mt_rand(10, 45))->toDateString(),
                    'status' => $shipped > 0 ? 'shipped' : 'pending',
                ];
            }

            $tax = round($subtotal * 0.2, 2);

            $soId = $this->upsert('sales_orders', ['company_id' => $this->companyId, 'number' => $number], [
                'customer_id' => $customerId,
                'created_by' => $this->userId,
                'plant_id' => $this->plantId,
                'warehouse_id' => $this->pick($this->warehouseIds),
                'order_date' => $date->toDateString(),
                'requested_delivery_date' => $date->copy()->addDays(mt_rand(14, 60))->toDateString(),
                'po_number' => 'CPO-'.mt_rand(10000, 99999),
                'currency' => 'USD',
                'payment_terms' => 'NET30',
                'subtotal' => $subtotal,
                'tax_amount' => $tax,
                'total_amount' => round($subtotal + $tax, 2),
                'priority' => $this->pick(['normal', 'normal', 'normal', 'high', 'low', 'urgent']),
                'status' => $status,
                'approved_by' => $status === 'draft' ? null : $this->userId,
                'approved_at' => $status === 'draft' ? null : $date,
            ], $date);

            $lineIds = $this->lines('sales_order_lines', 'sales_order_id', $soId, $lines, $date);

            if (in_array($status, ['shipped', 'delivered', 'invoiced', 'closed'], true)) {
                $shipDate = $date->copy()->addDays(mt_rand(7, 40));

                if ($shipDate->isFuture()) {
                    $shipDate = Carbon::today();
                }

                $shipNumber = 'SHP-'.$date->format('y').str_pad((string) $i, 4, '0', STR_PAD_LEFT);

                $shipmentId = $this->upsert('shipments', ['company_id' => $this->companyId, 'number' => $shipNumber], [
                    'sales_order_id' => $soId,
                    'warehouse_id' => $this->pick($this->warehouseIds),
                    'shipment_date' => $shipDate->toDateString(),
                    'shipping_address' => "Goods Inwards\nUnit ".mt_rand(1, 40).', Industrial Estate',
                    'carrier' => $this->pick(['DHL Freight', 'Kuehne+Nagel', 'DB Schenker', 'Own Fleet']),
                    'tracking_number' => strtoupper(Str::random(3)).mt_rand(1000000, 9999999),
                    'status' => $status === 'shipped' ? 'shipped' : 'delivered',
                ], $shipDate);

                $shipLines = [];

                foreach ($lines as $n => $line) {
                    if (($line['shipped_quantity'] ?? 0) <= 0 || ! isset($lineIds[$n])) {
                        continue;
                    }

                    $shipLines[] = [
                        'sales_order_line_id' => $lineIds[$n],
                        'item_id' => $line['item_id'],
                        'quantity' => $line['shipped_quantity'],
                    ];
                }

                $this->lines('shipment_lines', 'shipment_id', $shipmentId, $shipLines, $shipDate);
            }
        }

        $this->customerInvoices();
        $this->returns();
    }

    private function customerInvoices(): void
    {
        $orders = DB::table('sales_orders')
            ->whereIn('status', ['invoiced', 'delivered', 'closed', 'shipped'])
            ->inRandomOrder()
            ->limit(72)
            ->get();

        foreach ($orders as $n => $so) {
            $date = Carbon::parse($so->order_date)->addDays(mt_rand(12, 45));

            if ($date->isFuture()) {
                $date = Carbon::today();
            }

            $number = 'CI-'.str_pad((string) ($n + 1), 5, '0', STR_PAD_LEFT);

            $this->upsert('customer_invoices', ['company_id' => $this->companyId, 'number' => $number], [
                'customer_id' => $so->customer_id,
                'sales_order_id' => $so->id,
                'invoice_date' => $date->toDateString(),
                'due_date' => $date->copy()->addDays(30)->toDateString(),
                'currency' => 'USD',
                'subtotal' => $so->subtotal,
                'tax_amount' => $so->tax_amount,
                'total_amount' => $so->total_amount,
                'status' => $this->pick(['paid', 'paid', 'paid', 'sent', 'partially_paid', 'overdue']),
            ], $date);
        }
    }

    private function returns(): void
    {
        $orders = DB::table('sales_orders')
            ->whereIn('status', ['delivered', 'invoiced', 'closed'])
            ->inRandomOrder()
            ->limit(18)
            ->get();

        $reasons = [
            'Bearing noise reported on commissioning',
            'Wrong ratio supplied against customer order',
            'Transit damage to housing',
            'Oil weep from output seal',
            'Surplus to requirement — customer cancelled line',
            'Vibration outside specification at full load',
        ];

        foreach ($orders as $n => $so) {
            $date = Carbon::parse($so->order_date)->addDays(mt_rand(20, 70));

            if ($date->isFuture()) {
                $date = Carbon::today();
            }

            $number = 'RMA-'.str_pad((string) ($n + 1), 4, '0', STR_PAD_LEFT);

            $this->upsert('customer_returns', ['company_id' => $this->companyId, 'number' => $number], [
                'customer_id' => $so->customer_id,
                'sales_order_id' => $so->id,
                'created_by' => $this->userId,
                'return_date' => $date->toDateString(),
                'reason' => $reasons[$n % count($reasons)],
                'status' => $this->pick(['received', 'inspected', 'dispositioned', 'closed', 'draft']),
            ], $date);
        }
    }

    /* ── production ───────────────────────────────────────────────────────── */

    /**
     * The output history the dashboard chart draws.
     *
     * Most orders are finished, with `actual_end_date` scattered across the nine
     * months and a quantity that varies week to week — a flat line is not a
     * factory. The rest are open, so the status mix and the "on the floor" figures
     * are not all zero.
     */
    private function production(): void
    {
        $makeable = array_values(array_filter($this->items, fn ($i) => in_array($i['type'], ['finished', 'semi_finished'], true)));
        $statuses = ['closed', 'closed', 'closed', 'closed', 'closed', 'costed', 'technically_complete', 'in_progress', 'released', 'approved', 'planned', 'cancelled'];

        for ($i = 1; $i <= 168; $i++) {
            $start = $this->dateInHistory();
            $status = $statuses[$i % count($statuses)];
            $item = $this->pick($makeable);
            $number = 'PRO-'.$start->format('y').str_pad((string) $i, 4, '0', STR_PAD_LEFT);

            $planned = mt_rand(20, 400);
            $finished = in_array($status, ['closed', 'costed', 'technically_complete'], true);

            // A little scrap on most runs, none on a good one.
            $scrap = $finished ? (int) round($planned * (mt_rand(0, 45) / 1000)) : 0;
            $actual = $finished ? max(0, $planned - $scrap + mt_rand(-6, 10)) : 0;

            $end = $start->copy()->addDays(mt_rand(3, 26));

            if ($end->isFuture()) {
                $end = Carbon::today();
            }

            $this->upsert('production_orders', ['company_id' => $this->companyId, 'number' => $number], [
                'item_id' => $item['id'],
                'plant_id' => $this->plantId,
                'warehouse_id' => $this->pick($this->warehouseIds),
                'type' => $this->pick(['make_to_stock', 'make_to_stock', 'make_to_order']),
                'planned_quantity' => $planned,
                'actual_quantity' => $actual,
                'scrap_quantity' => $scrap,
                'uom_id' => $this->uom['PCS'],
                'priority' => $this->pick(['low', 'normal', 'normal', 'high']),
                'planned_start_date' => $start->toDateString(),
                'planned_end_date' => $start->copy()->addDays(mt_rand(5, 30))->toDateString(),
                'actual_start_date' => $status === 'planned' ? null : $start->toDateString(),
                'actual_end_date' => $finished ? $end->toDateString() : null,
                'status' => $status,
                'estimated_cost' => round($planned * $item['cost'], 2),
                'actual_cost' => $finished ? round($actual * $item['cost'] * (mt_rand(96, 110) / 100), 2) : 0,
                'created_by' => $this->userId,
                'approved_by' => $status === 'planned' ? null : $this->userId,
                'approved_at' => $status === 'planned' ? null : $start,
            ], $start);
        }
    }

    /* ── inventory ────────────────────────────────────────────────────────── */

    private function inventory(): void
    {
        foreach ($this->items as $item) {
            foreach ($this->sample($this->warehouseIds, min(2, count($this->warehouseIds))) as $warehouseId) {
                $exists = DB::table('stock_balances')
                    ->where(['company_id' => $this->companyId, 'warehouse_id' => $warehouseId, 'item_id' => $item['id']])
                    ->exists();

                if ($exists) {
                    continue;
                }

                // A handful of lines sit under their reorder level on purpose, so
                // the dashboard's shortfall panel has something to say.
                $qty = mt_rand(1, 10) === 1 ? mt_rand(0, 40) : mt_rand(80, 2400);
                $reserved = (int) round($qty * (mt_rand(0, 25) / 100));

                DB::table('stock_balances')->insert($this->onlyRealColumns('stock_balances', [
                    'company_id' => $this->companyId,
                    'plant_id' => $this->plantId,
                    'warehouse_id' => $warehouseId,
                    'item_id' => $item['id'],
                    'stock_status_id' => $this->stockStatusId,
                    'quantity' => $qty,
                    'reserved_quantity' => $reserved,
                    'available_quantity' => $qty - $reserved,
                    'unit_cost' => $item['cost'],
                    'total_value' => round($qty * $item['cost'], 2),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }

        if (DB::table('stock_movements')->count() > 50) {
            return;
        }

        $types = ['goods_receipt', 'production_issue', 'production_receipt', 'transfer', 'adjustment', 'shipment'];
        $rows = [];

        for ($i = 1; $i <= 520; $i++) {
            $date = $this->dateInHistory();
            $item = $this->pick($this->items);
            $type = $this->pick($types);
            $qty = mt_rand(5, 400);

            $rows[] = [
                'company_id' => $this->companyId,
                'document_type' => $type,
                'document_number' => strtoupper(substr($type, 0, 3)).'-'.str_pad((string) $i, 5, '0', STR_PAD_LEFT),
                'item_id' => $item['id'],
                'from_warehouse_id' => in_array($type, ['production_issue', 'shipment', 'transfer'], true) ? $this->pick($this->warehouseIds) : null,
                'to_warehouse_id' => in_array($type, ['goods_receipt', 'production_receipt', 'transfer'], true) ? $this->pick($this->warehouseIds) : null,
                'quantity' => $qty,
                'uom_id' => $this->uom['PCS'],
                'unit_cost' => $item['cost'],
                'total_cost' => round($qty * $item['cost'], 2),
                'stock_status_id' => $this->stockStatusId,
                'created_by' => $this->userId,
                'created_at' => $date,
                'updated_at' => $date,
            ];
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('stock_movements')->insert(array_map(fn ($r) => $this->onlyRealColumns('stock_movements', $r), $chunk));
        }
    }

    /* ── quality ──────────────────────────────────────────────────────────── */

    private function quality(): void
    {
        $planIds = [];

        foreach ([
            ['QP-001', 'Incoming bar stock', 'incoming'],
            ['QP-002', 'Incoming castings', 'incoming'],
            ['QP-003', 'Incoming bearings', 'incoming'],
            ['QP-004', 'First-off turning', 'in_process'],
            ['QP-005', 'In-process gear grinding', 'in_process'],
            ['QP-006', 'Heat treatment verification', 'in_process'],
            ['QP-007', 'Final assembly test', 'final'],
            ['QP-008', 'Annual gauge review', 'periodic'],
        ] as [$code, $name, $type]) {
            $planIds[] = $this->upsert('quality_plans', ['company_id' => $this->companyId, 'code' => $code], [
                'name' => $name,
                'type' => $type,
                'sampling_method' => $this->pick(['100%', 'AQL 1.0', 'AQL 2.5', 'First-off + last-off']),
                'is_active' => true,
            ]);
        }

        for ($i = 1; $i <= 76; $i++) {
            $date = $this->dateInHistory();
            $inspected = mt_rand(5, 200);
            $rejected = mt_rand(1, 12) === 1 ? mt_rand(1, (int) max(1, $inspected * 0.08)) : 0;
            $number = 'QI-'.str_pad((string) $i, 5, '0', STR_PAD_LEFT);

            $this->upsert('inspections', ['company_id' => $this->companyId, 'number' => $number], [
                'quality_plan_id' => $this->pick($planIds),
                'source_type' => $this->pick(['goods_receipt', 'production_order', 'shipment']),
                'source_id' => mt_rand(1, 50),
                'item_id' => $this->pick($this->items)['id'],
                'quantity_inspected' => $inspected,
                'quantity_accepted' => $inspected - $rejected,
                'quantity_rejected' => $rejected,
                'quantity_on_hold' => 0,
                'inspector_id' => $this->userId,
                'inspection_date' => $date->toDateString(),
                'result' => $rejected > 0 ? $this->pick(['rejected', 'conditional']) : 'accepted',
                'status' => 'completed',
            ], $date);
        }

        $defects = [
            'Bore diameter above upper limit on 3 of 12 sampled',
            'Surface finish outside Ra specification after grinding',
            'Case depth short of drawing requirement',
            'Runout exceeds 0.03mm on output shaft',
            'Incorrect heat number on supplier certificate',
            'Thread damage on 2 housings from transit',
            'Oil seal seat scored during assembly',
            'Gear tooth profile outside DIN 7 tolerance',
        ];

        for ($i = 1; $i <= 32; $i++) {
            $date = $this->dateInHistory();
            $number = 'NCR-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT);
            $closed = mt_rand(1, 10) > 3;

            $this->upsert('ncrs', ['company_id' => $this->companyId, 'number' => $number], [
                'item_id' => $this->pick($this->items)['id'],
                'severity' => $this->pick(['minor', 'minor', 'major', 'critical', 'cosmetic']),
                'source' => $this->pick(['incoming', 'in_process', 'final', 'customer_complaint']),
                'defect_description' => $defects[$i % count($defects)],
                'affected_quantity' => mt_rand(1, 60),
                'reported_by' => $this->userId,
                'reported_date' => $date->toDateString(),
                'disposition' => $this->pick(['rework', 'scrap', 'use_as_is', 'return_to_supplier', 'pending']),
                'status' => $closed ? 'closed' : $this->pick(['open', 'investigating', 'action_in_progress', 'contained']),
                'assigned_to' => $this->userId,
                'target_close_date' => $date->copy()->addDays(30)->toDateString(),
                'actual_close_date' => $closed ? $date->copy()->addDays(mt_rand(5, 40))->toDateString() : null,
            ], $date);
        }

        for ($i = 1; $i <= 14; $i++) {
            $date = $this->dateInHistory();
            $number = 'CAPA-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT);

            $this->upsert('capas', ['company_id' => $this->companyId, 'number' => $number], [
                'type' => $this->pick(['corrective', 'corrective', 'preventive']),
                'root_cause' => $this->pick([
                    'Tool wear compensation not applied between shifts',
                    'Incoming inspection sampling plan too loose for this supplier',
                    'Fixture datum worn beyond service limit',
                    'Operator work instruction out of date after design change',
                ]),
                'action_description' => 'Revise the control plan, retrain the cell and verify over three production runs.',
                'assigned_to' => $this->userId,
                'due_date' => $date->copy()->addDays(45)->toDateString(),
                'status' => $this->pick(['closed', 'closed', 'in_progress', 'open', 'effectiveness_review']),
            ], $date);
        }

        foreach ([
            ['GAU-001', 'Micrometer 0-25mm', 'Mitutoyo'], ['GAU-002', 'Micrometer 25-50mm', 'Mitutoyo'],
            ['GAU-003', 'Bore gauge 40-60mm', 'Bowers'], ['GAU-004', 'Vernier caliper 300mm', 'Mitutoyo'],
            ['GAU-005', 'Surface roughness tester', 'Taylor Hobson'], ['GAU-006', 'Hardness tester HRC', 'Struers'],
            ['GAU-007', 'CMM reference sphere', 'Zeiss'], ['GAU-008', 'Torque wrench 20-100Nm', 'Norbar'],
            ['GAU-009', 'Torque wrench 100-400Nm', 'Norbar'], ['GAU-010', 'Dial indicator 0.01mm', 'Mitutoyo'],
            ['GAU-011', 'Gear roll tester', 'Klingelnberg'], ['GAU-012', 'Thermocouple, furnace 1', 'Eurotherm'],
            ['GAU-013', 'Thermocouple, furnace 2', 'Eurotherm'], ['GAU-014', 'Pressure gauge, test rig', 'Wika'],
            ['GAU-015', 'Slip gauge set', 'Mitutoyo'], ['GAU-016', 'Height gauge 600mm', 'Trimos'],
        ] as $i => [$code, $name, $maker]) {
            $last = Carbon::today()->subDays(mt_rand(20, 400));
            $next = $last->copy()->addYear();

            $this->upsert('calibrations', ['company_id' => $this->companyId, 'asset_code' => $code], [
                'name' => $name,
                'manufacturer' => $maker,
                'serial_number' => strtoupper(Str::random(2)).mt_rand(10000, 99999),
                'location_warehouse_id' => $this->pick($this->warehouseIds),
                'last_calibration_date' => $last->toDateString(),
                'next_calibration_date' => $next->toDateString(),
                'calibration_frequency' => 'annual',
                'certificate_number' => 'CERT-'.mt_rand(100000, 999999),
                'status' => $next->isPast() ? 'overdue' : ($next->diffInDays(Carbon::today()) < 30 ? 'due' : 'active'),
            ]);
        }

        for ($i = 1; $i <= 12; $i++) {
            $date = $this->dateInHistory();
            $number = 'CMP-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT);

            $this->upsert('complaints', ['company_id' => $this->companyId, 'number' => $number], [
                'customer_id' => $this->pick($this->customerIds),
                'description' => $this->pick([
                    'Gearbox ran hot within 40 hours of commissioning',
                    'Delivery short against packing list',
                    'Paint finish blemished on two units',
                    'Documentation pack missing test certificates',
                    'Coupling bore not to agreed tolerance',
                ]),
                'severity' => $this->pick(['minor', 'minor', 'major', 'critical']),
                'received_date' => $date->toDateString(),
                'status' => $this->pick(['closed', 'closed', 'resolved', 'investigating', 'open']),
            ], $date);
        }
    }

    /* ── maintenance ──────────────────────────────────────────────────────── */

    private function maintenance(): void
    {
        foreach ([
            ['AST-001', 'CNC Lathe LB3000 #1', 'machine', 'critical'],
            ['AST-002', 'CNC Lathe LB3000 #2', 'machine', 'critical'],
            ['AST-003', 'CNC Lathe LB2000', 'machine', 'high'],
            ['AST-004', 'Machining Centre DMU50', 'machine', 'critical'],
            ['AST-005', 'Gear Hobber LC180', 'machine', 'critical'],
            ['AST-006', 'Gear Grinder RZ60', 'machine', 'critical'],
            ['AST-007', 'Heat Treatment Furnace 1', 'machine', 'high'],
            ['AST-008', 'Heat Treatment Furnace 2', 'machine', 'high'],
            ['AST-009', 'Assembly Line A', 'line', 'high'],
            ['AST-010', 'Test Rig — 400Nm', 'machine', 'medium'],
            ['AST-011', 'CMM Contura', 'machine', 'high'],
            ['AST-012', 'Compressor Plant', 'utility', 'critical'],
            ['AST-013', 'Chiller Unit', 'utility', 'medium'],
            ['AST-014', 'Overhead Crane 10t', 'facility', 'high'],
            ['AST-015', 'Forklift H30 #1', 'vehicle', 'medium'],
            ['AST-016', 'Forklift H30 #2', 'vehicle', 'low'],
        ] as [$code, $name, $type, $criticality]) {
            $this->assetIds[] = $this->upsert('assets', ['company_id' => $this->companyId, 'code' => $code], [
                'name' => $name,
                'type' => $type,
                'plant_id' => $this->plantId,
                'criticality' => $criticality,
                'manufacturer' => $this->pick(['Okuma', 'DMG Mori', 'Liebherr', 'Reishauer', 'Linde', 'Zeiss']),
                'serial_number' => strtoupper(Str::random(3)).mt_rand(10000, 99999),
                'commissioning_date' => Carbon::today()->subMonths(mt_rand(12, 120))->toDateString(),
                'status' => mt_rand(1, 12) === 1 ? 'under_maintenance' : 'active',
            ]);
        }

        $work = [
            ['preventive', 'Scheduled 500-hour service — filters, oils and way lubrication'],
            ['preventive', 'Quarterly spindle inspection and vibration check'],
            ['breakdown', 'Coolant pump failure — line stopped'],
            ['breakdown', 'Tool changer fault, arm not indexing'],
            ['predictive', 'Vibration trend exceeded alarm threshold — bearing check'],
            ['improvement', 'Guard interlock upgrade to current standard'],
        ];

        for ($i = 1; $i <= 38; $i++) {
            $date = $this->dateInHistory();
            [$type, $description] = $work[$i % count($work)];
            $number = 'WO-'.str_pad((string) $i, 5, '0', STR_PAD_LEFT);
            $done = mt_rand(1, 10) > 3;

            $this->upsert('maintenance_orders', ['company_id' => $this->companyId, 'number' => $number], [
                'asset_id' => $this->pick($this->assetIds),
                'type' => $type,
                'priority' => $type === 'breakdown' ? $this->pick(['high', 'emergency']) : $this->pick(['low', 'normal']),
                'description' => $description,
                'requested_by' => $this->userId,
                'assigned_to' => $this->userId,
                'requested_date' => $date->toDateString(),
                'scheduled_date' => $date->copy()->addDays(mt_rand(1, 14))->toDateString(),
                'start_date' => $done ? $date->copy()->addDays(2) : null,
                'end_date' => $done ? $date->copy()->addDays(mt_rand(2, 6)) : null,
                'estimated_hours' => mt_rand(2, 16),
                'actual_hours' => $done ? mt_rand(2, 20) : null,
                'estimated_cost' => mt_rand(200, 4000),
                'actual_cost' => $done ? mt_rand(200, 5200) : null,
                'status' => $done ? 'closed' : $this->pick(['requested', 'approved', 'scheduled', 'in_progress']),
            ], $date);
        }
    }

    /* ── workforce ────────────────────────────────────────────────────────── */

    private function workforce(): void
    {
        $skills = ['CNC Turning', 'CNC Milling', 'Gear Hobbing', 'Gear Grinding', 'Heat Treatment',
            'Assembly', 'Test Rig Operation', 'CMM Programming', 'Forklift', 'First Aid'];

        foreach ($this->employeeIds as $n => $employeeId) {
            foreach ($this->sample($skills, mt_rand(1, 3)) as $skill) {
                $exists = DB::table('skill_matrix')->where(['employee_id' => $employeeId, 'skill_name' => $skill])->exists();

                if ($exists) {
                    continue;
                }

                $certified = mt_rand(1, 10) > 3;

                DB::table('skill_matrix')->insert($this->onlyRealColumns('skill_matrix', [
                    'employee_id' => $employeeId,
                    'work_center_id' => $this->pick($this->workCenterIds),
                    'skill_name' => $skill,
                    'level' => $this->pick(['trainee', 'beginner', 'intermediate', 'intermediate', 'advanced', 'expert']),
                    'certification_date' => $certified ? Carbon::today()->subDays(mt_rand(60, 900))->toDateString() : null,
                    'certification_expiry' => $certified ? Carbon::today()->addDays(mt_rand(30, 700))->toDateString() : null,
                    'is_certified' => $certified,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }

        if (DB::table('time_bookings')->count() < 80) {
            $rows = [];

            for ($i = 0; $i < 260; $i++) {
                $date = $this->dateInHistory();
                $startHour = mt_rand(6, 14);
                $hours = round(mt_rand(10, 90) / 10, 2);

                $rows[] = [
                    'employee_id' => $this->pick($this->employeeIds),
                    'work_center_id' => $this->pick($this->workCenterIds),
                    'booking_date' => $date->toDateString(),
                    'start_time' => sprintf('%02d:00:00', $startHour),
                    'end_time' => sprintf('%02d:00:00', min(23, $startHour + (int) ceil($hours))),
                    'hours' => $hours,
                    'type' => $this->pick(['direct', 'direct', 'direct', 'setup', 'indirect', 'idle']),
                    'created_at' => $date,
                    'updated_at' => $date,
                ];
            }

            foreach (array_chunk($rows, 150) as $chunk) {
                DB::table('time_bookings')->insert(array_map(fn ($r) => $this->onlyRealColumns('time_bookings', $r), $chunk));
            }
        }

        for ($m = self::MONTHS; $m >= 1; $m--) {
            $period = Carbon::today()->subMonths($m);
            $number = 'PR-'.$period->format('Y-m');
            $gross = round(mt_rand(180000, 260000) + mt_rand(0, 99) / 100, 2);
            $deductions = round($gross * 0.28, 2);

            $this->upsert('payroll_runs', ['number' => $number], [
                'period' => $period->format('Y-m'),
                'prepared_by' => $this->userId,
                'total_gross' => $gross,
                'total_deductions' => $deductions,
                'total_net' => round($gross - $deductions, 2),
                'status' => $m <= 1 ? 'draft' : 'posted',
            ], $period);
        }
    }

    /* ── helpers ──────────────────────────────────────────────────────────── */

    /**
     * Insert if the natural key is new, otherwise leave the existing row alone and
     * return its id. This is what makes the seeder safe to run twice and safe to
     * run over records someone entered by hand.
     *
     * @param  array<string, mixed>  $key
     * @param  array<string, mixed>  $values
     */
    private function upsert(string $table, array $key, array $values, ?Carbon $timestamp = null): int
    {
        $existing = DB::table($table)->where($key)->value('id');

        if ($existing) {
            return (int) $existing;
        }

        $stamp = $timestamp ?? now();

        return (int) DB::table($table)->insertGetId(
            $this->onlyRealColumns($table, $key + $values + ['created_at' => $stamp, 'updated_at' => $stamp]),
        );
    }

    /**
     * Drop anything the table does not actually have.
     *
     * These thirty-odd tables were written by several hands and do not agree about
     * what a master record carries — one has `is_base`, its neighbour does not.
     * Filtering against the real schema means a column this seeder offers and the
     * table does not want is simply not written, instead of the whole run dying on
     * "Unknown column". Every NOT NULL column is still supplied explicitly, so
     * nothing that matters can be silently dropped.
     *
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function onlyRealColumns(string $table, array $values): array
    {
        $this->schema[$table] ??= collect(Schema::getColumns($table))
            ->mapWithKeys(fn ($c) => [$c['name'] => (bool) $c['nullable']])
            ->all();

        // Most tables here carry a surrogate `uuid` that is NOT NULL and unique,
        // filled by a model boot hook these raw inserts bypass. Supplying it
        // whenever the column exists is cheaper, and safer, than remembering to
        // pass one at each of forty call sites.
        if (array_key_exists('uuid', $this->schema[$table]) && ! isset($values['uuid'])) {
            $values['uuid'] = (string) Str::uuid();
        }

        return collect(array_intersect_key($values, $this->schema[$table]))
            // A NOT NULL column with a default takes "leave it out", not an
            // explicit null — `actual_hours` on an unfinished work order, say.
            ->reject(fn ($value, $column) => $value === null && $this->schema[$table][$column] === false)
            ->all();
    }

    /**
     * Child rows for a document, skipped entirely if it already has some.
     *
     * @param  list<array<string, mixed>>  $rows
     * @return list<int> the ids, in the order given
     */
    private function lines(string $table, string $foreignKey, int $parentId, array $rows, Carbon $date): array
    {
        if ($rows === [] || DB::table($table)->where($foreignKey, $parentId)->exists()) {
            return DB::table($table)->where($foreignKey, $parentId)->pluck('id')->all();
        }

        $ids = [];

        foreach ($rows as $row) {
            $ids[] = (int) DB::table($table)->insertGetId(
                $this->onlyRealColumns($table, [$foreignKey => $parentId] + $row + ['created_at' => $date, 'updated_at' => $date]),
            );
        }

        return $ids;
    }

    /** A date somewhere in the history window, weighted slightly toward recent. */
    private function dateInHistory(): Carbon
    {
        $days = (int) round(self::MONTHS * 30.4);

        return Carbon::today()->subDays(mt_rand(0, $days) - mt_rand(0, (int) ($days * 0.15)) > 0
            ? mt_rand(0, $days)
            : mt_rand(0, (int) ($days / 2)));
    }

    /** @param list<mixed> $values */
    private function pick(array $values): mixed
    {
        return $values[array_rand($values)];
    }

    /**
     * @param  list<mixed>  $values
     * @return list<mixed>
     */
    private function sample(array $values, int $count): array
    {
        if ($values === []) {
            return [];
        }

        $count = min($count, count($values));
        $keys = (array) array_rand($values, $count);

        return array_values(array_map(fn ($k) => $values[$k], $keys));
    }
}
