<?php

namespace App\Actions\Lender;

use App\Actions\ActionResult;
use App\Models\Lender;
use App\Models\User;
use App\Models\UserType;
use Throwable;

/**
 * Dropdown data for the lender filters and the create/edit form.
 */
class LenderOptionsAction
{
    public function handle(): ActionResult
    {
        try {
            $data = [
                'statuses' => $this->pairs(User::statusOptions()),
                'syndication_fee_types' => $this->pairs(Lender::syndicationFeeTypeOptions()),
                'yes_no' => [
                    ['id' => 0, 'name' => 'No'],
                    ['id' => 1, 'name' => 'Yes'],
                ],
                'companies' => User::where('user_type_id', UserType::Company)
                    ->orderBy('name')
                    ->get(['id', 'name']),
            ];

            return ActionResult::success($data);
        } catch (Throwable $e) {
            report($e);

            return ActionResult::failure('Unable to load form options.', 500);
        }
    }

    private function pairs(array $options): array
    {
        return collect($options)
            ->map(fn ($name, $id) => ['id' => $id, 'name' => $name])
            ->values()
            ->all();
    }
}
