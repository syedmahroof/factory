<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Capa extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id', 'number', 'ncr_id', 'type', 'root_cause', 'action_description',
        'assigned_to', 'due_date', 'effectiveness_criteria', 'effectiveness_result',
        'status', 'actual_close_date',
    ];

    protected $casts = ['due_date' => 'date', 'actual_close_date' => 'date'];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function ncr()
    {
        return $this->belongsTo(Ncr::class);
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
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
