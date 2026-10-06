<?php
use Illuminate\Database\Migrations\Migration;
return new class extends Migration {
    public function up(): void {
        Schema::create('quality_gates', function ($t) {
            $t->id();
            $t->foreignId('quality_plan_id')->constrained();
            $t->foreignId('routing_id')->nullable()->constrained();
            $t->foreignId('operation_id')->nullable();
            $t->string('gate_name');
            $t->enum('inspection_type', ['incoming', 'in_process', 'final', 'audit']);
            $t->boolean('blocks_completion')->default(false);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('certificates_of_analysis', function ($t) {
            $t->id(); $t->uuid('uuid');
            $t->string('number', 30)->unique();
            $t->foreignId('inspection_id')->constrained();
            $t->foreignId('item_id')->constrained();
            $t->string('batch_number', 50)->nullable();
            $t->foreignId('issued_by')->constrained('users');
            $t->date('issue_date');
            $t->text('results_summary')->nullable();
            $t->enum('status', ['draft', 'issued', 'revoked'])->default('draft');
            $t->timestamps();
        });
        Schema::create('capa_actions', function ($t) {
            $t->id(); $t->uuid('uuid');
            $t->string('number', 30)->unique();
            $t->foreignId('ncr_id')->nullable()->constrained();
            $t->enum('type', ['corrective', 'preventive']);
            $t->text('root_cause');
            $t->text('action_description');
            $t->foreignId('owner_id')->constrained('users');
            $t->date('due_date');
            $t->date('completed_date')->nullable();
            $t->date('effectiveness_review_date')->nullable();
            $t->text('effectiveness_result')->nullable();
            $t->enum('status', ['open', 'in_progress', 'completed', 'effective', 'closed', 'overdue'])->default('open');
            $t->timestamps();
        });
        Schema::create('calibration_records', function ($t) {
            $t->id(); $t->uuid('uuid');
            $t->string('instrument_code', 50);
            $t->string('instrument_name');
            $t->string('location')->nullable();
            $t->foreignId('plant_id')->constrained();
            $t->date('last_calibration_date');
            $t->date('next_calibration_date');
            $t->date('certificate_expiry')->nullable();
            $t->enum('status', ['calibrated', 'due', 'overdue', 'out_of_service'])->default('calibrated');
            $t->timestamps();
        });
        if (! Schema::hasTable('complaints')) {
            Schema::create('complaints', function ($t) {
                $t->id(); $t->uuid('uuid');
                $t->string('number', 30)->unique();
                $t->foreignId('customer_id')->nullable();
                $t->foreignId('item_id')->nullable()->constrained();
                $t->text('description');
                $t->text('investigation')->nullable();
                $t->text('corrective_action')->nullable();
                $t->foreignId('handled_by')->nullable()->constrained('users');
                $t->enum('severity', ['low', 'medium', 'high', 'critical']);
                $t->enum('status', ['open', 'investigating', 'resolved', 'closed'])->default('open');
                $t->timestamps();
            });
        }
        if (! Schema::hasTable('recalls')) {
            Schema::create('recalls', function ($t) {
                $t->id(); $t->uuid('uuid');
                $t->string('number', 30)->unique();
                $t->text('reason');
                $t->foreignId('item_id')->nullable()->constrained();
                $t->json('affected_batches')->nullable();
                $t->json('affected_customers')->nullable();
                $t->foreignId('initiated_by')->constrained('users');
                $t->enum('status', ['initiated', 'in_progress', 'completed'])->default('initiated');
                $t->timestamps();
            });
        }
    }
    public function down(): void {
    }
};