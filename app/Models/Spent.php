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
        'month',
        'day',
        'year'
    ];

    public function getMonthAttribute()
    {
        return Carbon::parse($this->attributes['created_at'])->monthName;
    }

    public function getDayAttribute()
    {
        return Carbon::parse($this->attributes['created_at'])->day;
    }

    public function getYearAttribute()
    {
        return Carbon::parse($this->attributes['created_at'])->year;
    }
}
