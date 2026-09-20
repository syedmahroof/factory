<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PreventiveMaintenanceSchedule extends Model {
    protected $guarded = [];
    protected $table = 'preventive_maintenance_schedules';
    protected $casts = ['next_due_date' => 'date'];
    public function maintenancePlan() { return $this->belongsTo(MaintenancePlan::class); }
    public function asset() { return $this->belongsTo(Asset::class); }
}