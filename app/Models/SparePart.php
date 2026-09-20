<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SparePart extends Model {
    protected $guarded = [];
    protected $table = 'spare_parts';
    public function item() { return $this->belongsTo(Item::class); }
    public function asset() { return $this->belongsTo(Asset::class); }
}