<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class InspectionResult extends Model {
    protected $fillable = ['inspection_id','quality_characteristic_id','actual_value','numeric_value','is_within_spec','notes','recorded_by','recorded_at'];
    public function inspection() { return $this->belongsTo(Inspection::class); }

}