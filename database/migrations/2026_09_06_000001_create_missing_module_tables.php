<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The six tables their own migrations never created.
 *
 * `bank_accounts`, `payroll_runs`, `time_bookings`, `skill_matrix`, `capas` and
 * `calibrations` are each defined inside a migration that is already recorded as
 * run — the definitions were added to those files afterwards, so `migrate` had
 * nothing left to do and the tables never appeared. Every register behind them
 * answered 500 ("Base table or view not found").
 *
 * Rather than rewind and re-run migrations that other tables now depend on, this
 * one creates exactly what is missing, with the definitions copied from those
 * files unchanged. `hasTable` guards each one so this is a no-op on any database
 * where the original migration did land.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bank_accounts')) {
            Schema::create('bank_accounts', function (Blueprint $t) {
                $t->id();
                $t->foreignId('account_id')->constrained('accounts');
                $t->string('bank_name');
                $t->string('account_number', 50);
                $t->string('routing_number', 20)->nullable();
                $t->string('swift_code', 20)->nullable();
                $t->string('currency', 10)->default('USD');
                $t->decimal('balance', 14, 2)->default(0);
                $t->boolean('is_active')->default(true);
                $t->timestamps();
            });
        }

        if (! Schema::hasTable('payroll_runs')) {
            Schema::create('payroll_runs', function (Blueprint $t) {
                $t->id();
                $t->uuid('uuid');
                $t->string('number', 30)->unique();
                $t->string('period', 20);
                $t->foreignId('prepared_by')->constrained('users');
                $t->decimal('total_gross', 14, 2)->default(0);
                $t->decimal('total_deductions', 14, 2)->default(0);
                $t->decimal('total_net', 14, 2)->default(0);
                $t->enum('status', ['draft', 'submitted', 'approved', 'paid', 'posted'])->default('draft');
                $t->timestamps();
            });
        }

        if (! Schema::hasTable('time_bookings')) {
            Schema::create('time_bookings', function (Blueprint $t) {
                $t->id();
                $t->foreignId('employee_id')->constrained();
                $t->foreignId('production_order_id')->nullable()->constrained();
                $t->foreignId('operation_job_id')->nullable();
                $t->foreignId('work_center_id')->nullable()->constrained();
                $t->date('booking_date');
                $t->time('start_time');
                $t->time('end_time')->nullable();
                $t->decimal('hours', 6, 2);
                $t->enum('type', ['direct', 'indirect', 'setup', 'idle']);
                $t->text('notes')->nullable();
                $t->timestamps();
            });
        }

        if (! Schema::hasTable('skill_matrix')) {
            Schema::create('skill_matrix', function (Blueprint $t) {
                $t->id();
                $t->foreignId('employee_id')->constrained();
                $t->foreignId('operation_id')->nullable();
                $t->foreignId('work_center_id')->nullable()->constrained();
                $t->string('skill_name');
                $t->enum('level', ['trainee', 'beginner', 'intermediate', 'advanced', 'expert']);
                $t->date('certification_date')->nullable();
                $t->date('certification_expiry')->nullable();
                $t->boolean('is_certified')->default(false);
                $t->timestamps();
            });
        }

        if (! Schema::hasTable('capas')) {
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
        }

        if (! Schema::hasTable('calibrations')) {
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
        }
    }

    public function down(): void
    {
        // Dropped in reverse dependency order.
        foreach (['calibrations', 'capas', 'skill_matrix', 'time_bookings', 'payroll_runs', 'bank_accounts'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
