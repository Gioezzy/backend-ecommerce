<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = Order::with('items.product')
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($orders);
    }

    public function store(Request $request)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:product_items,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        try {
            DB::beginTransaction();

            $totalPrice = 0;
            $orderItemsData = [];

            foreach ($request->items as $item) {
                $product = ProductItem::find($item['product_id']);
                
                if ($product->stock < $item['quantity']) {
                    throw new \Exception("Insufficient stock for {$product->name}");
                }

                $price = $product->price;
                $totalPrice += $price * $item['quantity'];

                $orderItemsData[] = [
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'price' => $price,
                ];

                $product->decrement('stock', $item['quantity']);
            }


            $order = Order::create([
                'user_id' => $request->user()->id,
                'total_price' => $totalPrice,
                'status' => 'PENDING',
            ]);

            foreach ($orderItemsData as $data) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $data['product_id'],
                    'quantity' => $data['quantity'],
                    'price' => $data['price'],
                ]);
            }

            \Midtrans\Config::$serverKey = env('MIDTRANS_SERVER_KEY');
            \Midtrans\Config::$isProduction = env('MIDTRANS_IS_PRODUCTION', false);
            \Midtrans\Config::$isSanitized = env('MIDTRANS_IS_SANITIZED', true);
            \Midtrans\Config::$is3ds = env('MIDTRANS_IS_3DS', true);

            $params = [
                'transaction_details' => [
                    'order_id' => $order->id . '-' . time(), 
                    'gross_amount' => (int) $totalPrice,
                ],
                'customer_details' => [
                    'first_name' => $request->user()->name,
                    'email' => $request->user()->email,
                ],
                'notification_url' => [url('/api/midtrans-callback')],
            ];

            $snapToken = \Midtrans\Snap::getSnapToken($params);

            $order->snap_token = $snapToken;
            $order->payment_url = "https://app.sandbox.midtrans.com/snap/v2/vtweb/" . $snapToken; 
            $order->save();

            DB::commit();

            return response()->json([
                'message' => 'Order created successfully',
                'order_id' => $order->id,
                'snap_token' => $snapToken,
                'payment_url' => $order->payment_url 
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Order creation failed: ' . $e->getMessage()], 400);
        }
    }
}
