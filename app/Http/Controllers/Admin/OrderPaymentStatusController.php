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
            $updatedOrder = $paymentStatusService->applyMidtransPayload($order, $payload);

            return back()->with(
                'success',
                'Status Midtrans berhasil dicek. Status pembayaran sekarang: ' . ucfirst($updatedOrder->payment_status) . '.'
            );
        } catch (\Throwable $e) {
            report($e);

            $message = 'Gagal mengecek status Midtrans.';

            if (config('app.debug')) {
                $message .= ' Detail: ' . $e->getMessage();
            }

            return back()->with('error', $message);
        }
    }
}
