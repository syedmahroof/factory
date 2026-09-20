<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
class PermitToWork extends Model {
    use HasUuids;

    public function uniqueIds(): array { return ['uuid']; }

    protected $guarded = [];
    protected $table = 'permits_to_work';
    protected $casts = ['valid_from' => 'datetime', 'valid_until' => 'datetime'];
    public function issuedBy() { return $this->belongsTo(User::class, 'issued_by'); }
}