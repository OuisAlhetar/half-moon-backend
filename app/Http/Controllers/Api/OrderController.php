<?php

namespace App\Http\Controllers\Api;

use App\Models\Order;
use App\Models\Cart;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OrderController extends BaseController
{
    public function index(Request $request)
    {
        $orders = Order::with(['items.product.images'])
                      ->where('user_id', $request->user()->id)
                      ->orderBy('created_at', 'desc')
                      ->get();
                   
        return $this->sendResponse($orders, 'Orders retrieved successfully.');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'address' => 'required|string'
        ]);

        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors(), 400);
        }

        $cartItems = Cart::with('product')
                        ->where('user_id', $request->user()->id)
                        ->get();

        if ($cartItems->isEmpty()) {
            return $this->sendError('Cart is empty.', [], 400);
        }

        try {
            DB::beginTransaction();

            $total = 0;
            foreach ($cartItems as $item) {
                $product = $item->product;
                if ($product->qty < 1) {
                    throw new \Exception("Product {$product->name} is out of stock.");
                }
                $price = $product->discount > 0 ? $product->price - $product->discount : $product->price;
                $total += $price;
            }

            $order = Order::create([
                'user_id' => $request->user()->id,
                'address' => $request->address,
                'total' => $total,
                'track_progress' => 'pending'
            ]);

            foreach ($cartItems as $item) {
                $product = $item->product;
                $price = $product->discount > 0 ? $product->price - $product->discount : $product->price;
                
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'qty' => 1,
                    'price' => $price,
                    'total' => $price
                ]);

                // Decrease product quantity
                $product->decrement('qty');
            }

            // Clear cart
            Cart::where('user_id', $request->user()->id)->delete();

            DB::commit();

            return $this->sendResponse($order->load('items.product'), 'Order created successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            return $this->sendError('Order Creation Error.', ['error' => $e->getMessage()], 400);
        }
    }

    public function show($id)
    {
        $order = Order::with(['items.product.images'])
                     ->where('id', $id)
                     ->where('user_id', request()->user()->id)
                     ->first();
        
        if (is_null($order)) {
            return $this->sendError('Order not found.');
        }
        
        return $this->sendResponse($order, 'Order retrieved successfully.');
    }

    public function updateProgress(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'track_progress' => 'required|in:pending,processing,shipped,delivered,cancelled'
        ]);

        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors(), 400);
        }

        $order = Order::find($id);
        
        if (is_null($order)) {
            return $this->sendError('Order not found.');
        }

        // Only admin can update order progress
        if (!$request->user()->role_id === 1) {
            return $this->sendError('Unauthorized.', [], 403);
        }

        $order->track_progress = $request->track_progress;
        $order->save();

        return $this->sendResponse($order, 'Order progress updated successfully.');
    }
}
