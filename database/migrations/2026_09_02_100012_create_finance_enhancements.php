<?php
use Illuminate\Database\Migrations\Migration;
return new class extends Migration {
    public function up(): void {
        Schema::create('journal_dimensions', function ($t) {
            $t->id();
            $t->foreignId('journal_line_id')->constrained('journal_lines');
            $t->foreignId('plant_id')->nullable()->constrained();
            $t->foreignId('department_id')->nullable();
            $t->foreignId('cost_center_id')->nullable();
            $t->foreignId('profit_center_id')->nullable();
            $t->foreignId('project_id')->nullable();
            $t->foreignId('production_order_id')->nullable();
            $t->timestamps();
        });
        Schema::create('supplier_invoices', function ($t) {
            $t->id(); $t->uuid('uuid');
            $t->string('invoice_number', 50)->unique();
            $t->foreignId('supplier_id')->constrained();
            $t->foreignId('purchase_order_id')->nullable()->constrained();
            $t->foreignId('goods_receipt_id')->nullable()->constrained();
            $t->date('invoice_date');
            $t->date('due_date');
            $t->decimal('total_amount', 14, 2);
            $t->decimal('tax_amount', 14, 2)->default(0);
            $t->decimal('net_amount', 14, 2);
            $t->decimal('discount_amount', 14, 2)->default(0);
            $t->decimal('balance_amount', 14, 2);
            $t->enum('status', ['draft', 'open', 'partially_paid', 'paid', 'cancelled'])->default('draft');
            $t->timestamps();
        });
        Schema::create('customer_invoices', function ($t) {
            $t->id(); $t->uuid('uuid');
            $t->string('invoice_number', 50)->unique();
            $t->foreignId('customer_id')->constrained();
            $t->foreignId('sales_order_id')->nullable()->constrained();
            $t->foreignId('shipment_id')->nullable();
            $t->date('invoice_date');
            $t->date('due_date');
            $t->decimal('total_amount', 14, 2);
            $t->decimal('tax_amount', 14, 2)->default(0);
            $t->decimal('net_amount', 14, 2);
            $t->decimal('discount_amount', 14, 2)->default(0);
            $t->decimal('balance_amount', 14, 2);
            $t->enum('status', ['draft', 'open', 'partially_paid', 'paid', 'cancelled'])->default('draft');
            $t->timestamps();
        });
        Schema::create('bank_accounts', function ($t) {
            $t->id();
            $t->foreignId('account_id')->constrained('accounts');
            $t->string('bank_name');
            $t->string('account_number', 50);
            $t->string('routing_number', 20)->nullable();
            $t->string('swift_code', 20)->nullable();
            $t->string('currency', 10)->default('USD');
            $t->decimal('balance', 14, 2)->default(0);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('bank_reconciliations', function ($t) {
            $t->id();
            $t->foreignId('bank_account_id')->constrained('bank_accounts');
            $t->date('statement_date');
            $t->decimal('statement_balance', 14, 2);
            $t->decimal('book_balance', 14, 2);
            $t->decimal('difference', 14, 2)->default(0);
            $t->enum('status', ['draft', 'reconciled'])->default('draft');
            $t->foreignId('reconciled_by')->nullable()->constrained('users');
            $t->timestamps();
        });
        Schema::create('tax_rules', function ($t) {
            $t->id();
            $t->string('name');
            $t->string('code', 20)->unique();
            $t->enum('type', ['gst', 'vat', 'tds', 'withholding', 'sales_tax']);
            $t->decimal('rate', 5, 2);
            $t->foreignId('account_id')->nullable()->constrained('accounts');
            $t->date('effective_from');
            $t->date('effective_until')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('fixed_assets', function ($t) {
            $t->id(); $t->uuid('uuid');
            $t->string('number', 30)->unique();
            $t->string('name');
            $t->foreignId('account_id')->constrained('accounts');
            $t->foreignId('depreciation_account_id')->nullable()->constrained('accounts');
            $t->foreignId('asset_id')->nullable()->constrained();
            $t->decimal('acquisition_cost', 14, 2);
            $t->decimal('salvage_value', 14, 2)->default(0);
            $t->integer('useful_life_months');
            $t->string('depreciation_method', 30)->default('straight_line');
            $t->decimal('accumulated_depreciation', 14, 2)->default(0);
            $t->decimal('book_value', 14, 2);
            $t->date('acquisition_date');
            $t->enum('status', ['active', 'fully_depreciated', 'disposed', 'impaired'])->default('active');
            $t->timestamps();
        });
        Schema::create('depreciation_entries', function ($t) {
            $t->id();
            $t->foreignId('fixed_asset_id')->constrained('fixed_assets');
            $t->foreignId('journal_id')->nullable()->constrained();
            $t->date('depreciation_date');
            $t->decimal('depreciation_amount', 14, 2);
            $t->decimal('accumulated_after', 14, 2);
            $t->enum('status', ['draft', 'posted'])->default('draft');
            $t->timestamps();
        });
        Schema::create('budget_lines', function ($t) {
            $t->id();
            $t->foreignId('budget_id')->constrained();
            $t->foreignId('account_id')->constrained('accounts');
            $t->foreignId('cost_center_id')->nullable();
            $t->string('period', 20);
            $t->decimal('planned_amount', 14, 2);
            $t->decimal('actual_amount', 14, 2)->default(0);
            $t->decimal('committed_amount', 14, 2)->default(0);
            $t->decimal('variance', 14, 2)->default(0);
            $t->timestamps();
        });
        Schema::create('period_closes', function ($t) {
            $t->id();
            $t->foreignId('fiscal_period_id')->constrained();
            $t->string('module', 50);
            $t->foreignId('closed_by')->constrained('users');
            $t->datetime('closed_at');
            $t->boolean('is_locked')->default(true);
            $t->timestamps();
        });
        Schema::create('cost_rollups', function ($t) {
            $t->id();
            $t->foreignId('item_id')->constrained();
            $t->date('effective_date');
            $t->decimal('material_cost', 14, 4)->default(0);
            $t->decimal('labor_cost', 14, 4)->default(0);
            $t->decimal('machine_cost', 14, 4)->default(0);
            $t->decimal('overhead_cost', 14, 4)->default(0);
            $t->decimal('total_standard_cost', 14, 4);
            $t->foreignId('approved_by')->nullable()->constrained('users');
            $t->timestamps();
        });
    }
    public function down(): void {
    }
};