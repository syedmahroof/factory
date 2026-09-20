<?php
namespace App\Actions\Hr;

use App\Models\TimeBooking;
use App\Services\AuditService;

class RecordTimeBooking
{
    public function execute(array $data): TimeBooking
    {
        $booking = TimeBooking::create($data);
        AuditService::log('created', 'hr', $booking);
        return $booking;
    }
}