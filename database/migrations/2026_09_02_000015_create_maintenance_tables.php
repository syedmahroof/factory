<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code', 30)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['machine', 'line', 'utility', 'tool', 'facility', 'vehicle', 'it_equipment']);
            $table->foreignId('parent_id')->nullable()->constrained('assets')->nullOnDelete();
            $table->foreignId('work_center_id')->nullable()->constrained()->nullOnDelete();
            $table->string('manufacturer')->nullable();
            $table->string('model')->nullable();
            $table->string('serial_number')->nullable();
            $table->date('purchase_date')->nullable();
            $table->date('installation_date')->nullable();
            $table->decimal('purchase_cost', 15, 4)->default(0);
            $table->decimal('current_value', 15, 4)->default(0);
            $table->date('warranty_expiry')->nullable();
            $table->foreignId('location_warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->enum('criticality', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->enum('status', ['active', 'inactive', 'under_maintenance', 'decommissioned'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('asset_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->string('document_type', 30); // manual, drawing, certificate, photo
            $table->string('name');
            $table->string('file_path');
            $table->string('revision', 10)->default('1');
            $table->boolean('is_current')->default(true);
            $table->timestamps();
        });

        Schema::create('maintenance_plans', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['calendar', 'meter', 'condition']);
            $table->integer('interval_days')->nullable();
            $table->decimal('meter_threshold', 15, 4)->nullable();
            $table->string('meter_name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('maintenance_plan_checklist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maintenance_plan_id')->constrained()->cascadeOnDelete();
            $table->integer('sequence');
            $table->string('description');
            $table->enum('type', ['check', 'action', 'measurement']);
            $table->string('expected_value')->nullable();
            $table->timestamps();
        });

        Schema::create('maintenance_orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('number', 30)->unique();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->foreignId('maintenance_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('type', ['preventive', 'breakdown', 'predictive', 'improvement']);
            $table->enum('priority', ['low', 'normal', 'high', 'emergency'])->default('normal');
            $table->text('description');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('requested_by')->constrained('users');
            $table->date('requested_date');
            $table->date('scheduled_date')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->decimal('estimated_hours', 6, 2)->nullable();
            $table->decimal('actual_hours', 6, 2)->default(0);
            $table->decimal('estimated_cost', 15, 4)->default(0);
            $table->decimal('actual_cost', 15, 4)->default(0);
            $table->text('findings')->nullable();
            $table->text('corrective_actions')->nullable();
            $table->boolean('lockout_tagout')->default(false);
            $table->enum('status', ['requested', 'approved', 'planned', 'scheduled', 'in_progress', 'verification', 'closed', 'cancelled'])->default('requested');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('maintenance_spare_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maintenance_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 15, 4);
            $table->decimal('unit_cost', 15, 4)->default(0);
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('issued_at')->nullable();
            $table->boolean('is_returned')->default(false);
            $table->timestamps();
        });

        Schema::create('downtime_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->foreignId('production_order_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('type', ['planned', 'unplanned']);
            $table->string('reason_category', 50); // breakdown, setup, material_wait, operator_absent, quality, etc.
            $table->text('description')->nullable();
            $table->timestamp('start_time');
            $table->timestamp('end_time')->nullable();
            $table->decimal('duration_minutes', 10, 2)->nullable();
            $table->foreignId('reported_by')->constrained('users');
            $table->foreignId('maintenance_order_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('meters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('unit', 20);
            $table->decimal('current_reading', 15, 4)->default(0);
            $table->decimal('min_threshold', 15, 4)->nullable();
            $table->decimal('max_threshold', 15, 4)->nullable();
            $table->timestamps();
        });

        Schema::create('meter_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meter_id')->constrained()->cascadeOnDelete();
            $table->decimal('reading', 15, 4);
            $table->date('reading_date');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('source', ['manual', 'api', 'iot']);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('tools', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code', 30)->unique();
            $table->string('name');
            $table->enum('type', ['die', 'mold', 'jig', 'gauge', 'fixture', 'other']);
            $table->integer('life_counter')->default(0);
            $table->integer('max_life')->nullable();
            $table->foreignId('location_warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->date('last_maintenance_date')->nullable();
            $table->date('next_maintenance_date')->nullable();
            $table->enum('status', ['available', 'in_use', 'under_maintenance', 'retired'])->default('available');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tools');
        Schema::dropIfExists('meter_readings');
        Schema::dropIfExists('meters');
        Schema::dropIfExists('downtime_events');
        Schema::dropIfExists('maintenance_spare_issues');
        Schema::dropIfExists('maintenance_orders');
        Schema::dropIfExists('maintenance_plan_checklist_items');
        Schema::dropIfExists('maintenance_plans');
        Schema::dropIfExists('asset_documents');
        Schema::dropIfExists('assets');
    }
};
