<?php
use Illuminate\Database\Migrations\Migration;
return new class extends Migration {
    public function up(): void {
        Schema::create('formulas', function ($t) {
            $t->id(); $t->uuid('uuid');
            $t->foreignId('item_id')->constrained()->cascadeOnDelete();
            $t->string('version', 20);
            $t->string('name')->nullable();
            $t->text('description')->nullable();
            $t->decimal('expected_yield', 10, 4)->default(1);
            $t->decimal('potency_factor', 8, 4)->default(1);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
            $t->unique(['item_id', 'version']);
        });
        Schema::create('formula_lines', function ($t) {
            $t->id();
            $t->foreignId('formula_id')->constrained()->cascadeOnDelete();
            $t->foreignId('item_id')->constrained();
            $t->decimal('quantity', 12, 4);
            $t->foreignId('uom_id')->constrained('uoms');
            $t->enum('type', ['input', 'co_product', 'by_product'])->default('input');
            $t->decimal('yield_percentage', 5, 2)->nullable();
            $t->integer('sequence')->default(0);
            $t->timestamps();
        });
    }
    public function down(): void { }
};