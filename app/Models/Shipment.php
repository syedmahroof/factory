<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Shipment extends Model {
    use SoftDeletes;
    protected $fillable = ['company_id','number','sales_order_id','warehouse_id','shipped_by','shipment_date','carrier','tracking_number','shipping_address','shipping_cost','notes','status'];
    protected $casts = ['shipment_date' => 'date'];
    public function salesOrder() { return $this->belongsTo(SalesOrder::class); }
    public function lines() { return $this->hasMany(ShipmentLine::class); }

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