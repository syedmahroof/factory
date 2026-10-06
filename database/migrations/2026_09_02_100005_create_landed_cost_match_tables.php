<?php
use Illuminate\Database\Migrations\Migration;
return new class extends Migration {
    public function up(): void {
        // Replaces the per-charge layout from the procurement migration, which nothing reads.
        Schema::dropIfExists('landed_costs');
        Schema::create('landed_costs', function ($t) {
            $t->id(); $t->uuid('uuid');
            $t->foreignId('goods_receipt_id')->constrained();
            $t->decimal('freight', 12, 2)->default(0);
            $t->decimal('duty', 12, 2)->default(0);
            $t->decimal('insurance', 12, 2)->default(0);
            $t->decimal('other_charges', 12, 2)->default(0);
            $t->decimal('total_landed_cost', 14, 2);
            $t->enum('allocation_method', ['value', 'quantity', 'weight', 'volume'])->default('value');
            $t->enum('status', ['draft', 'posted'])->default('draft');
            $t->timestamps();
        });
        Schema::create('three_way_matches', function ($t) {
            $t->id();
            $t->foreignId('purchase_order_id')->constrained();
            $t->foreignId('goods_receipt_id')->constrained();
            $t->foreignId('supplier_invoice_id')->nullable();
            $t->decimal('quantity_tolerance', 5, 2)->default(2.00);
            $t->decimal('price_tolerance', 5, 2)->default(1.00);
            $t->decimal('po_total', 14, 2);
            $t->decimal('grn_total', 14, 2);
            $t->decimal('invoice_total', 14, 2)->nullable();
            $t->decimal('quantity_variance', 5, 2)->nullable();
            $t->decimal('price_variance', 5, 2)->nullable();
            $t->enum('status', ['matched', 'variance', 'exception', 'resolved'])->default('matched');
            $t->text('exception_notes')->nullable();
            $t->timestamps();
        });
    }
    public function down(): void { }
};