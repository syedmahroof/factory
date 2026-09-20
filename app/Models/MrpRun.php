<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class MrpRun extends Model {
    protected $guarded = [];
    protected $casts = ['run_at' => 'datetime', 'parameters' => 'array'];
    public function plant() { return $this->belongsTo(Plant::class); }
    public function user() { return $this->belongsTo(User::class, 'run_by'); }
}