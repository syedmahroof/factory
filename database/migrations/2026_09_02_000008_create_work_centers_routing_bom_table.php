<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_centers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code', 20);
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('capacity_per_hour', 15, 4)->default(0);
            $table->decimal('capacity_per_day', 15, 4)->default(0);
            $table->decimal('cost_per_hour', 15, 4)->default(0);
            $table->decimal('setup_cost', 15, 4)->default(0);
            $table->foreignId('cost_center_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('type', ['machine', 'labor', 'both'])->default('machine');
            $table->boolean('is_bottleneck')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('routings', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('code', 20);
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('revision')->default(1);
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->enum('status', ['draft', 'approved', 'obsolete'])->default('draft');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('operations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('routing_id')->constrained()->cascadeOnDelete();
            $table->integer('sequence');
            $table->string('code', 20);
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('work_center_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('setup_time', 10, 2)->default(0);
            $table->decimal('run_time', 10, 2)->default(0);
            $table->decimal('queue_time', 10, 2)->default(0);
            $table->decimal('wait_time', 10, 2)->default(0);
            $table->integer('crew_size')->default(1);
            $table->decimal('scrap_factor', 5, 2)->default(0);
            $table->boolean('is_outside_process')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('operation_quality_gates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operation_id')->constrained()->cascadeOnDelete();
            $table->string('characteristic');
            $table->string('method');
            $table->decimal('lower_limit', 15, 4)->nullable();
            $table->decimal('upper_limit', 15, 4)->nullable();
            $table->string('target_value')->nullable();
            $table->enum('severity', ['critical', 'major', 'minor'])->default('major');
            $table->boolean('is_mandatory')->default(true);
            $table->timestamps();
        });

        Schema::create('boms', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('routing_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('revision')->default(1);
            $table->enum('type', ['normal', 'phantom', 'configurable', 'alternate'])->default('normal');
            $table->decimal('scrap_factor', 5, 2)->default(0);
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->enum('status', ['draft', 'approved', 'obsolete'])->default('draft');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('bom_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bom_id')->constrained()->cascadeOnDelete();
            $table->integer('sequence');
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 15, 6);
            $table->foreignId('uom_id')->constrained('uoms');
            $table->decimal('scrap_factor', 5, 2)->default(0);
            $table->decimal('cost', 15, 4)->default(0);
            $table->boolean('is_optional')->default(false);
            $table->boolean('is_co_product')->default(false);
            $table->boolean('is_by_product')->default(false);
            $table->decimal('yield_percentage', 5, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('production_versions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bom_id')->constrained()->cascadeOnDelete();
            $table->foreignId('routing_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('plant_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('min_lot_size', 15, 4)->nullable();
            $table->decimal('max_lot_size', 15, 4)->nullable();
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_versions');
        Schema::dropIfExists('bom_lines');
        Schema::dropIfExists('boms');
        Schema::dropIfExists('operation_quality_gates');
        Schema::dropIfExists('operations');
        Schema::dropIfExists('routings');
        Schema::dropIfExists('work_centers');
    }
};
