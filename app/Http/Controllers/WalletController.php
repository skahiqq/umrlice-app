<?php

namespace App\Http\Controllers;

use App\Models\Spent;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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

        Log::info(json_encode([$request->amount]));

        Wallet::where('id', 1)->update([
            'amount' => 100//$request->isAdding == true ? $wallet->amount + $request->amount : $wallet->amount - $request->amount
        ]);

        Log::info("amount added");

        return response()->json(['message' => 'Success added money']);
    }

    public function addSpent(Request $request)
    {
        Log::info(json_encode($request->all()));

        Spent::create([
            'price' => $request->amount,
            'description' => $request->description
        ]);

        return response()->json(['message' => 'Success']);
    }
}
