<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GoodsReceipt extends Model {
    use SoftDeletes;
    protected $fillable = ['company_id','number','purchase_order_id','supplier_id','warehouse_id','received_by','receipt_date','delivery_note_id','notes','status'];
    protected $casts = ['receipt_date' => 'date'];
    public function supplier() { return $this->belongsTo(Supplier::class); }
    public function purchaseOrder() { return $this->belongsTo(PurchaseOrder::class); }
    public function lines() { return $this->hasMany(GoodsReceiptLine::class); }

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