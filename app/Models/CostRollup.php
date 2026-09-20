<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CostRollup extends Model {
    protected $guarded = [];
    protected $table = 'cost_rollups';
    protected $casts = ['effective_date' => 'date'];
    public function item() { return $this->belongsTo(Item::class); }
}