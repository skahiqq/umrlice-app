<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentTransaction extends Model
{
    use HasFactory;

    public const TYPE = [
        'PREAUTHORIZE',
        'CAPTURE'
    ];

    protected $fillable = [
        'user_id',
        'post_id',
        'transaction_id',
        'data'
    ];
}
