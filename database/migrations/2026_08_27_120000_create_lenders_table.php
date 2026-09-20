<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lenders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique()->index();
            $table->string('username')->nullable()->unique();
            $table->string('notification_email')->nullable();
            $table->decimal('management_fee_percentage', 5, 2)->default(0);
            $table->decimal('up_sell_management_fee_percentage', 5, 2)->default(0);
            $table->decimal('underwriting_fee_percentage', 5, 2)->default(0);
            $table->tinyInteger('syndication_fee_type')->default(0);
            $table->decimal('syndication_fee_percentage', 5, 2)->default(0);
            $table->decimal('up_sell_syndication_fee_percentage', 5, 2)->default(0);
            $table->unsignedSmallInteger('lag_time_days')->default(0);
            $table->boolean('ip_filtering')->default(false);
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by');
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lenders');
    }
};
