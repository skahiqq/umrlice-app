<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\PaymentTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function initPayment()
    {
        $lastTransaction = PaymentTransaction::orderBy('id', 'DESC')->first();

        $date = \Carbon\Carbon::now();
        $year = $date->year;
        $month = $date->month;
        $month = $month < 10 ? '0' . $month : $month;
        $day = $date->day;
        $day = $day < 10 ? '0' . $day : $day;
        $transactionId = $year . '-' . $month . '-' . $day . '-' . ($lastTransaction ? $lastTransaction->id + 1 : 1);

        $cart = Cart::where('user_id', request()->user_id)->first();

        Log::info(json_encode($cart->price));
        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Authorization' => 'Basic ' . base64_encode('press-api:G4P4bs)4+I_V2nHKdCv3u+?YiVe1G'),
                'X-Signature' => 'OQxsFuuj4ifcLFaPAPyuO6TtaC65Yb'
            ])->post('https://asxgw.paymentsandbox.cloud/api/v3/transaction/press-simulator/preauthorize', [ // debit
                'merchantTransactionId' => $transactionId,
                'amount' => $cart->price,
                'currency' => 'EUR',
                'transactionToken' => request()->token,
                'successUrl' => 'https://umrlice.vercel.app/payment-success',
                'cancelUrl' => 'https://umrlice.vercel.app/payment-error',
                'errorUrl' => 'https://umrlice.vercel.app/payment-error'
            ]);

            $jsonResponse = $response->body();

            PaymentTransaction::create([
                'user_id' => $cart->user_id,
                'price' => $cart->price,
                'transaction_id' => $transactionId,
                'data' => $jsonResponse,
                'type' => request()->type ?? PaymentTransaction::TYPE[0]
            ]);

            return $jsonResponse;
        } catch (\Exception $e) {
            Log::info(json_encode($e->getMessage()));
            return response()->json(['success' => false]);
        }
    }

    public function setPostId()
    {
        $transactionWithoutPost = PaymentTransaction::where('user_id', request()->user_id)
            ->where('post_id', null)
            ->orderBy('created_at', 'DESC')
            ->first();

        if ($transactionWithoutPost) {
            $transactionWithoutPost->update([
                'post_id' => request()->post_id,
                'type' => PaymentTransaction::TYPE[1]
            ]);

            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false, 'message' => 'User has no transaction without a post'], 422);
    }
}
