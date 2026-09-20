<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Item extends Model {
    use SoftDeletes;
    protected $fillable = [
        'company_id','code','name','description','type','category_id','base_uom_id','purchase_uom_id',
        'sales_uom_id','production_uom_id','weight','weight_uom','volume','volume_uom','length','width','height',
        'dimension_uom','hsn_code','barcode','standard_cost','selling_price','purchase_price','tax_rate',
        'is_serialized','is_lot_tracked','is_batch_tracked','shelf_life_days','reorder_level','reorder_quantity',
        'minimum_stock','maximum_stock','safety_stock','lead_time_days','valuation_method','is_active',
    ];
    protected $casts = [
        'standard_cost' => 'decimal:4','selling_price' => 'decimal:4','purchase_price' => 'decimal:4',
        'tax_rate' => 'decimal:2','weight' => 'decimal:4','volume' => 'decimal:4',
        'is_serialized' => 'boolean','is_lot_tracked' => 'boolean','is_batch_tracked' => 'boolean',
        'minimum_stock' => 'decimal:4','maximum_stock' => 'decimal:4','safety_stock' => 'decimal:4',
    ];
    public function company() { return $this->belongsTo(Company::class); }
    public function category() { return $this->belongsTo(ItemCategory::class, 'category_id'); }
    public function baseUom() { return $this->belongsTo(Uom::class, 'base_uom_id'); }
    public function purchaseUom() { return $this->belongsTo(Uom::class, 'purchase_uom_id'); }
    public function salesUom() { return $this->belongsTo(Uom::class, 'sales_uom_id'); }
    public function boms() { return $this->hasMany(Bom::class); }
    public function stockBalances() { return $this->hasMany(StockBalance::class); }
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = \Illuminate\Support\Str::uuid();
            }
        });
    }
}