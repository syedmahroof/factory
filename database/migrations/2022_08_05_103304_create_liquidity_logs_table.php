<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('liquidity_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('merchant_id')->nullable()->index();
            $table->unsignedBigInteger('investor_id')->index();
            $table->unsignedBigInteger('company_id')->index();
            $table->integer('batch_no')->nullable();
            $table->decimal('amount', 16, 8);
            $table->decimal('net_liquidity', 16, 8);
            $table->string('description')->nullable();
            $table->unsignedBigInteger('creator_id')->index();
            $table->foreign('merchant_id')->references('id')->on('users');
            $table->foreign('investor_id')->references('id')->on('users');
            $table->foreign('company_id')->references('id')->on('users');
            $table->foreign('creator_id')->references('id')->on('users');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('liquidity_logs');
    }
};
