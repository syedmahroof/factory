<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
class Formula extends Model {
    use HasUuids;

    public function uniqueIds(): array { return ['uuid']; }

    protected $guarded = [];
    public function item() { return $this->belongsTo(Item::class); }
    public function lines() { return $this->hasMany(FormulaLine::class); }
}