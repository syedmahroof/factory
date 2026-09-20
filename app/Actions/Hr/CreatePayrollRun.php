<?php
namespace App\Actions\Hr;

use App\Models\{PayrollRun, Payslip};
use App\Services\{NumberGenerator, AuditService};
use Illuminate\Support\Facades\DB;

class CreatePayrollRun
{
    public function execute(array $data): PayrollRun
    {
        return DB::transaction(function () use ($data) {
            $payslipsData = $data['payslips'] ?? [];
            unset($data['payslips']);
            $data['number'] = NumberGenerator::next('payroll_run', 'PAY');
            $data['status'] = 'draft';

            $run = PayrollRun::create($data);

            $totalGross = 0;
            $totalDeductions = 0;

            foreach ($payslipsData as $ps) {
                $basic = $ps['basic_salary'] ?? 0;
                $overtime = $ps['overtime_pay'] ?? 0;
                $allowances = $ps['allowances'] ?? 0;
                $bonus = $ps['bonus'] ?? 0;
                $gross = $basic + $overtime + $allowances + $bonus;

                $tax = $ps['tax'] ?? ($gross * 0.15);
                $ss = $ps['social_security'] ?? ($gross * 0.05);
                $otherDeductions = $ps['other_deductions'] ?? 0;
                $totalDed = $tax + $ss + $otherDeductions;
                $net = $gross - $totalDed;

                Payslip::create([
                    'payroll_run_id' => $run->id,
                    'employee_id' => $ps['employee_id'],
                    'basic_salary' => $basic,
                    'overtime_pay' => $overtime,
                    'allowances' => $allowances,
                    'bonus' => $bonus,
                    'gross_pay' => $gross,
                    'tax' => $tax,
                    'social_security' => $ss,
                    'other_deductions' => $otherDeductions,
                    'net_pay' => $net,
                ]);

                $totalGross += $gross;
                $totalDeductions += $totalDed;
            }

            $run->update([
                'total_gross' => $totalGross,
                'total_deductions' => $totalDeductions,
                'total_net' => $totalGross - $totalDeductions,
            ]);

            AuditService::log('created', 'hr', $run);
            return $run;
        });
    }
}