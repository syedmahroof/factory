<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Company extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $fillable = [
        'code', 'name', 'legal_name', 'address', 'city', 'state', 'country',
        'postal_code', 'phone', 'email', 'website', 'tax_id', 'registration_number',
        'currency', 'fiscal_year_start_month', 'is_active',
    ];

    /**
     * The table keeps a separate auto-increment bigint `id` and a `uuid` column,
     * so the UUID belongs in `uuid`, not in the primary key. Without this,
     * HasUuids overwrites `id` with a UUID string that MySQL coerces to 1,
     * colliding with the first seeded row (Duplicate entry '1').
     */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function plants() { return $this->hasMany(Plant::class); }
    public function departments() { return $this->hasMany(Department::class); }
    public function warehouses() { return $this->hasMany(Warehouse::class); }
    public function users() { return $this->hasMany(User::class); }
    public function items() { return $this->hasMany(Item::class); }
    public function customers() { return $this->hasMany(Customer::class); }
    public function suppliers() { return $this->hasMany(Supplier::class); }
}