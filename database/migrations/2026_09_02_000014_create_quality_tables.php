<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quality_plans', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('code', 20);
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['incoming', 'in_process', 'final', 'periodic'])->default('incoming');
            $table->foreignId('item_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->foreignId('operation_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('frequency_days')->default(1);
            $table->decimal('sample_percentage', 5, 2)->nullable();
            $table->integer('min_sample_size')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('quality_characteristics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quality_plan_id')->constrained()->cascadeOnDelete();
            $table->string('code', 20);
            $table->string('name');
            $table->string('method', 50);
            $table->string('instrument')->nullable();
            $table->enum('data_type', ['numeric', 'text', 'boolean', 'pass_fail']);
            $table->decimal('target_value', 15, 4)->nullable();
            $table->decimal('lower_limit', 15, 4)->nullable();
            $table->decimal('upper_limit', 15, 4)->nullable();
            $table->enum('severity', ['critical', 'major', 'minor'])->default('major');
            $table->boolean('is_mandatory')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('inspections', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('number', 30)->unique();
            $table->foreignId('quality_plan_id')->constrained()->cascadeOnDelete();
            $table->string('source_type'); // goods_receipt, production_order, operation_job, finished_goods
            $table->unsignedBigInteger('source_id');
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lot_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('serial_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('quantity_inspected', 15, 4);
            $table->decimal('quantity_accepted', 15, 4)->default(0);
            $table->decimal('quantity_rejected', 15, 4)->default(0);
            $table->decimal('quantity_on_hold', 15, 4)->default(0);
            $table->foreignId('inspector_id')->constrained('users');
            $table->date('inspection_date');
            $table->enum('result', ['pending', 'accepted', 'conditional', 'rejected', 'on_hold'])->default('pending');
            $table->text('notes')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('inspection_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inspection_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quality_characteristic_id')->constrained()->cascadeOnDelete();
            $table->string('actual_value')->nullable();
            $table->decimal('numeric_value', 15, 4)->nullable();
            $table->boolean('is_within_spec')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->constrained('users');
            $table->timestamp('recorded_at');
            $table->timestamps();
        });

        Schema::create('ncrs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('number', 30)->unique();
            $table->foreignId('inspection_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('item_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('lot_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('severity', ['critical', 'major', 'minor', 'cosmetic']);
            $table->enum('source', ['incoming', 'in_process', 'final', 'customer_complaint', 'internal_audit']);
            $table->text('defect_description');
            $table->decimal('affected_quantity', 15, 4)->default(0);
            $table->foreignId('reported_by')->constrained('users');
            $table->date('reported_date');
            $table->text('containment_actions')->nullable();
            $table->enum('disposition', ['pending', 'use_as_is', 'rework', 'return_to_supplier', 'scrap', 'concession'])->default('pending');
            $table->enum('status', ['open', 'contained', 'investigating', 'action_in_progress', 'effectiveness_review', 'closed'])->default('open');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->date('target_close_date')->nullable();
            $table->date('actual_close_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('capas', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('number', 30)->unique();
            $table->foreignId('ncr_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('type', ['corrective', 'preventive']);
            $table->text('root_cause');
            $table->text('action_description');
            $table->foreignId('assigned_to')->constrained('users');
            $table->date('due_date');
            $table->text('effectiveness_criteria')->nullable();
            $table->text('effectiveness_result')->nullable();
            $table->enum('status', ['open', 'in_progress', 'effectiveness_review', 'closed', 'overdue'])->default('open');
            $table->date('actual_close_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('calibrations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('asset_code', 30);
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('manufacturer')->nullable();
            $table->string('model')->nullable();
            $table->string('serial_number')->nullable();
            $table->foreignId('location_warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->date('last_calibration_date')->nullable();
            $table->date('next_calibration_date')->nullable();
            $table->string('calibration_frequency', 20)->default('annual');
            $table->string('certificate_number')->nullable();
            $table->enum('status', ['active', 'due', 'overdue', 'out_of_service', 'decommissioned'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('complaints', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('number', 30)->unique();
            $table->foreignId('customer_id')->constrained('customers');
            $table->foreignId('item_id')->nullable()->constrained()->nullOnDelete();
            $table->text('description');
            $table->enum('severity', ['critical', 'major', 'minor']);
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->date('received_date');
            $table->enum('status', ['open', 'investigating', 'resolved', 'closed'])->default('open');
            $table->text('resolution')->nullable();
            $table->boolean('recall_required')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('recalls', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('number', 30)->unique();
            $table->foreignId('complaint_id')->nullable()->constrained()->nullOnDelete();
            $table->text('reason');
            $table->text('affected_lots')->nullable();
            $table->text('affected_customers')->nullable();
            $table->text('remaining_stock')->nullable();
            $table->text('communication_log')->nullable();
            $table->enum('status', ['planned', 'in_progress', 'completed', 'cancelled'])->default('planned');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recalls');
        Schema::dropIfExists('complaints');
        Schema::dropIfExists('calibrations');
        Schema::dropIfExists('capas');
        Schema::dropIfExists('ncrs');
        Schema::dropIfExists('inspection_results');
        Schema::dropIfExists('inspections');
        Schema::dropIfExists('quality_characteristics');
        Schema::dropIfExists('quality_plans');
    }
};
