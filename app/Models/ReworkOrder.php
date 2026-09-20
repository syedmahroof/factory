<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
class ReworkOrder extends Model {
    use HasUuids;

    public function uniqueIds(): array { return ['uuid']; }

    protected $guarded = [];
    public function productionOrder() { return $this->belongsTo(ProductionOrder::class); }
    public function ncr() { return $this->belongsTo(Ncr::class); }
}