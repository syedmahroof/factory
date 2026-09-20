<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FixedAsset extends Model {
    use SoftDeletes;
    protected $fillable = ['company_id','number','asset_id','name','description','account_id','depreciation_account_id','cost_center_id','acquisition_date','acquisition_cost','accumulated_depreciation','net_book_value','salvage_value','useful_life_months','depreciation_method','status','notes'];
    protected $casts = ['acquisition_date' => 'date','acquisition_cost' => 'decimal:4','accumulated_depreciation' => 'decimal:4','net_book_value' => 'decimal:4'];
    public function account() { return $this->belongsTo(Account::class); }

}