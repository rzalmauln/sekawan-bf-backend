<?php

namespace App\Repositories;

use App\Models\Catalog;

class CatalogRepository
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function getAll(array $filters = [])
    {
        $perPage = $filters['per_page'] ?? 10;
        return Catalog::filter($filters)->paginate($perPage);
    }

    public function findById($id)
    {
        return Catalog::findOrFail($id);
    }

    public function create(array $data)
    {
        return Catalog::create($data);
    }

    public function update(Catalog $catalog, array $data)
    {
        $catalog->update($data);
        return $catalog;
    }

    public function delete(Catalog $catalog)
    {
        return $catalog->delete();
    }
}
