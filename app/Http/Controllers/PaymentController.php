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
                'Authorization' => 'Basic ' . base64_encode(env('PAYMENT_USERNAME') . ':' . env('PAYMENT_PASSWORD')),
                'X-Signature' => '' . env('PAYMENT_SHARED_SECRET')
            ])->post(env('PAYMENT_BASE_URL') . 'api/v3/transaction/'.env('PAYMENT_API_KEY').'/preauthorize', [ // debit
                'merchantTransactionId' => $transactionId,
                'amount' => $cart->price,
                'currency' => 'EUR',
                'transactionToken' => request()->token,
                'successUrl' => 'https://umrlice.vijesti.me/payment-success',
                'cancelUrl' => 'https://umrlice.vijesti.me/payment-error',
                'errorUrl' => 'https://umrlice.vijesti.me/payment-error',
                'callbackUrl' => 'https://umrlice-api.com/callback',
                'customer' => [
                    'billingAddress1' => request()->billingAddress1,
                    'billingCity' => request()->billingCity,
                    'billingPostcode' => request()->billingPostcode,
                    'billingCountry' => request()->billingCountry
                ]
            ]);

            $jsonResponse = $response->body();

            PaymentTransaction::create([
                'user_id' => $cart->user_id,
                'post_id' => $cart->data['id'],
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
        $isPaid = PaymentTransaction::where([
            'user_id' => request()->user_id,
            'post_id' => request()->post_id,
            'type' => 3
        ])->first();

        $failed = PaymentTransaction::where([
            'user_id' => request()->user_id,
            'post_id' => request()->post_id,
            'type' => 4
        ])->first();

       if ($isPaid) {
           $preAuthorizeTransaction = PaymentTransaction::where([
               'user_id' => request()->user_id,
               'post_id' => request()->post_id
           ])->first();

           try {
               $response = Http::withHeaders([
                   'Content-Type' => 'application/json',
                   'Accept' => 'application/json',
                   'Authorization' => 'Basic ' . base64_encode(env('PAYMENT_USERNAME') . ':' . env('PAYMENT_PASSWORD')),
                   'X-Signature' => '' . env('PAYMENT_SHARED_SECRET')
               ])->post(env('PAYMENT_BASE_URL') . 'api/v3/transaction/'.env('PAYMENT_API_KEY').'/capture', [ // debit
                   'merchantTransactionId' => 'capture-' . $preAuthorizeTransaction->transaction_id,
                   'amount' => $preAuthorizeTransaction->price,
                   'currency' => 'EUR',
                   'referenceUuid' => json_decode($isPaid->data, TRUE)['uuid']
               ]);

               $jsonResponse = $response->body();

               PaymentTransaction::create([
                   'user_id' => $isPaid->user_id,
                   'price' => $isPaid->price,
                   'transaction_id' => $isPaid->transaction_id,
                   'data' => $jsonResponse,
                   'type' => 1
               ]);

               return $jsonResponse;
           } catch (\Exception $e) {
               Log::info(json_encode($e->getMessage()));
               return response()->json(['success' => false, 'message' => $e->getMessage()]);
           }
       }

       if ($failed) {
           return response()->json(['success' => false, 'message' => 'failed']);
       }

       return response()->json(['success' => false, 'message' => 'yet']);
    }

    /**
     * Is going to cancel a previous preauthorize payment
     */
    public function void()
    {
        $preAuthorizeTransaction = PaymentTransaction::where([
            'user_id' => request()->user_id,
            'post_id' => request()->post_id
        ])->first();


        if (!$preAuthorizeTransaction) {
            return \response()->json(['success' => true]);
        }

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Authorization' => 'Basic ' . base64_encode(env('PAYMENT_USERNAME') . ':' . env('PAYMENT_PASSWORD')),
                'X-Signature' => '' . env('PAYMENT_SHARED_SECRET')
            ])->post(env('PAYMENT_BASE_URL') . 'api/v3/transaction/'.env('PAYMENT_API_KEY').'/void', [
                'merchantTransactionId' => 'void-' . $preAuthorizeTransaction->transaction_id . '-' . PaymentTransaction::orderBy('created_at', 'DESC')->first()->id,
                'referenceUuid' => json_decode($preAuthorizeTransaction->data, TRUE)['uuid']
            ]);

            $jsonResponse = $response->body();

            if (json_decode($jsonResponse, TRUE)['success'] === false) {
                $cart = Cart::whereUserId((int) request()->user_id)->where('data', '!=', null)->first();

                if ($cart) {
                    if ($cart['data']['id'] === (int) \request()->post_id) {
                        return response()->json(['success' => true]);
                    }
                    return \response()->json(['success' => true]);
                }

                return \response()->json(['success' => true]);
            }

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
                'Authorization' => 'Basic ' . base64_encode(env('PAYMENT_USERNAME') . ':' . env('PAYMENT_PASSWORD')),
                'X-Signature' => '' . env('PAYMENT_SHARED_SECRET')
            ])->get(env('PAYMENT_BASE_URL') . 'api/v3/status/'.env('PAYMENT_API_KEY').'/getByUuid/' . json_decode($lastTransactionDetails->data, TRUE)['uuid']);

            $lastTransaction = PaymentTransaction::where('type', 0)->orderBy('id', 'DESC')->first();
            $isErrorLastTransaction = PaymentTransaction::where('type', 4)->orderBy('created_at', 'DESC')->first();

            if ($lastTransaction->sent && $isErrorLastTransaction && $isErrorLastTransaction->sent) {
                return response()->json(['success' => false]);
            }

            $date = \Carbon\Carbon::now();
            $year = $date->year;
            $month = $date->month;
            $month = $month < 10 ? '0' . $month : $month;
            $day = $date->day;
            $day = $day < 10 ? '0' . $day : $day;
            $transactionId = $year . '-' . $month . '-' . $day . '-' . ($lastTransaction ? $lastTransaction->id + 1 : 1);

            $responseBody = $response->body();

            $oldBody = $responseBody;

            $responseBody = json_decode($responseBody, TRUE);

            $concatResponseBody = array_merge($responseBody, ['timestamp' => Carbon::parse($lastTransaction->created_at)->format('Y-m-d h:i:s')]);

            Log::info('email ' . $concatResponseBody['customer']['email']);

            $transaction = PaymentTransaction::create([
                'user_id' => $request->user_id,
                'post_id' => $lastTransaction->post_id,
                'price' => $lastTransaction->price,
                'transaction_id' => (isset($responseBody['errors']) ? PaymentTransaction::TYPE[4] : PaymentTransaction::TYPE[3]) . '_' . $transactionId,
                'data' => $oldBody,
                'type' => isset($responseBody['errors']) ? 4 : 3
            ]);

            if (!$lastTransaction->sent) {
                if (!isset($responseBody['errors'])) {
                    \Illuminate\Support\Facades\Mail::to($concatResponseBody['customer']['email'])
                        ->send(new \App\Mail\PaymentMail($concatResponseBody));
                } else {
                    $transaction->sent = 1;
                    $transaction->save();
                }

                $lastTransaction->sent = 1;
                $lastTransaction->save();
            }

            return json_encode($concatResponseBody);
        } catch (\Exception $e) {
            Log::info(json_encode($e->getMessage()));
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
