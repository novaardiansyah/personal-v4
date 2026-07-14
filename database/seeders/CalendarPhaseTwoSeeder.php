<?php

namespace Database\Seeders;

use App\Models\CalendarCategory;
use App\Models\CalendarEvent;
use App\Models\CalendarTodo;
use Illuminate\Database\Seeder;

class CalendarPhaseTwoSeeder extends Seeder
{
  public function run(): void
  {
    $cat1 = CalendarCategory::create(['name' => 'General', 'color' => '#3B82F6', 'is_default' => true]);
    $cat2 = CalendarCategory::create(['name' => 'Work',    'color' => '#EF4444']);
    $cat3 = CalendarCategory::create(['name' => 'Personal','color' => '#10B981']);

    $event1 = CalendarEvent::create([
      'title'       => 'Team Standup',
      'description' => 'Daily sync with the team.',
      'start_at'    => now()->addHours(1),
      'end_at'      => now()->addHours(2),
      'is_all_day'  => false,
      'category_id' => $cat2->id,
      'color'       => '#EF4444',
    ]);

    $event2 = CalendarEvent::create([
      'title'             => 'Gym Session',
      'start_at'          => now()->addDay()->setHour(7)->setMinute(0),
      'end_at'            => now()->addDay()->setHour(8)->setMinute(0),
      'is_all_day'        => false,
      'category_id'       => $cat3->id,
      'color'             => '#10B981',
      'recurrence_type'   => 'weekly',
      'recurrence_interval' => 1,
    ]);

    CalendarEvent::create([
      'title'             => 'Project Deadline',
      'start_at'          => now()->addDays(7),
      'end_at'            => now()->addDays(7)->setHour(17)->setMinute(0),
      'is_all_day'        => true,
      'category_id'       => $cat2->id,
      'color'             => '#EF4444',
    ]);

    CalendarTodo::create([
      'title'       => 'Review PR #42',
      'description' => 'Check code style and tests.',
      'priority'    => 'high',
      'due_at'      => now()->addHours(3),
      'event_id'    => $event1->id,
    ]);

    CalendarTodo::create([
      'title'       => 'Buy groceries',
      'priority'    => 'medium',
      'due_at'      => now()->addDay(),
      'event_id'    => null,
    ]);

    CalendarTodo::create([
      'title'       => 'Read chapter 5',
      'priority'    => 'low',
      'due_at'      => now()->addDays(3),
      'completed_at'=> now(),
    ]);

    $this->command->info('Calendar phase-2 seeded successfully!');
  }
}
