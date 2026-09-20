<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
class Recall extends Model {
    use HasUuids;

    public function uniqueIds(): array { return ['uuid']; }

    protected $guarded = [];
    protected $casts = ['affected_batches' => 'array', 'affected_customers' => 'array'];
    public function item() { return $this->belongsTo(Item::class); }
}