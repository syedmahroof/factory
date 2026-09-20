<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uoms', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('code', 20);
            $table->string('name');
            $table->string('type', 20)->default('primary');
            $table->decimal('base_conversion', 15, 6)->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('item_categories', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('code', 20);
            $table->string('name');
            $table->foreignId('parent_id')->nullable()->constrained('item_categories')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->string('description')->nullable();
            $table->enum('type', ['raw_material', 'packing', 'consumable', 'spare', 'semi_finished', 'finished', 'service', 'asset']);
            $table->foreignId('category_id')->nullable()->constrained('item_categories')->nullOnDelete();
            $table->foreignId('base_uom_id')->constrained('uoms');
            $table->foreignId('purchase_uom_id')->nullable()->constrained('uoms')->nullOnDelete();
            $table->foreignId('sales_uom_id')->nullable()->constrained('uoms')->nullOnDelete();
            $table->foreignId('production_uom_id')->nullable()->constrained('uoms')->nullOnDelete();
            $table->decimal('weight', 10, 4)->nullable();
            $table->string('weight_uom', 10)->nullable();
            $table->decimal('volume', 10, 4)->nullable();
            $table->string('volume_uom', 10)->nullable();
            $table->decimal('length', 10, 4)->nullable();
            $table->decimal('width', 10, 4)->nullable();
            $table->decimal('height', 10, 4)->nullable();
            $table->string('dimension_uom', 10)->nullable();
            $table->string('hsn_code', 20)->nullable();
            $table->string('barcode')->nullable();
            $table->decimal('standard_cost', 15, 4)->default(0);
            $table->decimal('selling_price', 15, 4)->default(0);
            $table->decimal('purchase_price', 15, 4)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->boolean('is_serialized')->default(false);
            $table->boolean('is_lot_tracked')->default(false);
            $table->boolean('is_batch_tracked')->default(false);
            $table->integer('shelf_life_days')->nullable();
            $table->integer('reorder_level')->nullable();
            $table->integer('reorder_quantity')->nullable();
            $table->decimal('minimum_stock', 15, 4)->default(0);
            $table->decimal('maximum_stock', 15, 4)->default(0);
            $table->decimal('safety_stock', 15, 4)->default(0);
            $table->decimal('lead_time_days', 5, 1)->default(0);
            $table->enum('valuation_method', ['fifo', 'lifo', 'moving_average', 'standard'])->default('fifo');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('uom_conversions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_uom_id')->constrained('uoms')->cascadeOnDelete();
            $table->foreignId('to_uom_id')->constrained('uoms')->cascadeOnDelete();
            $table->decimal('factor', 15, 6);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uom_conversions');
        Schema::dropIfExists('items');
        Schema::dropIfExists('item_categories');
        Schema::dropIfExists('uoms');
    }
};
