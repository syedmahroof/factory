<?php
use Illuminate\Database\Migrations\Migration;
return new class extends Migration {
    public function up(): void {
        Schema::create('engineering_changes', function ($t) {
            $t->id(); $t->uuid('uuid');
            $t->string('number', 30)->unique();
            $t->enum('type', ['ecr', 'eco']);
            $t->string('title');
            $t->text('reason')->nullable();
            $t->text('impact')->nullable();
            $t->string('entity_type')->nullable();
            $t->unsignedBigInteger('entity_id')->nullable();
            $t->foreignId('requested_by')->constrained('users');
            $t->foreignId('approved_by')->nullable()->constrained('users');
            $t->date('effective_date')->nullable();
            $t->enum('status', ['draft', 'submitted', 'approved', 'rejected', 'implemented', 'cancelled'])->default('draft');
            $t->timestamps();
        });
    }
    public function down(): void { }
};