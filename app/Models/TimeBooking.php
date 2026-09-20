<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class TimeBooking extends Model {
    protected $guarded = [];
    protected $table = 'time_bookings';
    protected $casts = ['booking_date' => 'date'];
    public function employee() { return $this->belongsTo(Employee::class); }
    public function productionOrder() { return $this->belongsTo(ProductionOrder::class); }
}