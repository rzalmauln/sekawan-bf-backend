<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Catalog extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'slug',
        'is_active'
    ];

    public function items()
    {
        return $this->hasMany(Item::class);
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
