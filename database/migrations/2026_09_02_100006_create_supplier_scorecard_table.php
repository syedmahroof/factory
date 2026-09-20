<?php
use Illuminate\Database\Migrations\Migration;
return new class extends Migration {
    public function up(): void {
        Schema::create('supplier_scorecards', function ($t) {
            $t->id();
            $t->foreignId('supplier_id')->constrained();
            $t->string('period', 20);
            $t->integer('total_orders')->default(0);
            $t->integer('on_time_orders')->default(0);
            $t->decimal('otif_percentage', 5, 2)->nullable();
            $t->integer('total_items_received')->default(0);
            $t->integer('rejected_items')->default(0);
            $t->decimal('rejection_rate', 5, 2)->nullable();
            $t->decimal('avg_price_variance', 5, 2)->nullable();
            $t->decimal('avg_response_time_days', 5, 1)->nullable();
            $t->decimal('overall_score', 5, 2)->nullable();
            $t->timestamps();
        });
    }
    public function down(): void { }
};