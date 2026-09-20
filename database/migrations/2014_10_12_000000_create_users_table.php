<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_type_id')->index();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->string('name');
            $table->string('email');
            $table->string('cell_phone')->nullable();
            $table->decimal('liquidity', 16, 8)->default(0);
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->foreign('user_type_id')->references('id')->on('user_types')->onDelete('cascade');
            $table->foreign('company_id')->references('id')->on('users')->onDelete('cascade');
            $table->tinyInteger('status_id')->default(1);
            $table->unsignedBigInteger('mysql2_user_id')->nullable();
            $table->unsignedBigInteger('mysql_2_merchant_id')->nullable();
            $table->softDeletes();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('users');
    }
};
