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

    public function addOrWithdrawAmount(Request $request)
    {
        $wallet = Wallet::first();

        if ($request->isAdding === true) {
            $wallet->amount += $request->amount;
        } else {
            $wallet->amount -= $request->amount;
        }

        $wallet->save();

        return response()->json(Wallet::first());
    }

    public function addSpent(Request $request)
    {
        Spent::create([
            'amount' => $request->amount,
            'description' => $request->description
        ]);
    }
}
