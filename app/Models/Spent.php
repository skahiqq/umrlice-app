<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Spent extends Model
{
    use HasFactory;

    protected $fillable = [
        'price',
        'description',
        'type'
    ];

    protected $appends = [
        'month'
    ];

    public function getMonthAttribute()
    {
        return Carbon::parse($this->attributes['created_at'])->monthName;
    }
}
