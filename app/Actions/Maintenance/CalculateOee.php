<?php
namespace App\Actions\Maintenance;

use App\Models\{OeeRecord, WorkCenter};
use Illuminate\Support\Carbon;

class CalculateOee
{
    public function execute(int $workCenterId, Carbon $date): OeeRecord
    {
        $wc = WorkCenter::findOrFail($workCenterId);
        $capacityHours = 8; // Default 8-hour shift
        $downtime = $wc->downtimeEvents()->whereDate('start_time', $date)->sum('duration_hours') ?? 0;
        $operatingHours = $capacityHours - $downtime;

        // Get production output for the day
        $outputs = $wc->operationJobs()->whereDate('created_at', $date)->get();
        $totalCount = $outputs->sum('produced_quantity');
        $goodCount = $outputs->sum('produced_quantity') - $outputs->sum('scrap_quantity');

        $availability = $capacityHours > 0 ? ($operatingHours / $capacityHours) * 100 : 0;
        $performance = $operatingHours > 0 && $totalCount > 0 ? min(100, ($totalCount / ($operatingHours * ($wc->capacity ?? 10))) * 100) : 0;
        $quality = $totalCount > 0 ? ($goodCount / $totalCount) * 100 : 0;
        $oee = $availability * $performance * $quality / 10000;

        return OeeRecord::updateOrCreate(
            ['work_center_id' => $workCenterId, 'record_date' => $date->toDateString()],
            [
                'available_hours' => $capacityHours,
                'operating_hours' => $operatingHours,
                'downtime_hours' => $downtime,
                'total_count' => $totalCount,
                'good_count' => $goodCount,
                'availability' => round($availability, 2),
                'performance' => round($performance, 2),
                'quality' => round($quality, 2),
                'oee' => round($oee, 2),
            ]
        );
    }
}