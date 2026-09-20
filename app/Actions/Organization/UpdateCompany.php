<?php
namespace App\Actions\Organization;

use App\Models\Company;
use App\Services\AuditService;

class UpdateCompany
{
    public function execute(Company $company, array $data): Company
    {
        $old = $company->toArray();
        $company->update($data);

        AuditService::log('updated', 'organization', $company, $old, $data);

        return $company;
    }
}