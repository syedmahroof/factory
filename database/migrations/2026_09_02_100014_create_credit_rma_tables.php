<?php
use Illuminate\Database\Migrations\Migration;
return new class extends Migration {
    public function up(): void {
        Schema::create('customer_credit_exposures', function ($t) {
            $t->id();
            $t->foreignId('customer_id')->constrained();
            $t->decimal('credit_limit', 14, 2)->default(0);
            $t->decimal('outstanding_balance', 14, 2)->default(0);
            $t->decimal('overdue_balance', 14, 2)->default(0);
            $t->decimal('available_credit', 14, 2)->default(0);
            $t->enum('credit_status', ['normal', 'watch', 'hold', 'blocked'])->default('normal');
            $t->timestamps();
        });
        Schema::create('return_merchandise_authorizations', function ($t) {
            $t->id(); $t->uuid('uuid');
            $t->string('number', 30)->unique();
            $t->foreignId('customer_id')->constrained();
            $t->foreignId('sales_order_id')->nullable()->constrained();
            $t->foreignId('created_by')->constrained('users');
            $t->date('rma_date');
            $t->text('reason');
            $t->enum('status', ['requested', 'approved', 'received', 'inspected', 'resolved', 'rejected'])->default('requested');
            $t->timestamps();
        });
        Schema::create('return_merchandise_lines', function ($t) {
            $t->id();
            $t->foreignId('rma_id')->constrained('return_merchandise_authorizations')->cascadeOnDelete();
            $t->foreignId('item_id')->constrained();
            $t->decimal('quantity', 12, 4);
            $t->foreignId('uom_id')->constrained('uoms');
            $t->string('batch_number', 50)->nullable();
            $t->string('serial_number', 50)->nullable();
            $t->enum('disposition', ['restock', 'rework', 'scrap', 'credit_only'])->nullable();
            $t->decimal('refund_amount', 14, 2)->nullable();
            $t->timestamps();
        });
    }
    public function down(): void {
    }
};