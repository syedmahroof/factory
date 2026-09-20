<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_requisitions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained('companies');
            $table->string('number', 30)->unique();
            $table->enum('source', ['manual', 'mrp', 'min_max', 'maintenance', 'production'])->default('manual');
            $table->unsignedBigInteger('requested_by')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->date('required_date');
            $table->text('justification')->nullable();
            $table->enum('status', ['draft', 'submitted', 'approved', 'rejected', 'partially_fulfilled', 'fulfilled', 'cancelled'])->default('draft');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('purchase_requisition_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_requisition_id')->constrained()->cascadeOnDelete();
            $table->integer('line_number');
            $table->foreignId('item_id')->constrained('items');
            $table->unsignedBigInteger('plant_id')->nullable();
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->decimal('quantity', 15, 4);
            $table->foreignId('uom_id')->constrained('uoms');
            $table->date('required_date');
            $table->decimal('estimated_cost', 15, 4)->nullable();
            $table->unsignedBigInteger('production_order_id')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['pending', 'approved', 'ordered', 'cancelled'])->default('pending');
            $table->timestamps();
        });

        Schema::create('rfqs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained('companies');
            $table->string('number', 30)->unique();
            $table->unsignedBigInteger('purchase_requisition_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->date('issue_date');
            $table->date('closing_date');
            $table->text('terms_and_conditions')->nullable();
            $table->enum('status', ['draft', 'sent', 'received', 'evaluated', 'awarded', 'cancelled'])->default('draft');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('rfq_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rfq_id')->constrained()->cascadeOnDelete();
            $table->integer('line_number');
            $table->foreignId('item_id')->constrained('items');
            $table->decimal('quantity', 15, 4);
            $table->foreignId('uom_id')->constrained('uoms');
            $table->text('specifications')->nullable();
            $table->timestamps();
        });

        Schema::create('rfq_suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rfq_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers');
            $table->date('quoted_date')->nullable();
            $table->decimal('total_amount', 15, 4)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('rfq_supplier_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rfq_supplier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('rfq_line_id')->constrained('rfq_lines');
            $table->decimal('unit_price', 15, 4);
            $table->decimal('lead_time_days', 5, 1)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained('companies');
            $table->string('number', 30)->unique();
            $table->enum('type', ['standard', 'blanket', 'service', 'import', 'subcontract'])->default('standard');
            $table->foreignId('supplier_id')->constrained('suppliers');
            $table->unsignedBigInteger('rfq_id')->nullable();
            $table->unsignedBigInteger('purchase_requisition_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('plant_id')->nullable();
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->date('order_date');
            $table->date('expected_delivery_date')->nullable();
            $table->string('payment_terms', 30)->default('NET30');
            $table->string('currency', 3)->default('USD');
            $table->decimal('subtotal', 15, 4)->default(0);
            $table->decimal('tax_amount', 15, 4)->default(0);
            $table->decimal('shipping_cost', 15, 4)->default(0);
            $table->decimal('total_amount', 15, 4)->default(0);
            $table->text('terms_and_conditions')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['draft', 'submitted', 'approved', 'partially_received', 'received', 'closed', 'cancelled'])->default('draft');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('purchase_order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
            $table->integer('line_number');
            $table->foreignId('item_id')->constrained('items');
            $table->decimal('quantity', 15, 4);
            $table->decimal('received_quantity', 15, 4)->default(0);
            $table->decimal('invoiced_quantity', 15, 4)->default(0);
            $table->foreignId('uom_id')->constrained('uoms');
            $table->decimal('unit_price', 15, 4);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('discount_rate', 5, 2)->default(0);
            $table->decimal('line_total', 15, 4)->default(0);
            $table->date('required_date');
            $table->date('expected_date')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['pending', 'partially_received', 'received', 'invoiced', 'closed', 'cancelled'])->default('pending');
            $table->timestamps();
        });

        Schema::create('goods_receipts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained('companies');
            $table->string('number', 30)->unique();
            $table->unsignedBigInteger('purchase_order_id')->nullable();
            $table->foreignId('supplier_id')->constrained('suppliers');
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->unsignedBigInteger('received_by')->nullable();
            $table->date('receipt_date');
            $table->unsignedBigInteger('delivery_note_id')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['draft', 'received', 'inspected', 'put_away', 'rejected'])->default('draft');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('goods_receipt_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goods_receipt_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('purchase_order_line_id')->nullable();
            $table->foreignId('item_id')->constrained('items');
            $table->decimal('quantity', 15, 4);
            $table->decimal('accepted_quantity', 15, 4)->default(0);
            $table->decimal('rejected_quantity', 15, 4)->default(0);
            $table->foreignId('uom_id')->constrained('uoms');
            $table->unsignedBigInteger('lot_id')->nullable();
            $table->unsignedBigInteger('stock_status_id')->nullable();
            $table->unsignedBigInteger('bin_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('landed_costs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goods_receipt_id')->constrained()->cascadeOnDelete();
            $table->string('charge_type', 30);
            $table->decimal('amount', 15, 4);
            $table->string('currency', 3)->default('USD');
            $table->decimal('exchange_rate', 10, 6)->default(1);
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landed_costs');
        Schema::dropIfExists('goods_receipt_lines');
        Schema::dropIfExists('goods_receipts');
        Schema::dropIfExists('purchase_order_lines');
        Schema::dropIfExists('purchase_orders');
        Schema::dropIfExists('rfq_supplier_lines');
        Schema::dropIfExists('rfq_suppliers');
        Schema::dropIfExists('rfq_lines');
        Schema::dropIfExists('rfqs');
        Schema::dropIfExists('purchase_requisition_lines');
        Schema::dropIfExists('purchase_requisitions');
    }
};
