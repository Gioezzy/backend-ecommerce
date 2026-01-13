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
            $serverKey = config('app.midtrans_server_key') ?? env('MIDTRANS_SERVER_KEY');
            
            $notif = $request->all();

            Log::info('Midtrans Notification:', $notif);

            $transactionStatus = $notif['transaction_status'];
            $type = $notif['payment_type'];
            $orderId = $notif['order_id'];
            $fraudStatus = $notif['fraud_status'];

            $input = $orderId . $notif['status_code'] . $notif['gross_amount'] . $serverKey;
            $signature = openssl_digest($input, 'sha512');

            if ($signature != $notif['signature_key']) {
                Log::warning('Midtrans Invalid Signature: ' . $orderId);
                return response()->json(['message' => 'Invalid Signature'], 403);
            }

            $realOrderId = explode('-', $orderId)[0];
            $order = Order::find($realOrderId);

            if (!$order) {
                return response()->json(['message' => 'Order not found'], 404);
            }

            if ($transactionStatus == 'capture') {
                if ($fraudStatus == 'challenge') {
                    $order->update(['status' => 'PENDING']);
                } else if ($fraudStatus == 'accept') {
                    $order->update(['status' => 'PAID']);
                }
            } else if ($transactionStatus == 'settlement') {
                $order->update(['status' => 'PAID']);
            } else if ($transactionStatus == 'cancel' ||
              $transactionStatus == 'deny' ||
              $transactionStatus == 'expire') {
                $order->update(['status' => 'CANCELLED']);
            } else if ($transactionStatus == 'pending') {
                $order->update(['status' => 'PENDING']);
            }

            return response()->json(['message' => 'Callback received successfully']);

        } catch (\Exception $e) {
            Log::error('Midtrans Callback Error: ' . $e->getMessage());
            return response()->json(['message' => 'Error processing callback'], 500);
        }
    }
}
