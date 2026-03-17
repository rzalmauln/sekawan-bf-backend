<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Repositories\OrderRepository;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        protected OrderRepository $orderRepository
    ) {}

    public function index(Request $request)
    {
        $validated = $request->validate([
            'status' => 'nullable|string|in:pending,paid,packed,shipped,completed,cancelled',
            'search' => 'nullable|string',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $orders = $this->orderRepository->paginateForAdmin(
            $validated,
            $validated['per_page'] ?? 15
        );

        return OrderResource::collection($orders);
    }
}
