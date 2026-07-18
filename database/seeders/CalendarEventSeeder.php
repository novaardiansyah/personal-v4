<?php

namespace Database\Seeders;

use App\Models\CalendarCategory;
use App\Models\CalendarEvent;
use App\Models\User;
use Illuminate\Database\Seeder;

class CalendarEventSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();
        if (! $user) {
            $this->command->error('No user found. Please run UserSeeder first.');

            return;
        }

        $categories = CalendarCategory::all()->keyBy('name');

        $now = now()->startOfMonth();

        $events = [
            [
                'title' => 'Team Standup',
                'description' => 'Daily sync with the development team.',
                'location' => 'Meeting Room A / Zoom',
                'start_at' => $now->copy()->addHours(9),
                'end_at' => $now->copy()->addHours(10),
                'is_all_day' => false,
                'category_id' => $categories['Work']->id ?? null,
                'color' => '#EF4444',
                'recurrence_type' => 'weekly',
                'recurrence_interval' => 1,
                'recurrence_end_at' => $now->copy()->addMonths(3),
            ],
            [
                'title' => 'Gym Session',
                'description' => 'Strength training and cardio.',
                'location' => 'Fitness Center',
                'start_at' => $now->copy()->addDay()->setHour(7)->setMinute(0),
                'end_at' => $now->copy()->addDay()->setHour(8)->setMinute(30),
                'is_all_day' => false,
                'category_id' => $categories['Health']->id ?? null,
                'color' => '#10B981',
                'recurrence_type' => 'weekly',
                'recurrence_interval' => 1,
                'recurrence_end_at' => $now->copy()->addMonths(6),
            ],
            [
                'title' => 'Project Deadline',
                'description' => 'Final submission for Q3 project.',
                'location' => '',
                'start_at' => $now->copy()->addDays(10)->setHour(17)->setMinute(0),
                'end_at' => $now->copy()->addDays(10)->setHour(18)->setMinute(0),
                'is_all_day' => true,
                'category_id' => $categories['Work']->id ?? null,
                'color' => '#EF4444',
                'recurrence_type' => null,
            ],
            [
                'title' => 'Dentist Appointment',
                'description' => 'Regular checkup and cleaning.',
                'location' => 'Dental Clinic Downtown',
                'start_at' => $now->copy()->addDays(5)->setHour(14)->setMinute(0),
                'end_at' => $now->copy()->addDays(5)->setHour(14)->setMinute(45),
                'is_all_day' => false,
                'category_id' => $categories['Health']->id ?? null,
                'color' => '#10B981',
                'recurrence_type' => 'yearly',
                'recurrence_interval' => 1,
                'recurrence_end_at' => $now->copy()->addYears(2),
            ],
            [
                'title' => 'Monthly Budget Review',
                'description' => 'Review expenses and adjust budget for next month.',
                'location' => 'Home Office',
                'start_at' => $now->copy()->endOfMonth()->subDays(2)->setHour(20)->setMinute(0),
                'end_at' => $now->copy()->endOfMonth()->subDays(2)->setHour(21)->setMinute(0),
                'is_all_day' => false,
                'category_id' => $categories['Finance']->id ?? null,
                'color' => '#F59E0B',
                'recurrence_type' => 'monthly',
                'recurrence_interval' => 1,
                'recurrence_end_at' => $now->copy()->addYear(),
            ],
            [
                'title' => 'Weekend Hiking Trip',
                'description' => 'Mount Gede hiking with friends.',
                'location' => 'Mount Gede National Park',
                'start_at' => $now->copy()->addDays(14)->setHour(6)->setMinute(0),
                'end_at' => $now->copy()->addDays(15)->setHour(18)->setMinute(0),
                'is_all_day' => false,
                'category_id' => $categories['Personal']->id ?? null,
                'color' => '#3B82F6',
                'recurrence_type' => null,
            ],
            [
                'title' => 'Client Meeting',
                'description' => 'Quarterly review with ABC Corp.',
                'location' => 'Client Office / Google Meet',
                'start_at' => $now->copy()->addDays(3)->setHour(10)->setMinute(0),
                'end_at' => $now->copy()->addDays(3)->setHour(11)->setMinute(30),
                'is_all_day' => false,
                'category_id' => $categories['Work']->id ?? null,
                'color' => '#EF4444',
                'recurrence_type' => null,
            ],
        ];

        foreach ($events as $eventData) {
            $eventData['user_id'] = $user->id;
            $eventData['code'] = getCode('calendar_event');
            CalendarEvent::create($eventData);
        }

        $this->command->info('Calendar events seeded successfully!');
    }
}
