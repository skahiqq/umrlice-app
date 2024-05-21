<?php

namespace App\Http\Controllers;

use App\Mail\PaymentMail;
use App\Models\Cart;
use App\Models\PaymentTransaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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
                'errorUrl' => 'https://umrlice.vercel.app/payment-error',
                'callbackUrl' => 'https://umrlice-api.com/callback'
            ]);

            $jsonResponse = $response->body();

            PaymentTransaction::create([
                'user_id' => $cart->user_id,
                'price' => $cart->price,
                'transaction_id' => $transactionId,
                'data' => $jsonResponse,
                'type' => request()->type ?? 0
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
                'post_id' => request()->post_id
            ]);

            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false, 'message' => 'User has no transaction without a post'], 422);
    }

    public function capture()
    {
        $preAuthorizeTransaction = PaymentTransaction::where([
            'user_id' => request()->user_id,
            'post_id' => request()->post_id
        ])->first();

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Authorization' => 'Basic ' . base64_encode('press-api:G4P4bs)4+I_V2nHKdCv3u+?YiVe1G'),
                'X-Signature' => 'OQxsFuuj4ifcLFaPAPyuO6TtaC65Yb'
            ])->post('https://asxgw.paymentsandbox.cloud/api/v3/transaction/press-simulator/capture', [ // debit
                'merchantTransactionId' => 'capture-' . $preAuthorizeTransaction->transaction_id,
                'amount' => $preAuthorizeTransaction->price,
                'currency' => 'EUR',
                'referenceUuid' => json_decode($preAuthorizeTransaction->data, TRUE)['uuid']
            ]);

            $jsonResponse = $response->body();

            PaymentTransaction::create([
                'user_id' => $preAuthorizeTransaction->user_id,
                'price' => $preAuthorizeTransaction->price,
                'transaction_id' => $preAuthorizeTransaction->transaction_id,
                'data' => $jsonResponse,
                'type' => 1
            ]);

            return $jsonResponse;
        } catch (\Exception $e) {
            Log::info(json_encode($e->getMessage()));
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Is going to cancel a previous preauthorize payment
     * @return \Illuminate\Http\JsonResponse|string
     */
    public function void()
    {
        $preAuthorizeTransaction = PaymentTransaction::where([
            'user_id' => request()->user_id,
            'post_id' => request()->post_id
        ])->first();

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Authorization' => 'Basic ' . base64_encode('press-api:G4P4bs)4+I_V2nHKdCv3u+?YiVe1G'),
                'X-Signature' => 'OQxsFuuj4ifcLFaPAPyuO6TtaC65Yb'
            ])->post('https://asxgw.paymentsandbox.cloud/api/v3/transaction/press-simulator/void', [
                'merchantTransactionId' => 'void-' . $preAuthorizeTransaction->transaction_id,
                'referenceUuid' => json_decode($preAuthorizeTransaction->data, TRUE)['uuid']
            ]);

            $jsonResponse = $response->body();

            PaymentTransaction::create([
                'user_id' => $preAuthorizeTransaction->user_id,
                'price' => $preAuthorizeTransaction->price,
                'transaction_id' => $preAuthorizeTransaction->transaction_id,
                'data' => $jsonResponse,
                'type' => 2
            ]);

            return $jsonResponse;
        } catch (\Exception $e) {
            Log::info(json_encode($e->getMessage()));
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function callBackPreAuthorize()
    {
        Log::info(json_encode(\request()->all));

        return response('OK', 200);
    }

    public function getLastPayment(Request $request)
    {
        $lastTransactionDetails = PaymentTransaction::where('user_id', $request->user_id)->orderBy('created_at', 'DESC')->first();

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Authorization' => 'Basic ' . base64_encode('press-api:G4P4bs)4+I_V2nHKdCv3u+?YiVe1G'),
                'X-Signature' => 'OQxsFuuj4ifcLFaPAPyuO6TtaC65Yb'
            ])->get('https://asxgw.paymentsandbox.cloud/api/v3/status/press-simulator/getByUuid/' . json_decode($lastTransactionDetails->data, TRUE)['uuid']);

            $lastTransaction = PaymentTransaction::where('type', 0)->orderBy('id', 'DESC')->first();

            $date = \Carbon\Carbon::now();
            $year = $date->year;
            $month = $date->month;
            $month = $month < 10 ? '0' . $month : $month;
            $day = $date->day;
            $day = $day < 10 ? '0' . $day : $day;
            $transactionId = $year . '-' . $month . '-' . $day . '-' . ($lastTransaction ? $lastTransaction->id + 1 : 1);

            $responseBody = $response->body();

            $transaction = PaymentTransaction::create([
                'user_id' => $request->user_id,
                'transaction_id' => PaymentTransaction::TYPE[3] . '_' . $transactionId,
                'data' => $responseBody,
                'type' => 3
            ]);

            $responseBody = json_decode($responseBody, TRUE);

            $concatResponseBody = array_merge($responseBody, ['timestamp' => Carbon::parse($lastTransaction->created_at)->format('Y-m-d h:i:s')]);

            Log::info('email ' . $concatResponseBody['customer']['email']);


            if (!$lastTransaction->sent) {
                \Illuminate\Support\Facades\Mail::to($concatResponseBody['customer']['email'])
                    ->send(new \App\Mail\PaymentMail($concatResponseBody));
            }

            return json_encode($concatResponseBody);
        } catch (\Exception $e) {
            Log::info(json_encode($e->getMessage()));
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
