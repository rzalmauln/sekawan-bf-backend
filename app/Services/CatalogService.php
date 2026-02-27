<?php

namespace App\Services;

use App\Models\Catalog;
use App\Repositories\CatalogRepository;
use Illuminate\Support\Str;

class CatalogService
{
    protected $repository;

    public function __construct(CatalogRepository $repository)
    {
        $this->repository = $repository;
    }

    public function list(array $params)
    {
        try {
            $filters = [
                'search'   => $params['search'] ?? null,
                'sort_by'  => $params['sort_by'] ?? 'created_at',
                'sort_dir' => $params['sort_dir'] ?? 'desc',
                'per_page' => $params['per_page'] ?? 10,
            ];
            return $this->repository->getAll($filters);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function find($id)
    {
        try {
            return $this->repository->findById($id);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function store(array $data)
    {
        try {
            $data['slug'] = Str::slug($data['name']);
            return $this->repository->create($data);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function update(Catalog $catalog, array $data)
    {
        try {
            $data['slug'] = Str::slug($data['name']);
            return $this->repository->update($catalog, $data);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function destroy(Catalog $catalog)
    {
        try {
            return $this->repository->delete($catalog);
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
