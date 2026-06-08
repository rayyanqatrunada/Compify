<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\MidtransPaymentService;
use App\Services\OrderPaymentStatusService;
use Illuminate\Http\RedirectResponse;

class OrderPaymentStatusController extends Controller
{
    public function check(Order $order, MidtransPaymentService $midtrans, OrderPaymentStatusService $paymentStatusService): RedirectResponse
    {
        if ($order->payment_type !== 'midtrans_snap') {
            return back()->with('error', 'Order ini bukan pembayaran Midtrans.');
        }

        try {
            $payload = $midtrans->getStatus($order);
            $updatedOrder = $paymentStatusService->applyMidtransPayload($order, $payload, 'admin_midtrans_check');

            return back()->with(
                'success',
                'Status Midtrans berhasil dicek. Status pembayaran sekarang: ' . ucfirst($updatedOrder->payment_status) . '.'
            );
        } catch (\Throwable $e) {
            $errorMessage = $e->getMessage();
            $isNotFound = str_contains($errorMessage, 'Transaction doesn\'t exist')
                || str_contains($errorMessage, '"status_code":"404"')
                || str_contains($errorMessage, 'HTTP status code: 404');

            if ($isNotFound) {
                logger()->info('Order Midtrans tidak ditemukan saat cek detail order.', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'message' => $errorMessage,
                ]);

                return back()->with(
                    'warning',
                    'Order ini belum ditemukan di Midtrans. Biasanya karena order testing lama, Snap gagal dibuat saat checkout, atau key/environment Midtrans tidak cocok.'
                );
            }

            report($e);

            $message = 'Gagal mengecek status Midtrans.';

            if (config('app.debug')) {
                $message .= ' Detail: ' . $errorMessage;
            }

            return back()->with('error', $message);
        }
    }
}
