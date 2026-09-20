<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained('companies');
            $table->string('number', 30)->unique();
            $table->foreignId('customer_id')->constrained('customers');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->date('quotation_date');
            $table->date('valid_until');
            $table->string('currency', 3)->default('USD');
            $table->decimal('subtotal', 15, 4)->default(0);
            $table->decimal('tax_amount', 15, 4)->default(0);
            $table->decimal('discount_amount', 15, 4)->default(0);
            $table->decimal('total_amount', 15, 4)->default(0);
            $table->text('terms_and_conditions')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['draft', 'sent', 'approved', 'rejected', 'converted', 'expired'])->default('draft');
            $table->integer('revision')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('quotation_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained()->cascadeOnDelete();
            $table->integer('line_number');
            $table->foreignId('item_id')->constrained('items');
            $table->decimal('quantity', 15, 4);
            $table->foreignId('uom_id')->constrained('uoms');
            $table->decimal('unit_price', 15, 4);
            $table->decimal('discount_rate', 5, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('line_total', 15, 4)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('sales_orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained('companies');
            $table->string('number', 30)->unique();
            $table->foreignId('customer_id')->constrained('customers');
            $table->unsignedBigInteger('quotation_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('plant_id')->nullable();
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->date('order_date');
            $table->date('requested_delivery_date')->nullable();
            $table->date('confirmed_delivery_date')->nullable();
            $table->string('po_number')->nullable();
            $table->string('currency', 3)->default('USD');
            $table->string('payment_terms', 30)->default('NET30');
            $table->decimal('subtotal', 15, 4)->default(0);
            $table->decimal('tax_amount', 15, 4)->default(0);
            $table->decimal('discount_amount', 15, 4)->default(0);
            $table->decimal('shipping_cost', 15, 4)->default(0);
            $table->decimal('total_amount', 15, 4)->default(0);
            $table->text('shipping_address')->nullable();
            $table->text('notes')->nullable();
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->enum('status', ['draft', 'confirmed', 'allocated', 'partially_picked', 'picked', 'packed', 'shipped', 'delivered', 'invoiced', 'closed', 'cancelled'])->default('draft');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('sales_order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_order_id')->constrained()->cascadeOnDelete();
            $table->integer('line_number');
            $table->foreignId('item_id')->constrained('items');
            $table->decimal('quantity', 15, 4);
            $table->decimal('picked_quantity', 15, 4)->default(0);
            $table->decimal('shipped_quantity', 15, 4)->default(0);
            $table->decimal('invoiced_quantity', 15, 4)->default(0);
            $table->foreignId('uom_id')->constrained('uoms');
            $table->decimal('unit_price', 15, 4);
            $table->decimal('discount_rate', 5, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('line_total', 15, 4)->default(0);
            $table->date('requested_date');
            $table->date('confirmed_date')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['pending', 'allocated', 'picked', 'packed', 'shipped', 'delivered', 'cancelled'])->default('pending');
            $table->timestamps();
        });

        Schema::create('order_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_order_line_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('stock_balance_id')->nullable();
            $table->unsignedBigInteger('production_order_id')->nullable();
            $table->decimal('quantity', 15, 4);
            $table->enum('status', ['reserved', 'picked', 'shipped', 'cancelled'])->default('reserved');
            $table->timestamps();
        });

        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained('companies');
            $table->string('number', 30)->unique();
            $table->foreignId('sales_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->unsignedBigInteger('shipped_by')->nullable();
            $table->date('shipment_date');
            $table->string('carrier')->nullable();
            $table->string('tracking_number')->nullable();
            $table->text('shipping_address');
            $table->decimal('shipping_cost', 15, 4)->default(0);
            $table->text('notes')->nullable();
            $table->enum('status', ['pending', 'packed', 'shipped', 'in_transit', 'delivered', 'returned'])->default('pending');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('shipment_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sales_order_line_id')->constrained('sales_order_lines');
            $table->foreignId('item_id')->constrained('items');
            $table->decimal('quantity', 15, 4);
            $table->unsignedBigInteger('lot_id')->nullable();
            $table->unsignedBigInteger('serial_id')->nullable();
            $table->timestamps();
        });

        Schema::create('sales_invoices', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained('companies');
            $table->string('number', 30)->unique();
            $table->foreignId('sales_order_id')->constrained('sales_orders');
            $table->foreignId('customer_id')->constrained('customers');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->date('invoice_date');
            $table->date('due_date');
            $table->string('currency', 3)->default('USD');
            $table->decimal('subtotal', 15, 4)->default(0);
            $table->decimal('tax_amount', 15, 4)->default(0);
            $table->decimal('total_amount', 15, 4)->default(0);
            $table->decimal('amount_paid', 15, 4)->default(0);
            $table->decimal('balance_due', 15, 4)->default(0);
            $table->text('notes')->nullable();
            $table->enum('status', ['draft', 'sent', 'paid', 'partially_paid', 'overdue', 'cancelled'])->default('draft');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('customer_returns', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained('companies');
            $table->string('number', 30)->unique();
            $table->foreignId('customer_id')->constrained('customers');
            $table->unsignedBigInteger('sales_order_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->date('return_date');
            $table->text('reason')->nullable();
            $table->enum('status', ['draft', 'received', 'inspected', 'dispositioned', 'closed'])->default('draft');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('customer_return_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_return_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items');
            $table->decimal('quantity', 15, 4);
            $table->unsignedBigInteger('lot_id')->nullable();
            $table->unsignedBigInteger('serial_id')->nullable();
            $table->enum('disposition', ['restock', 'rework', 'scrap', 'return_to_supplier'])->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_return_lines');
        Schema::dropIfExists('customer_returns');
        Schema::dropIfExists('sales_invoices');
        Schema::dropIfExists('shipment_lines');
        Schema::dropIfExists('shipments');
        Schema::dropIfExists('order_allocations');
        Schema::dropIfExists('sales_order_lines');
        Schema::dropIfExists('sales_orders');
        Schema::dropIfExists('quotation_lines');
        Schema::dropIfExists('quotations');
    }
};
