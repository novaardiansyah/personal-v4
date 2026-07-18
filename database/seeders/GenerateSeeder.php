<?php

namespace Database\Seeders;

use App\Models\Generate;
use Illuminate\Database\Seeder;

class GenerateSeeder extends Seeder
{
    public function run(): void
    {
        $generates = [
            ['name' => 'Calendar Event',  'alias' => 'calendar_event', 'prefix' => 'CEV-', 'suffix' => ''],
            ['name' => 'Calendar Todo',   'alias' => 'calendar_todo',  'prefix' => 'CTD-', 'suffix' => ''],
            ['name' => 'Subscription',    'alias' => 'subscription',   'prefix' => 'SUB-', 'suffix' => ''],
        ];

        foreach ($generates as $generate) {
            Generate::updateOrCreate(
                ['alias' => $generate['alias']],
                $generate
            );
        }
    }
}
