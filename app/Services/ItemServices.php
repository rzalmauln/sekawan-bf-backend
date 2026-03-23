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
            $data = $this->storeItemFiles($data);

            return $this->repository->create($data);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function update(Item $item, array $data)
    {
        try {
            $data['slug'] = Str::slug($data['name']);
            $data = $this->storeItemFiles($data, $item);

            return $this->repository->update($item, $data);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function destroy(Item $item)
    {
        try {
            $this->deleteItemFiles($item);

            return $this->repository->delete($item);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function checkPasswordCertificate(int $itemId, string $password): array
    {
        try {
            $item = $this->repository->findById($itemId);

            if ($item->certificate_password === null || $item->certificate_password === '') {
                throw new \Exception('Password sertifikat belum diatur');
            }

            if ($item->certificate_password !== $password) {
                throw new \Exception('Password sertifikat tidak sesuai');
            }

            return [
                'message' => 'Password sertifikat sesuai',
                'certificate_url' => $item->certificate_path
                    ? asset('storage/' . $item->certificate_path)
                    : null,
            ];
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    private function storeItemFiles(array $data, ?Item $item = null): array
    {
        $fileMappings = [
            'certificate_path' => 'certificates',
            'image_path' => 'items/images',
            'video_path' => 'items/videos',
        ];

        foreach ($fileMappings as $field => $directory) {
            $file = $data[$field] ?? null;

            if (!$file instanceof \Illuminate\Http\UploadedFile) {
                continue;
            }

            if ($item && $item->{$field}) {
                Storage::disk('public')->delete($item->{$field});
            }

            $filename = time() . '-' . str_replace('_path', '', $field) . '-' . $data['slug'] . '.' . $file->getClientOriginalExtension();
            $data[$field] = $file->storeAs($directory, $filename, 'public');
        }

        return $data;
    }

    private function deleteItemFiles(Item $item): void
    {
        foreach (['certificate_path', 'image_path', 'video_path'] as $field) {
            if ($item->{$field}) {
                Storage::disk('public')->delete($item->{$field});
            }
        }
    }
}
