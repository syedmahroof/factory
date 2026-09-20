<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Which columns an operator has hidden on a given table, remembered per operator.
     * Keyed by a table_key string rather than a route, so a table that moves keeps its
     * saved layout, and stored as the hidden set rather than the visible one — a column
     * added to a table later then shows up for everyone instead of staying hidden for
     * whoever had already saved a preference.
     */
    public function up()
    {
        Schema::create('user_table_preferences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('table_key', 64);
            $table->json('hidden_columns')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['user_id', 'table_key']);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_table_preferences');
    }
};
