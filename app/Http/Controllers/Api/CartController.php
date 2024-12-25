<?php

namespace App\Http\Controllers\Api;

use App\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CartController extends BaseController
{
    public function index(Request $request)
    {
        $cart = Cart::with('product.images')
                   ->where('user_id', $request->user()->id)
                   ->get();
                   
        return $this->sendResponse($cart, 'Cart items retrieved successfully.');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id'
        ]);

        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors(), 400);
        }

        // Check if product already in cart
        $existingItem = Cart::where('user_id', $request->user()->id)
                          ->where('product_id', $request->product_id)
                          ->first();

        if ($existingItem) {
            return $this->sendError('Product already in cart.', [], 400);
        }

        $cart = Cart::create([
            'user_id' => $request->user()->id,
            'product_id' => $request->product_id
        ]);

        return $this->sendResponse($cart->load('product.images'), 'Product added to cart successfully.');
    }

    public function destroy($id)
    {
        $cartItem = Cart::where('id', $id)
                       ->where('user_id', request()->user()->id)
                       ->first();
        
        if (is_null($cartItem)) {
            return $this->sendError('Cart item not found.');
        }

        $cartItem->delete();
        return $this->sendResponse([], 'Product removed from cart successfully.');
    }

    public function clear(Request $request)
    {
        Cart::where('user_id', $request->user()->id)->delete();
        return $this->sendResponse([], 'Cart cleared successfully.');
    }
}
