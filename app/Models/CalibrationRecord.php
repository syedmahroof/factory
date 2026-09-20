<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
class CalibrationRecord extends Model {
    use HasUuids;

    public function uniqueIds(): array { return ['uuid']; }

    protected $guarded = [];
    protected $table = 'calibration_records';
    protected $casts = ['last_calibration_date' => 'date', 'next_calibration_date' => 'date'];
    public function plant() { return $this->belongsTo(Plant::class); }
}