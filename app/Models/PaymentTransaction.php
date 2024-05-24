<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentTransaction extends Model
{
    use HasFactory;

    public const TYPE = [
        'PREAUTHORIZE',
        'CAPTURE',
        'VOID',
        'PAYMENT_PREAUTHORIZE_SUCCESS',
        'PAYMENT_PREAUTHORIZE_ERROR',
    ];

    protected $fillable = [
        'user_id',
        'post_id',
        'price',
        'type',
        'transaction_id',
        'data'
    ];
}
