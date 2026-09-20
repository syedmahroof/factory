<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The CRM's private request log is superseded by api_logs, which records the
 * same traffic alongside every other integration's.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('crm_request_logs');
    }

    public function down(): void
    {
        Schema::create('crm_request_logs', function (Blueprint $table) {
            $table->id();
            $table->string('type')->index();
            $table->json('payload')->nullable();
            $table->timestamps();
        });
    }
};
