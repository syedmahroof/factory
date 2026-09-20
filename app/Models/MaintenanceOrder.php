<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MaintenanceOrder extends Model {
    use SoftDeletes;
    protected $fillable = [
        'company_id','number','asset_id','maintenance_plan_id','type','priority','description',
        'assigned_to','requested_by','requested_date','scheduled_date','start_date','end_date',
        'estimated_hours','actual_hours','estimated_cost','actual_cost','findings',
        'corrective_actions','lockout_tagout','status','approved_by','approved_at',
    ];
    protected $casts = ['requested_date' => 'date','start_date' => 'date','end_date' => 'date','approved_at' => 'datetime'];
    public function asset() { return $this->belongsTo(Asset::class); }
    public function assignedTo() { return $this->belongsTo(User::class, 'assigned_to'); }

}