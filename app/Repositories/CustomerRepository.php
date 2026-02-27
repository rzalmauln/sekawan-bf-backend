<?php

namespace App\Repositories;

use App\Models\Customer;

class CustomerRepository
{
    public function create(array $data)
    {
        return Customer::create($data);
    }

    public function update() {}
}
