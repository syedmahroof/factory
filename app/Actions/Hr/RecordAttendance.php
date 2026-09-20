<?php
namespace App\Actions\Hr;

use App\Models\Attendance;
use App\Services\AuditService;

class RecordAttendance
{
    public function execute(array $data): Attendance
    {
        // Auto-calculate hours worked
        if (isset($data['clock_in']) && isset($data['clock_out'])) {
            $start = \Carbon\Carbon::parse($data['clock_in']);
            $end = \Carbon\Carbon::parse($data['clock_out']);
            $data['hours_worked'] = $start->diffInMinutes($end) / 60;
        }

        $attendance = Attendance::create($data);
        AuditService::log('created', 'hr', $attendance, null, $data);
        return $attendance;
    }
}