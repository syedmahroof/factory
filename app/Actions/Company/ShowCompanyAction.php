<?php

namespace App\Actions\Company;

use App\Actions\ActionResult;
use App\Helpers\Facades\InvestorHelper;
use App\Models\User;
use Throwable;

class ShowCompanyAction
{
    public function handle(User $company): ActionResult
    {
        try {
            $company->loadMissing('UserType');

            $data = [
                'company' => $company,
                'portfolio' => InvestorHelper::portofolioValues(['company_id' => $company->id]),
            ];

            return ActionResult::success($data);
        } catch (Throwable $e) {
            report($e);

            return ActionResult::failure('Unable to load the company.', 500);
        }
    }
}
