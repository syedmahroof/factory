<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
class CycleCount extends Model {
    use HasUuids;

    public function uniqueIds(): array { return ['uuid']; }

    protected $guarded = [];
    public function warehouse() { return $this->belongsTo(Warehouse::class); }
    public function lines() { return $this->hasMany(CycleCountLine::class); }
}