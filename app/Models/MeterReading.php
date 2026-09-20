<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class MeterReading extends Model {
    protected $guarded = [];
    protected $table = 'meter_readings';
    protected $casts = ['reading_date' => 'datetime'];
    public function asset() { return $this->belongsTo(Asset::class); }
    public function recordedBy() { return $this->belongsTo(User::class, 'recorded_by'); }
}