<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `period_closes`, from the same already-run finance migration whose later edits
 * never reached the database. Definition copied unchanged; guarded so it is a
 * no-op wherever the original did land.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('period_closes')) {
            return;
        }

        Schema::create('period_closes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('fiscal_period_id')->constrained();
            $t->string('module', 50);
            $t->foreignId('closed_by')->constrained('users');
            $t->datetime('closed_at');
            $t->boolean('is_locked')->default(true);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('period_closes');
    }
};
