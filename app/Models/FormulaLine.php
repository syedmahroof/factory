<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class FormulaLine extends Model {
    protected $guarded = [];
    public function formula() { return $this->belongsTo(Formula::class); }
    public function item() { return $this->belongsTo(Item::class); }
    public function uom() { return $this->belongsTo(Uom::class, 'uom_id'); }
}