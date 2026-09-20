<?php
use Illuminate\Database\Migrations\Migration;
return new class extends Migration {
    public function up(): void {
        Schema::create('preventive_maintenance_schedules', function ($t) {
            $t->id();
            $t->foreignId('maintenance_plan_id')->constrained();
            $t->foreignId('asset_id')->constrained();
            $t->enum('trigger_type', ['calendar', 'meter', 'condition']);
            $t->integer('interval_days')->nullable();
            $t->decimal('meter_threshold', 12, 2)->nullable();
            $t->date('next_due_date')->nullable();
            $t->decimal('last_meter_reading', 12, 2)->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('spare_parts', function ($t) {
            $t->id();
            $t->foreignId('item_id')->constrained();
            $t->foreignId('asset_id')->nullable()->constrained();
            $t->integer('min_stock')->default(0);
            $t->integer('max_stock')->default(0);
            $t->integer('reorder_point')->default(0);
            $t->timestamps();
        });
        Schema::create('meter_readings', function ($t) {
            $t->id();
            $t->foreignId('asset_id')->constrained();
            $t->string('meter_name');
            $t->decimal('reading_value', 12, 2);
            $t->datetime('reading_date');
            $t->foreignId('recorded_by')->nullable()->constrained('users');
            $t->enum('source', ['manual', 'api', 'iot']);
            $t->timestamps();
        });
        Schema::create('tool_registrations', function ($t) {
            $t->id(); $t->uuid('uuid');
            $t->string('code', 50)->unique();
            $t->string('name');
            $t->string('type', 50);
            $t->foreignId('plant_id')->constrained();
            $t->decimal('life_limit', 12, 2)->nullable();
            $t->decimal('current_life_used', 12, 2)->default(0);
            $t->enum('status', ['active', 'worn_out', 'retired'])->default('active');
            $t->timestamps();
        });
        Schema::create('oee_records', function ($t) {
            $t->id();
            $t->foreignId('work_center_id')->constrained();
            $t->date('record_date');
            $t->decimal('available_hours', 8, 2);
            $t->decimal('operating_hours', 8, 2);
            $t->decimal('downtime_hours', 8, 2)->default(0);
            $t->decimal('ideal_cycle_time', 8, 4)->nullable();
            $t->integer('total_count')->default(0);
            $t->integer('good_count')->default(0);
            $t->decimal('availability', 5, 2)->nullable();
            $t->decimal('performance', 5, 2)->nullable();
            $t->decimal('quality', 5, 2)->nullable();
            $t->decimal('oee', 5, 2)->nullable();
            $t->timestamps();
        });
    }
    public function down(): void {
    }
};