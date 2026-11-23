<?php

namespace Database\Seeders;
use App\Models\Category;
use Illuminate\Support\Str;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         $names = ['Perabot', 'Literatur Kuliah', 'Elektronik', 'Perlengkapan Harian'];

    foreach ($names as $name) {
        Category::create(['name' => $name, 'slug' => Str::slug($name)]);
    }
    }
}
