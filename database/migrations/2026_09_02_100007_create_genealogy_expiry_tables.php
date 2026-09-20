<?php
use Illuminate\Database\Migrations\Migration;
return new class extends Migration {
    public function up(): void {
        Schema::create('serial_genealogies', function ($t) {
            $t->id();
            $t->string('parent_serial_number');
            $t->string('child_serial_number');
            $t->foreignId('production_order_id')->nullable()->constrained();
            $t->foreignId('item_id')->constrained();
            $t->decimal('quantity', 12, 4)->default(1);
            $t->timestamps();
            $t->index(['parent_serial_number']);
            $t->index(['child_serial_number']);
        });
        Schema::create('stock_statuses', function ($t) {
            $t->id(); $t->string('name')->unique();
            $t->string('code', 20)->unique();
            $t->string('color', 20)->nullable();
            $t->boolean('available_for_issue')->default(false);
            $t->boolean('available_for_production')->default(false);
            $t->boolean('available_for_sale')->default(false);
            $t->boolean('available_for_mrp')->default(false);
            $t->timestamps();
        });
        Schema::create('cycle_counts', function ($t) {
            $t->id(); $t->uuid('uuid');
            $t->string('number', 30)->unique();
            $t->foreignId('warehouse_id')->constrained();
            $t->foreignId('created_by')->constrained('users');
            $t->date('count_date');
            $t->enum('type', ['cycle', 'abc', 'full', 'blind', 'recount'])->default('cycle');
            $t->enum('status', ['draft', 'in_progress', 'reviewed', 'approved', 'posted'])->default('draft');
            $t->timestamps();
        });
        Schema::create('cycle_count_lines', function ($t) {
            $t->id();
            $t->foreignId('cycle_count_id')->constrained()->cascadeOnDelete();
            $t->foreignId('item_id')->constrained();
            $t->foreignId('bin_id')->nullable();
            $t->string('lot_number')->nullable();
            $t->decimal('system_quantity', 12, 4);
            $t->decimal('counted_quantity', 12, 4)->nullable();
            $t->decimal('variance', 12, 4)->nullable();
            $t->text('reason')->nullable();
            $t->timestamps();
        });
    }
    public function down(): void {
    }
};