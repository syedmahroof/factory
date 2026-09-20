<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Paying syndicates what was collected from merchants on their behalf.
 *
 * A run names a collection window and pays each syndicate the net of their
 * splits inside it. One row here per syndicate per run — the header is the
 * `batch_no` they share.
 *
 * The stamp on merchant_payment_investors is what makes a run safe to repeat.
 * A window alone is not enough: two overlapping ranges would pay the same
 * collection twice, and a collection posted late would be missed by a window
 * that has already been run. Claiming the split rows themselves means a
 * collection is paid exactly once, whatever windows are chosen.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('syndication_payments', function (Blueprint $table) {
            $table->id();
            // Shared by every row of one run, so a run reads as one thing.
            $table->string('batch_no', 64)->index();
            $table->unsignedBigInteger('investor_id')->index();
            $table->date('from_date');
            $table->date('to_date');

            // What the splits in the window came to, before this pays it out.
            $table->decimal('collected', 20, 8)->default(0);
            $table->decimal('fees', 20, 8)->default(0);
            // Net of fees — the figure actually sent to the bank.
            $table->decimal('amount', 20, 8)->default(0);
            $table->unsignedInteger('splits')->default(0);

            // The ACH credit this became, once it was originated.
            $table->unsignedBigInteger('actum_request_id')->nullable()->index();
            $table->boolean('same_day')->default(false);
            // pending -> sent | failed. The gateway's own outcome lives on the
            // actum_requests row; this only records whether we got that far.
            $table->string('status', 16)->default('pending')->index();
            $table->text('error')->nullable();

            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('merchant_payment_investors', function (Blueprint $table) {
            // Which payout covered this split. Null means undistributed, which is
            // what a run looks for.
            $table->unsignedBigInteger('syndication_payment_id')->nullable()->after('merchant_payment_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('merchant_payment_investors', function (Blueprint $table) {
            $table->dropColumn('syndication_payment_id');
        });

        Schema::dropIfExists('syndication_payments');
    }
};
