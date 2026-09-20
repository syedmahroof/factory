<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('number', 30)->unique();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('production_version_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('bom_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('routing_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('plant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('type', ['planned', 'make_to_stock', 'make_to_order', 'engineer_to_order', 'assemble_to_order', 'subcontract', 'rework'])->default('planned');
            $table->decimal('planned_quantity', 15, 4);
            $table->decimal('actual_quantity', 15, 4)->default(0);
            $table->decimal('scrap_quantity', 15, 4)->default(0);
            $table->foreignId('uom_id')->constrained('uoms');
            $table->decimal('priority', 5, 1)->default(5);
            $table->date('planned_start_date');
            $table->date('planned_end_date');
            $table->date('actual_start_date')->nullable();
            $table->date('actual_end_date')->nullable();
            $table->foreignId('production_order_id')->nullable()->constrained()->nullOnDelete(); // parent for rework
            $table->enum('status', ['planned', 'approved', 'released', 'in_progress', 'technically_complete', 'costed', 'closed', 'cancelled'])->default('planned');
            $table->decimal('estimated_cost', 15, 4)->default(0);
            $table->decimal('actual_cost', 15, 4)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('operation_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('operation_id')->constrained()->cascadeOnDelete();
            $table->integer('sequence');
            $table->foreignId('work_center_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('planned_setup_time', 10, 2)->default(0);
            $table->decimal('planned_run_time', 10, 2)->default(0);
            $table->decimal('actual_setup_time', 10, 2)->default(0);
            $table->decimal('actual_run_time', 10, 2)->default(0);
            $table->decimal('quantity_input', 15, 4)->default(0);
            $table->decimal('quantity_output', 15, 4)->default(0);
            $table->decimal('quantity_scrap', 15, 4)->default(0);
            $table->enum('status', ['pending', 'in_progress', 'paused', 'completed', 'on_hold'])->default('pending');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('material_issues', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('production_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bom_line_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bin_id')->nullable()->constrained('bins')->nullOnDelete();
            $table->decimal('required_quantity', 15, 4);
            $table->decimal('issued_quantity', 15, 4)->default(0);
            $table->foreignId('uom_id')->constrained('uoms');
            $table->foreignId('lot_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('serial_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('status', ['reserved', 'staged', 'issued', 'returned'])->default('reserved');
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('issued_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('time_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('operation_job_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('employee_id')->constrained('users');
            $table->foreignId('work_center_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shift_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('type', ['direct', 'indirect', 'setup', 'idle', 'overtime'])->default('direct');
            $table->decimal('hours', 6, 2);
            $table->date('date');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('production_outputs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('production_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('operation_job_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 15, 4);
            $table->decimal('good_quantity', 15, 4)->default(0);
            $table->decimal('scrap_quantity', 15, 4)->default(0);
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bin_id')->nullable()->constrained('bins')->nullOnDelete();
            $table->foreignId('lot_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('stock_status_id')->constrained();
            $table->date('date');
            $table->foreignId('recorded_by')->constrained('users');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('process_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('operation_job_id')->nullable()->constrained()->nullOnDelete();
            $table->string('parameter_name');
            $table->string('uom', 20);
            $table->decimal('target_value', 15, 4)->nullable();
            $table->decimal('actual_value', 15, 4)->nullable();
            $table->decimal('lower_limit', 15, 4)->nullable();
            $table->decimal('upper_limit', 15, 4)->nullable();
            $table->boolean('is_within_spec')->nullable();
            $table->timestamp('recorded_at');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('rework_orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('production_order_id')->constrained()->cascadeOnDelete();
            $table->string('number', 30)->unique();
            $table->decimal('quantity', 15, 4);
            $table->text('defect_description');
            $table->foreignId('rework_routing_id')->nullable()->constrained('routings')->nullOnDelete();
            $table->enum('status', ['open', 'in_progress', 'completed', 'cancelled'])->default('open');
            $table->decimal('estimated_cost', 15, 4)->default(0);
            $table->decimal('actual_cost', 15, 4)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('shift_handovers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shift_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->foreignId('outgoing_user_id')->constrained('users');
            $table->foreignId('incoming_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('open_jobs')->nullable();
            $table->text('issues')->nullable();
            $table->text('shortages')->nullable();
            $table->text('downtime')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('acknowledged')->default(false);
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_handovers');
        Schema::dropIfExists('rework_orders');
        Schema::dropIfExists('process_readings');
        Schema::dropIfExists('production_outputs');
        Schema::dropIfExists('time_entries');
        Schema::dropIfExists('material_issues');
        Schema::dropIfExists('operation_jobs');
        Schema::dropIfExists('production_orders');
    }
};
