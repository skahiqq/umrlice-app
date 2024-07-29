<?php

namespace App\Http\Controllers;

use App\Models\Spent;
use App\Models\Wallet;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function getWallet()
    {
        return Wallet::first();
    }

    public function getSpents()
    {
        return Spent::all();
    }
}
