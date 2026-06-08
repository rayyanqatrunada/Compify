<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Services\OrderPaymentStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MidtransNotificationController extends Controller
{
    public function __invoke(Request $request, OrderPaymentStatusService $paymentStatusService): JsonResponse
    {
        $order = $paymentStatusService->handleMidtransNotification($request->all());

        return response()->json([
            'success' => true,
            'order_number' => $order->order_number,
            'payment_status' => $order->payment_status,
            'order_status' => $order->order_status,
        ]);
    }
}
