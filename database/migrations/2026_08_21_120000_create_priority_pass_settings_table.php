<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('priority_pass_settings', function (Blueprint $table) {
            $table->id();
            // users.id, like every other investor_id in the schema.
            $table->unsignedBigInteger('investor_id')->unique();
            // Cap as a share of the merchant's funded amount, per deal.
            $table->decimal('max_percentage', 8, 4)->default(0);
            // Hard dollar ceiling per deal; 0 means no dollar ceiling.
            $table->decimal('max_amount', 16, 2)->default(0);
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->foreign('investor_id')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('priority_pass_settings');
    }
};
