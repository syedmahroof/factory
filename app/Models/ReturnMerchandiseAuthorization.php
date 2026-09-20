<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * An RMA. The table has always been `customer_returns` — the sales migration
 * created it under that name — while the route, the controller and the screen
 * call it an RMA, so the class carries the business name and points at the table.
 */
class ReturnMerchandiseAuthorization extends Model
{
    use SoftDeletes;

    protected $table = 'customer_returns';

    protected $fillable = [
        'company_id', 'number', 'customer_id', 'sales_order_id', 'created_by',
        'return_date', 'reason', 'status',
    ];

    protected $casts = ['return_date' => 'date'];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class);
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid();
            }
        });
    }
}
