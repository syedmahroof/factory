<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class QualityGate extends Model {
    protected $guarded = [];
    protected $table = 'quality_gates';
    public function qualityPlan() { return $this->belongsTo(QualityPlan::class); }
}