<?php

namespace App\Repositories;

use App\Models\OrderItem;

class OrderItemRepository
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function create(array $data)
    {
        return OrderItem::create($data);
    }
}
