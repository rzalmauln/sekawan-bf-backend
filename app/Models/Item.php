<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Item extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'catalog_id',
        'name',
        'slug',
        'description',
        'price',
        'stock',
        'type',
        'certificate_path',
        'certificate_password',
        'gaya_main',
        'body',
        'umur',
        'materi',
        'volume',
        'panjang_ekor',
        'warna',
        'warna_kaki',
        'paruh',
        'jenis_kepala',
        'voer',
        'extra_fooding',
        'embun',
        'jemur',
        'mandi',
        'tenggar',
        'krodong_ablak',
        'is_active'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean'
    ];

    public function catalog()
    {
        return $this->belongsTo(Catalog::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['search'] ?? null, function ($q, $search) {
            $q->where('name', 'like', "{$search}%");
        });

        $query->when($filters['sort_by'] ?? 'created_at', function ($q, $sortBy) use ($filters) {
            $direction = $filters['sort_dir'] ?? 'desc';
            $q->orderBy($sortBy, $direction);
        });

        return $query;
    }
}
