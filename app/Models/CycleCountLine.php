<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CycleCountLine extends Model {
    protected $guarded = [];
    public function cycleCount() { return $this->belongsTo(CycleCount::class); }
    public function item() { return $this->belongsTo(Item::class); }
}