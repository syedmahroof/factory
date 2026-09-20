<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class BomLine extends Model {
    protected $fillable = ['bom_id','sequence','item_id','quantity','uom_id','scrap_factor','cost','is_optional','is_co_product','is_by_product','yield_percentage','is_active'];
    protected $casts = ['quantity' => 'decimal:6','scrap_factor' => 'decimal:2','cost' => 'decimal:4','sequence' => 'integer'];
    public function bom() { return $this->belongsTo(Bom::class); }
    public function item() { return $this->belongsTo(Item::class); }
    public function uom() { return $this->belongsTo(Uom::class); }

}