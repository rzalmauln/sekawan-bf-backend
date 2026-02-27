<?php

namespace App\Repositories;

use App\Models\Item;

class ItemRepository
{
    public function getAll(array $filters = [])
    {
        $perPage = $filters['per_page'] ?? 10;
        return Item::filter($filters)->paginate($perPage);
    }

    public function findById($id)
    {
        return Item::findOrFail($id);
    }

    public function create(array $data)
    {
        return Item::create($data);
    }

    public function update(Item $item, array $data)
    {
        $item->update($data);
        return $item;
    }

    public function delete(Item $item)
    {
        return $item->delete();
    }

    public function findForUpdate($id)
    {
        return Item::lockForUpdate()->findOrFail($id);
    }

    public function decrementStock($item, $qty)
    {
        $item->decrement('stock', $qty);
    }

    public function incrementStock($item, $qty)
    {
        $item->increment('stock', $qty);
    }
}
