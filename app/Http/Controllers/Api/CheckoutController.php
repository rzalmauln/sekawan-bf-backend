<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutRequest;
use App\Services\CheckoutService;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    public function __construct(
        protected CheckoutService $checkoutService
    ) {}

    public function store(CheckoutRequest $request)
    {
        try {
            $result = $this->checkoutService->checkout($request->validated());
            return response()->json($result);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function requestPayment(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer|exists:orders,id',
            'shipping_cost' => 'required|numeric|min:0',
        ]);

        try {
            $result = $this->checkoutService->requestPayment(
                $validated['id'],
                (float) $validated['shipping_cost']
            );

            return response()->json($result);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function submitPayment(Request $request)
    {
        $validated = $request->validate([
            'invoice_number' => 'required|string|exists:orders,invoice_number',
            'payment_proof' => 'required|image|max:2048',
        ]);

        try {
            $result = $this->checkoutService->submitPaymentProof(
                $validated['invoice_number'],
                $request->file('payment_proof')
            );

            return response()->json($result);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function verify(Request $request){
        $validated = $request->validate([
            'id' => 'required|integer|exists:orders,id'
        ]);
        try {
            $result = $this->checkoutService->verify($validated['id']);
            return response()->json($result);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function ship(Request $request){
        $validated = $request->validate([
            'id' => 'required|integer|exists:orders,id',
            'tracking_number' => 'required|string'
        ]);
        try {
            $result = $this->checkoutService->ship($validated['id'], $validated['tracking_number']);
            return response()->json($result);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function cancel(Request $request){
        $validated = $request->validate([
            'id' => 'required|integer|exists:orders,id'
        ]);
        try {
            $result = $this->checkoutService->cancel($validated['id']);
            return response()->json($result);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function complete(Request $request){
        $validated = $request->validate([
            'invoice_number' => 'required|string|exists:orders,invoice_number'
        ]);
        try {
            $result = $this->checkoutService->complete($validated['invoice_number']);
            return response()->json($result);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
