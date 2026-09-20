<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CustomerCreditExposure extends Model {
    protected $guarded = [];
    protected $table = 'customer_credit_exposures';
    public function customer() { return $this->belongsTo(Customer::class); }
}