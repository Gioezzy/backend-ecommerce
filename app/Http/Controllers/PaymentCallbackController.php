<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentCallbackController extends Controller
{
    public function receive(Request $request)
    {
        try {
            $serverKey = config('services.midtrans.server_key');

            $notif = $request->all();
            Log::info('Midtrans Notification', $notif);

            $orderId = $notif['order_id'] ?? null;
            $transactionStatus = $notif['transaction_status'] ?? null;
            $fraudStatus = $notif['fraud_status'] ?? null;

            if (!$orderId) {
                return response()->json(['message' => 'Invalid payload'], 400);
            }

            $signature = hash(
                'sha512',
                $orderId .
                $notif['status_code'] .
                $notif['gross_amount'] .
                $serverKey
            );

            if (!hash_equals($signature, $notif['signature_key'])) {
                return response()->json(['message' => 'Invalid signature'], 403);
            }

            $realOrderId = explode('-', $orderId)[0];
            $order = Order::find($realOrderId);

            if (!$order) {
                return response()->json(['message' => 'Order not found'], 404);
            }

            match ($transactionStatus) {
                'capture', 'settlement' => $order->update(['payment_status' => 'paid']),
                'pending' => $order->update(['payment_status' => 'pending']),
                'cancel', 'deny', 'expire' => $order->update([
                    'payment_status' => 'failed',
                    'status' => 'Cancelled',
                ]),
                default => null,
            };

            return response()->json(['message' => 'OK']);

        } catch (\Throwable $e) {
            Log::error('Midtrans Callback Error', [
                'error' => $e->getMessage(),
                'payload' => $request->all(),
            ]);

            return response()->json(['message' => 'Internal error'], 500);
        }
    }
}
