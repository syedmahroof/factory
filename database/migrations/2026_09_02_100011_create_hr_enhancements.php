<?php
use Illuminate\Database\Migrations\Migration;
return new class extends Migration {
    public function up(): void {
        Schema::create('shift_rosters', function ($t) {
            $t->id();
            $t->foreignId('shift_id')->constrained();
            $t->foreignId('employee_id')->constrained();
            $t->date('roster_date');
            $t->time('expected_start')->nullable();
            $t->time('expected_end')->nullable();
            $t->enum('status', ['assigned', 'confirmed', 'absent', 'swap'])->default('assigned');
            $t->timestamps();
        });
        Schema::create('skill_matrix', function ($t) {
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
        Schema::create('time_bookings', function ($t) {
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
        Schema::create('safety_incidents', function ($t) {
            $t->id(); $t->uuid('uuid');
            $t->string('number', 30)->unique();
            $t->enum('type', ['incident', 'near_miss', 'first_aid', 'lost_time']);
            $t->foreignId('reported_by')->constrained('users');
            $t->foreignId('employee_id')->nullable()->constrained();
            $t->foreignId('asset_id')->nullable()->constrained();
            $t->date('incident_date');
            $t->text('description');
            $t->text('root_cause')->nullable();
            $t->text('corrective_action')->nullable();
            $t->enum('status', ['reported', 'investigating', 'resolved', 'closed'])->default('reported');
            $t->timestamps();
        });
        Schema::create('permits_to_work', function ($t) {
            $t->id(); $t->uuid('uuid');
            $t->string('number', 30)->unique();
            $t->foreignId('maintenance_order_id')->nullable()->constrained();
            $t->foreignId('issued_by')->constrained('users');
            $t->foreignId('worker_id')->nullable()->constrained('users');
            $t->string('permit_type');
            $t->text('hazards_identified')->nullable();
            $t->text('safety_measures')->nullable();
            $t->datetime('valid_from');
            $t->datetime('valid_until');
            $t->enum('status', ['pending', 'active', 'expired', 'cancelled'])->default('pending');
            $t->timestamps();
        });
        Schema::create('payroll_runs', function ($t) {
            $t->id(); $t->uuid('uuid');
            $t->string('number', 30)->unique();
            $t->string('period', 20);
            $t->foreignId('prepared_by')->constrained('users');
            $t->decimal('total_gross', 14, 2)->default(0);
            $t->decimal('total_deductions', 14, 2)->default(0);
            $t->decimal('total_net', 14, 2)->default(0);
            $t->enum('status', ['draft', 'submitted', 'approved', 'paid', 'posted'])->default('draft');
            $t->timestamps();
        });
        Schema::create('payslips', function ($t) {
            $t->id();
            $t->foreignId('payroll_run_id')->constrained('payroll_runs');
            $t->foreignId('employee_id')->constrained();
            $t->decimal('basic_salary', 12, 2);
            $t->decimal('overtime_pay', 12, 2)->default(0);
            $t->decimal('allowances', 12, 2)->default(0);
            $t->decimal('bonus', 12, 2)->default(0);
            $t->decimal('gross_pay', 12, 2);
            $t->decimal('tax', 12, 2)->default(0);
            $t->decimal('social_security', 12, 2)->default(0);
            $t->decimal('other_deductions', 12, 2)->default(0);
            $t->decimal('net_pay', 12, 2);
            $t->timestamps();
        });
    }
    public function down(): void {
    }
};