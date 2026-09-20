<?php
namespace App\Actions\Organization;

use App\Models\Company;
use App\Services\AuditService;
use Illuminate\Support\Str;

class CreateCompany
{
    public function execute(array $data): Company
    {
        $data['uuid'] = Str::uuid();
        $company = Company::create($data);

        AuditService::log('created', 'organization', $company, null, $data);

        return $company;
    }
}