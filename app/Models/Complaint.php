<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
class Complaint extends Model {
    use HasUuids;

    public function uniqueIds(): array { return ['uuid']; }

    protected $guarded = [];
    public function customer() { return $this->belongsTo(Customer::class); }
    public function item() { return $this->belongsTo(Item::class); }
}