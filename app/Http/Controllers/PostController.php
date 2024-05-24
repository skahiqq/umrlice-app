<?php

namespace App\Http\Controllers;

use App\Models\PaymentTransaction;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function isPaid(Request $request)
    {
        $isPaid = PaymentTransaction::where([
            'user_id' => $request->user_id,
            'post_id' => $request->post_id,
            'type' => 3
        ]);

        response()->json(['success' => (bool)$isPaid]);
    }
}
