<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function initPayment()
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Basic ' . base64_encode('press-api:G4P4bs)4+I_V2nHKdCv3u+?YiVe1G'),
            'X-Signature' => 'OQxsFuuj4ifcLFaPAPyuO6TtaC65Yb'
        ];

        $request_body = [
            'merchantTransactionId' => '2018-04-11-04',
            'amount' => '9.99',
            'currency' => 'EUR',
            'transactionToken' => request()->token,
            'successUrl' => 'https://umrlice-e44rgsm58-ardis-projects-0c0d2ea9.vercel.app/poslednji-pozdravi',
            'cancelUrl' => 'https://5e5e-46-99-63-213.ngrok-free.app/redirected.php',
            'errorUrl' => 'https://5e5e-46-99-63-213.ngrok-free.app/redirected.php'
        ];

        Log::info(json_encode(request()->all()));

        try {
            /*$response = $client->request('POST','https://asxgw.paymentsandbox.cloud/api/v3/transaction/press-simulator/debit', [
                'headers' => $headers,
                'json' => $request_body,
            ]);*/

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Authorization' => 'Basic ' . base64_encode('press-api:G4P4bs)4+I_V2nHKdCv3u+?YiVe1G'),
                'X-Signature' => 'OQxsFuuj4ifcLFaPAPyuO6TtaC65Yb'
            ])->post('https://asxgw.paymentsandbox.cloud/api/v3/transaction/press-simulator/debit', [
                'merchantTransactionId' => '20200-04-11-07',
                'amount' => '9.99',
                'currency' => 'EUR',
                'transactionToken' => request()->token,
                'successUrl' => 'https://umrlice-e44rgsm58-ardis-projects-0c0d2ea9.vercel.app/poslednji-pozdravi',
                'cancelUrl' => 'https://5e5e-46-99-63-213.ngrok-free.app/redirected.php',
                'errorUrl' => 'https://5e5e-46-99-63-213.ngrok-free.app/redirected.php'
            ]);

            Log::info(json_encode($response));

            return $response->body();

        } catch (\Exception $e) {

            Log::info(json_encode($e->getMessage()));
        }

        return response()->json(['success' => true]);
    }
}
