<?php
namespace App\Actions\Hr;

use App\Models\Employee;
use App\Services\AuditService;

class CreateEmployee
{
    public function execute(array $data): Employee
    {
        $employee = Employee::create($data);
        AuditService::log('created', 'hr', $employee, null, $data);
        return $employee;
    }
}