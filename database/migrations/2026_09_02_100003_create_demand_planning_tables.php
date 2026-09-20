<?php
use Illuminate\Database\Migrations\Migration;
return new class extends Migration {
    public function up(): void {
        Schema::create('forecasts', function ($t) {
            $t->id(); $t->uuid('uuid');
            $t->string('number', 30)->unique();
            $t->foreignId('item_id')->constrained();
            $t->foreignId('plant_id')->constrained();
            $t->date('forecast_date');
            $t->decimal('quantity', 12, 4);
            $t->enum('type', ['forecast', 'firm_order', 'safety_stock', 'inter_plant'])->default('forecast');
            $t->enum('status', ['active', 'consumed', 'cancelled'])->default('active');
            $t->timestamps();
        });
        Schema::create('planned_orders', function ($t) {
            $t->id(); $t->uuid('uuid');
            $t->string('number', 30)->unique();
            $t->foreignId('item_id')->constrained();
            $t->foreignId('plant_id')->constrained();
            $t->enum('source', ['mrp', 'manual', 'reorder_point']);
            $t->decimal('planned_quantity', 12, 4);
            $t->date('required_date');
            $t->date('planned_start_date')->nullable();
            $t->enum('order_type', ['purchase', 'production', 'transfer']);
            $t->enum('status', ['planned', 'approved', 'converted', 'cancelled'])->default('planned');
            $t->foreignId('converted_order_id')->nullable();
            $t->string('converted_order_type')->nullable();
            $t->timestamps();
        });
        Schema::create('mrp_runs', function ($t) {
            $t->id();
            $t->foreignId('plant_id')->constrained();
            $t->foreignId('run_by')->constrained('users');
            $t->datetime('run_at');
            $t->json('parameters')->nullable();
            $t->integer('items_processed')->default(0);
            $t->integer('planned_orders_generated')->default(0);
            $t->integer('exceptions_count')->default(0);
            $t->enum('status', ['running', 'completed', 'failed'])->default('running');
            $t->timestamps();
        });
        Schema::create('capacity_loads', function ($t) {
            $t->id();
            $t->foreignId('work_center_id')->constrained();
            $t->foreignId('plant_id')->constrained();
            $t->date('load_date');
            $t->decimal('available_hours', 8, 2);
            $t->decimal('loaded_hours', 8, 2);
            $t->decimal('utilization_percentage', 5, 2)->nullable();
            $t->timestamps();
        });
    }
    public function down(): void {
    }
};