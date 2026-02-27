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
