<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function insert()
    {
        $cart = Cart::where([
                    'user_id' => request()->user_id
                ])->first();
        if (!$cart) {
            Cart::create([
                'user_id' => request()->user_id
            ]);
        }
    }

    public function get()
    {
        return Cart::where('user_id', request()->user_id)->first();
    }

    public function update()
    {
        $cart = Cart::where('user_id', request()->user_id)->first();

        $cart->update([
            'price' => request()->price ?: $cart->price,
            'data' => request()->data ? json_decode(request()->data) : ($cart->data ? json_decode($cart->data) : null)
        ]);
    }

    public function destroy()
    {
        Cart::where('user_id', request()->user_id)->delete();
    }

    public function setNull()
    {
        Cart::where('user_id', request()->user_id)->update([
            'price' => null,
            'data' => null
        ]);
    }
}
