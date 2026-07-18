<?php

namespace Database\Seeders;

use App\Models\CalendarCategory;
use Illuminate\Database\Seeder;

class CalendarCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Personal', 'color' => '#3B82F6', 'is_default' => true],
            ['name' => 'Work',     'color' => '#EF4444', 'is_default' => false],
            ['name' => 'Health',   'color' => '#10B981', 'is_default' => false],
            ['name' => 'Finance',  'color' => '#F59E0B', 'is_default' => false],
            ['name' => 'Other',    'color' => '#8B5CF6', 'is_default' => false],
        ];

        foreach ($categories as $category) {
            CalendarCategory::create($category);
        }

        $this->command->info('Calendar categories seeded successfully!');
    }
}
