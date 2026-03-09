<?php

namespace Database\Seeders;

use App\Models\Catalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CatalogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $catalogs = [
            [
                'name' => 'Bahorok & Aceh',
                'description' => 'Persilangan Murai Batu Bahorok dan Aceh',
            ],
            [
                'name' => 'Maestro-Bahorok',
                'description' => 'Persilangan Murai Batu Maestro dan Bahorok',
            ],
            [
                'name' => 'Split & Split',
                'description' => 'Persilangan Murai Batu Split',
            ],
            [
                'name' => 'Panda & Panda',
                'description' => 'Persilangan Murai Batu Panda',
            ],
            [
                'name' => 'Bahorok & Blorok',
                'description' => 'Persilangan Murai Batu Bahorok dan Blorok',
            ],
        ];

        foreach ($catalogs as $catalog) {
            Catalog::create([
                'name' => $catalog['name'],
                'slug' => Str::slug($catalog['name']),
                'description' => $catalog['description'],
                'is_active' => true,
            ]);
        }
    }
}
