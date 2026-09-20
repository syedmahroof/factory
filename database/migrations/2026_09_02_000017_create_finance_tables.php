<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('code', 20);
            $table->string('name');
            $table->enum('type', ['asset', 'liability', 'equity', 'revenue', 'expense']);
            $table->integer('level')->default(1);
            $table->foreignId('parent_id')->nullable()->constrained('account_groups')->nullOnDelete();
            $table->boolean('is_control_account')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('code', 20);
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('account_group_id')->nullable();
            $table->enum('type', ['asset', 'liability', 'equity', 'revenue', 'expense']);
            $table->boolean('is_control_account')->default(false);
            $table->boolean('is_bank_account')->default(false);
            $table->boolean('is_cash_account')->default(false);
            $table->string('currency', 3)->default('USD');
            $table->decimal('opening_balance', 15, 4)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'code']);
        });

        Schema::create('fiscal_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['open', 'closing', 'closed', 'locked'])->default('open');
            $table->boolean('is_adjustment')->default(false);
            $table->timestamps();
        });

        Schema::create('tax_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('code', 20);
            $table->string('name');
            $table->enum('type', ['gst', 'vat', 'tds', 'withholding', 'sales_tax', 'excise', 'customs', 'other']);
            $table->decimal('rate', 5, 2);
            $table->foreignId('account_id')->constrained('accounts');
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('journals', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('number', 30)->unique();
            $table->foreignId('fiscal_period_id')->constrained();
            $table->date('date');
            $table->enum('type', ['general', 'sales', 'purchase', 'receipt', 'payment', 'adjustment', 'opening', 'closing']);
            $table->text('description')->nullable();
            $table->decimal('total_debit', 15, 4)->default(0);
            $table->decimal('total_credit', 15, 4)->default(0);
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->enum('status', ['draft', 'submitted', 'approved', 'posted', 'reversed'])->default('draft');
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->text('reversal_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('journal_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_id')->constrained()->cascadeOnDelete();
            $table->integer('line_number');
            $table->foreignId('account_id')->constrained('accounts');
            $table->decimal('debit', 15, 4)->default(0);
            $table->decimal('credit', 15, 4)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->decimal('exchange_rate', 10, 6)->default(1);
            $table->decimal('debit_local', 15, 4)->default(0);
            $table->decimal('credit_local', 15, 4)->default(0);
            $table->foreignId('cost_center_id')->nullable();
            $table->foreignId('profit_center_id')->nullable();
            $table->foreignId('plant_id')->nullable();
            $table->foreignId('department_id')->nullable();
            $table->foreignId('production_order_id')->nullable();
            $table->text('description')->nullable();
            $table->string('reference')->nullable();
            $table->timestamps();
        });

        Schema::create('supplier_invoices', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('number', 30)->unique();
            $table->foreignId('supplier_id')->constrained('suppliers');
            $table->foreignId('purchase_order_id')->nullable();
            $table->foreignId('goods_receipt_id')->nullable();
            $table->string('supplier_invoice_number')->nullable();
            $table->date('invoice_date');
            $table->date('due_date');
            $table->string('currency', 3)->default('USD');
            $table->decimal('subtotal', 15, 4)->default(0);
            $table->decimal('tax_amount', 15, 4)->default(0);
            $table->decimal('total_amount', 15, 4)->default(0);
            $table->decimal('amount_paid', 15, 4)->default(0);
            $table->decimal('balance_due', 15, 4)->default(0);
            $table->enum('status', ['draft', 'matched', 'approved', 'paid', 'partially_paid', 'overdue', 'cancelled'])->default('draft');
            $table->foreignId('journal_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('customer_invoices', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('number', 30)->unique();
            $table->foreignId('customer_id')->constrained('customers');
            $table->foreignId('sales_order_id')->nullable();
            $table->foreignId('shipment_id')->nullable();
            $table->date('invoice_date');
            $table->date('due_date');
            $table->string('currency', 3)->default('USD');
            $table->decimal('subtotal', 15, 4)->default(0);
            $table->decimal('tax_amount', 15, 4)->default(0);
            $table->decimal('total_amount', 15, 4)->default(0);
            $table->decimal('amount_paid', 15, 4)->default(0);
            $table->decimal('balance_due', 15, 4)->default(0);
            $table->enum('status', ['draft', 'sent', 'paid', 'partially_paid', 'overdue', 'cancelled'])->default('draft');
            $table->foreignId('journal_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('number', 30)->unique();
            $table->enum('type', ['supplier_payment', 'customer_receipt', 'bank_transfer', 'petty_cash']);
            $table->foreignId('account_id')->constrained('accounts'); // bank/cash account
            $table->foreignId('party_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('party_type')->nullable(); // supplier, customer
            $table->date('date');
            $table->decimal('amount', 15, 4);
            $table->string('currency', 3)->default('USD');
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('journal_id')->nullable();
            $table->enum('status', ['draft', 'approved', 'completed', 'cancelled'])->default('draft');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('accounts');
            $table->foreignId('fiscal_period_id')->constrained();
            $table->foreignId('cost_center_id')->nullable();
            $table->foreignId('plant_id')->nullable();
            $table->decimal('budget_amount', 15, 4);
            $table->decimal('committed_amount', 15, 4)->default(0);
            $table->decimal('actual_amount', 15, 4)->default(0);
            $table->decimal('variance', 15, 4)->default(0);
            $table->timestamps();
        });

        Schema::create('fixed_assets', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('number', 30)->unique();
            $table->foreignId('asset_id')->nullable()->constrained('assets')->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('account_id')->constrained('accounts');
            $table->foreignId('depreciation_account_id')->constrained('accounts');
            $table->foreignId('cost_center_id')->nullable();
            $table->date('acquisition_date');
            $table->decimal('acquisition_cost', 15, 4);
            $table->decimal('accumulated_depreciation', 15, 4)->default(0);
            $table->decimal('net_book_value', 15, 4)->default(0);
            $table->decimal('salvage_value', 15, 4)->default(0);
            $table->integer('useful_life_months');
            $table->enum('depreciation_method', ['straight_line', 'declining_balance', 'units_of_production'])->default('straight_line');
            $table->enum('status', ['active', 'fully_depreciated', 'sold', 'impaired', 'disposed'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('cost_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('work_center_id')->nullable();
            $table->enum('type', ['labor', 'machine', 'overhead', 'material']);
            $table->decimal('rate_per_hour', 15, 4);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('cost_rollups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('production_version_id')->nullable();
            $table->decimal('material_cost', 15, 4)->default(0);
            $table->decimal('labor_cost', 15, 4)->default(0);
            $table->decimal('machine_cost', 15, 4)->default(0);
            $table->decimal('overhead_cost', 15, 4)->default(0);
            $table->decimal('total_cost', 15, 4)->default(0);
            $table->decimal('cost_per_unit', 15, 4)->default(0);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->enum('status', ['draft', 'approved', 'obsolete'])->default('draft');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cost_rollups');
        Schema::dropIfExists('cost_rates');
        Schema::dropIfExists('fixed_assets');
        Schema::dropIfExists('budgets');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('customer_invoices');
        Schema::dropIfExists('supplier_invoices');
        Schema::dropIfExists('journal_lines');
        Schema::dropIfExists('journals');
        Schema::dropIfExists('tax_rules');
        Schema::dropIfExists('fiscal_periods');
        Schema::dropIfExists('accounts');
        Schema::dropIfExists('account_groups');
    }
};
