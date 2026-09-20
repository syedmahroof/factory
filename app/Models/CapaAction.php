<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
class CapaAction extends Model {
    use HasUuids;

    public function uniqueIds(): array { return ['uuid']; }

    protected $guarded = [];
    protected $casts = ['due_date' => 'date', 'completed_date' => 'date', 'effectiveness_review_date' => 'date'];
    public function ncr() { return $this->belongsTo(Ncr::class); }
    public function owner() { return $this->belongsTo(User::class, 'owner_id'); }
}