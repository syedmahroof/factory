<?php
use Illuminate\Database\Migrations\Migration;
return new class extends Migration {
    public function up(): void {
        Schema::create('batch_records', function ($t) {
            $t->id(); $t->uuid('uuid');
            $t->string('batch_number', 50)->unique();
            $t->foreignId('production_order_id')->constrained();
            $t->foreignId('item_id')->constrained();
            $t->decimal('planned_quantity', 12, 4);
            $t->decimal('actual_quantity', 12, 4)->nullable();
            $t->decimal('yield_percentage', 5, 2)->nullable();
            $t->date('manufacture_date');
            $t->date('expiry_date')->nullable();
            $t->date('retest_date')->nullable();
            $t->json('process_values')->nullable();
            $t->enum('status', ['open', 'in_progress', 'completed', 'reviewed', 'approved', 'rejected'])->default('open');
            $t->timestamps();
        });
        Schema::create('wip_balances', function ($t) {
            $t->id();
            $t->foreignId('production_order_id')->constrained();
            $t->foreignId('item_id')->constrained();
            $t->foreignId('work_center_id')->nullable()->constrained();
            $t->decimal('quantity', 12, 4)->default(0);
            $t->decimal('material_cost', 14, 4)->default(0);
            $t->decimal('labor_cost', 14, 4)->default(0);
            $t->decimal('overhead_cost', 14, 4)->default(0);
            $t->decimal('total_cost', 14, 4)->default(0);
            $t->timestamps();
        });
        if (! Schema::hasTable('rework_orders')) {
            Schema::create('rework_orders', function ($t) {
                $t->id(); $t->uuid('uuid');
                $t->string('number', 30)->unique();
                $t->foreignId('production_order_id')->constrained();
                $t->foreignId('ncr_id')->nullable();
                $t->foreignId('item_id')->constrained();
                $t->decimal('quantity', 12, 4);
                $t->text('reason');
                $t->text('disposition')->nullable();
                $t->decimal('rework_cost', 14, 4)->nullable();
                $t->enum('status', ['open', 'in_progress', 'completed', 'cancelled'])->default('open');
                $t->timestamps();
            });
        }
        Schema::create('scrap_records', function ($t) {
            $t->id();
            $t->foreignId('production_order_id')->constrained();
            $t->foreignId('item_id')->constrained();
            $t->decimal('quantity', 12, 4);
            $t->foreignId('warehouse_id')->nullable()->constrained();
            $t->foreignId('reason_id')->nullable();
            $t->text('reason_text')->nullable();
            $t->decimal('scrap_cost', 14, 4)->nullable();
            $t->timestamps();
        });
    }
    public function down(): void {
    }
};