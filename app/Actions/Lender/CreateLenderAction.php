<?php

namespace App\Actions\Lender;

use App\Actions\ActionResult;
use App\Http\Resources\Lender\LenderProfileResource;
use App\Models\Lender;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Throwable;

class CreateLenderAction
{
    /**
     * Create a lender: the `users` row and its `lenders` profile row, in one
     * transaction.
     *
     * @param  array  $attributes  the flat form payload (user columns + profile columns)
     * @param  User  $actor  who is making the change (created_by/updated_by)
     */
    public function handle(array $attributes, User $actor): ActionResult
    {
        try {
            $data = [
                'user_type_id' => UserType::Lender,
                'company_id' => $attributes['company_id'] ?? null,
                'status_id' => ($attributes['status_id'] ?? null) ?: User::Active,
                'name' => $attributes['name'],
                'email' => $attributes['email'],
                'cell_phone' => $attributes['cell_phone'] ?? null,
                'password' => Hash::make(
                    filled($attributes['password'] ?? null) ? $attributes['password'] : Str::password(32)
                ),
            ];
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
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ];

            $user = DB::transaction(function () use ($data, $lenderData) {
                $user = User::create($data);

                $lenderData['user_id'] = $user->id;
                $lender = Lender::create($lenderData);

                $user->setRelation('Lender', $lender);

                return $user;
            });

            return ActionResult::success(new LenderProfileResource($user), 'Lender created successfully.');
        } catch (Throwable $e) {
            report($e);

            return ActionResult::failure('Unable to create lender.', 500);
        }
    }
}
