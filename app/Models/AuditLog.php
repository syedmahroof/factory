<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class AuditLog extends Model {
    protected $fillable = ['company_id','user_id','event','auditable_type','auditable_id','old_values','new_values','url','ip_address','user_agent','tags'];
    protected $casts = ['old_values' => 'json','new_values' => 'json'];
    public function user() { return $this->belongsTo(User::class); }
    public function auditable() { return $this->morphTo(); }

}