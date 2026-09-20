<?php

namespace App\Actions\Lender;

use App\Actions\ActionResult;
use App\Http\Resources\Lender\LenderProfileResource;
use App\Models\Lender;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Throwable;

class UpdateLenderAction
{
    /**
     * Update a lender. The `lenders` row is created on the way through when the
     * lender predates the table.
     *
     * @param  array  $attributes  the flat form payload (user columns + profile columns)
     * @param  User  $actor  who is making the change (created_by/updated_by)
     */
    public function handle(User $lender, array $attributes, User $actor): ActionResult
    {
        try {
            $data = [
                'name' => $attributes['name'],
                'email' => $attributes['email'],
                'cell_phone' => $attributes['cell_phone'] ?? null,
                'company_id' => $attributes['company_id'] ?? null,
                'status_id' => ($attributes['status_id'] ?? null) ?: $lender->status_id,
            ];

            if (filled($attributes['password'] ?? null)) {
                $data['password'] = Hash::make($attributes['password']);
            }

            $lenderData = [
                'username' => $attributes['username'] ?? null,
                'notification_email' => $attributes['notification_email'] ?? null,
                'management_fee_percentage' => $attributes['management_fee_percentage'] ?? 0,
                'up_sell_management_fee_percentage' => $attributes['up_sell_management_fee_percentage'] ?? 0,
                'underwriting_fee_percentage' => $attributes['underwriting_fee_percentage'] ?? 0,
                'syndication_fee_type' => $attributes['syndication_fee_type'] ?? Lender::SyndicationFeeNone,
                'syndication_fee_percentage' => $attributes['syndication_fee_percentage'] ?? 0,
                'up_sell_syndication_fee_percentage' => $attributes['up_sell_syndication_fee_percentage'] ?? 0,
                'lag_time_days' => $attributes['lag_time_days'] ?? 0,
                'ip_filtering' => (bool) ($attributes['ip_filtering'] ?? false),
                'created_by' => $lender->Lender?->created_by ?? $actor->id,
                'updated_by' => $actor->id,
            ];

            $user = DB::transaction(function () use ($lender, $data, $lenderData) {
                $lender->update($data);

                $profile = Lender::updateOrCreate(
                    [
                        'user_id' => $lender->id,
                    ],
                    $lenderData
                );

                $lender->setRelation('Lender', $profile);

                return $lender;
            });

            return ActionResult::success(new LenderProfileResource($user), 'Lender updated successfully.');
        } catch (Throwable $e) {
            report($e);

            return ActionResult::failure('Unable to update lender.', 500);
        }
    }
}
