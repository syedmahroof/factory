<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class DepreciationEntry extends Model {
    protected $guarded = [];
    protected $table = 'depreciation_entries';
    protected $casts = ['depreciation_date' => 'date'];
    public function fixedAsset() { return $this->belongsTo(FixedAsset::class); }
    public function journal() { return $this->belongsTo(Journal::class); }
}