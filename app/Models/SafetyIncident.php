<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SafetyIncident extends Model {
    use SoftDeletes;
    protected $fillable = ['company_id','number','type','severity','description','location','reported_by','incident_date','immediate_actions','investigation_findings','corrective_actions','status'];
    protected $casts = ['incident_date' => 'date'];

}