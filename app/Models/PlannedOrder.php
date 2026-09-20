<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
class PlannedOrder extends Model {
    use HasUuids;

    public function uniqueIds(): array { return ['uuid']; }

    protected $guarded = [];
    protected $casts = ['required_date' => 'date', 'planned_start_date' => 'date'];
    public function item() { return $this->belongsTo(Item::class); }
    public function plant() { return $this->belongsTo(Plant::class); }
}