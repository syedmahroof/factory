<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Inspection extends Model {
    use SoftDeletes;
    protected $fillable = ['company_id','number','quality_plan_id','source_type','source_id','item_id','lot_id','serial_id','quantity_inspected','quantity_accepted','quantity_rejected','quantity_on_hold','inspector_id','inspection_date','result','notes','status'];
    protected $casts = ['quantity_inspected' => 'decimal:4','inspection_date' => 'date'];
    public function qualityPlan() { return $this->belongsTo(QualityPlan::class); }
    public function item() { return $this->belongsTo(Item::class); }
    public function results() { return $this->hasMany(InspectionResult::class); }

}