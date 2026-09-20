<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ncr extends Model {
    use SoftDeletes;
    protected $table = 'ncrs';
    protected $fillable = ['company_id','number','inspection_id','item_id','lot_id','severity','source','defect_description','affected_quantity','reported_by','reported_date','containment_actions','disposition','status','assigned_to','target_close_date','actual_close_date'];
    protected $casts = ['reported_date' => 'date','affected_quantity' => 'decimal:4'];
    public function company() { return $this->belongsTo(Company::class); }
    public function item() { return $this->belongsTo(Item::class); }
    public function capas() { return $this->hasMany(Capa::class); }

}