<?php

namespace App\Services;

use App\Models\Item;
use App\Repositories\ItemRepository;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ItemServices
{
    protected $repository;
    public function __construct(ItemRepository $repository)
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
            if ($file = ($data['certificate_file'] ?? null)) {
                $filename = time()  . '-certificate-' . $data['slug']  . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('certificates', $filename, 'public');
                $data['certificate_path'] = $path;
            }
            return $this->repository->create($data);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function update(Item $item, array $data)
    {
        try {
            $data['slug'] = Str::slug($data['name']);
            if ($file = ($data['certificate_file'] ?? null)) {
                if ($item->certificate_path) {
                    Storage::disk('public')->delete($item->certificate_path);
                }
                $filename = time() . "-certificate-{$data['slug']}." . $file->getClientOriginalExtension();
                $data['certificate_path'] = $file->storeAs('certificates', $filename, 'public');
            }
            return $this->repository->update($item, $data);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function destroy(Item $item)
    {
        try {
            return $this->repository->delete($item);
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
