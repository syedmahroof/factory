<?php

use App\Models\Settings;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The agent fee is one platform-wide rate, not a per-advance one.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            $table->decimal('agent_fee_percentage', 4, 2)->nullable()->default(null)->change();
            $table->decimal('management_fee_percentage', 4, 2)->nullable()->default(null)->change();
        });

        /*
         * Seed the setting from the rate the advances actually used, so the first
         * payment after this migration charges what the last one before it did.
         * The most frequent non-zero rate wins; an admin can correct it on the
         * settings screen.
         */
        if (! Settings::where('key', Settings::AgentFeePercentage)->exists()) {
            $common = DB::table('merchants')
                ->select('agent_fee_percentage', DB::raw('count(*) as total'))
                ->whereNotNull('agent_fee_percentage')
                ->where('agent_fee_percentage', '>', 0)
                ->groupBy('agent_fee_percentage')
                ->orderByDesc('total')
                ->value('agent_fee_percentage');

            Settings::create([
                'key' => Settings::AgentFeePercentage,
                'values' => number_format((float) $common, 2, '.', ''),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            $table->decimal('agent_fee_percentage', 4, 2)->default(0)->change();
            $table->decimal('management_fee_percentage', 4, 2)->default(0)->change();
        });

        Settings::where('key', Settings::AgentFeePercentage)->delete();
    }
};
