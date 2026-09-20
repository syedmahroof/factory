<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
class BatchRecord extends Model {
    use HasUuids;

    public function uniqueIds(): array { return ['uuid']; }

    protected $guarded = [];
    protected $casts = ['process_values' => 'array', 'manufacture_date' => 'date', 'expiry_date' => 'date'];
    public function productionOrder() { return $this->belongsTo(ProductionOrder::class); }
    public function item() { return $this->belongsTo(Item::class); }
}