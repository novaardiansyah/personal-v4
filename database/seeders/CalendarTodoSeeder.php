<?php

namespace Database\Seeders;

use App\Models\CalendarEvent;
use App\Models\CalendarTodo;
use App\Models\User;
use Illuminate\Database\Seeder;

class CalendarTodoSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();
        if (! $user) {
            $this->command->error('No user found. Please run UserSeeder first.');

            return;
        }

        $events = CalendarEvent::where('user_id', $user->id)->get()->keyBy('title');

        $now = now()->startOfMonth();

        $todos = [
            [
                'title' => 'Prepare standup notes',
                'description' => 'Review yesterday\'s work and plan today\'s tasks.',
                'priority' => 'high',
                'due_at' => $now->copy()->addHours(8),
                'event_id' => $events['Team Standup']->id ?? null,
                'completed_at' => null,
                'sort_order' => 1,
            ],
            [
                'title' => 'Submit PR for authentication module',
                'description' => 'Code review needed before merging to main.',
                'priority' => 'high',
                'due_at' => $now->copy()->addHours(4),
                'event_id' => $events['Project Deadline']->id ?? null,
                'completed_at' => null,
                'sort_order' => 2,
            ],
            [
                'title' => 'Buy protein powder',
                'description' => 'Running low on supplements.',
                'priority' => 'medium',
                'due_at' => $now->copy()->addDay(),
                'event_id' => null,
                'completed_at' => null,
                'sort_order' => 3,
            ],
            [
                'title' => 'Schedule dentist follow-up',
                'description' => 'Book appointment for next cleaning.',
                'priority' => 'low',
                'due_at' => $now->copy()->addDays(7),
                'event_id' => $events['Dentist Appointment']->id ?? null,
                'completed_at' => null,
                'sort_order' => 4,
            ],
            [
                'title' => 'Review monthly expenses',
                'description' => 'Categorize transactions and update budget spreadsheet.',
                'priority' => 'medium',
                'due_at' => $now->copy()->endOfMonth()->subDays(3),
                'event_id' => $events['Monthly Budget Review']->id ?? null,
                'completed_at' => null,
                'sort_order' => 5,
            ],
            [
                'title' => 'Pack hiking gear',
                'description' => 'Backpack, water, snacks, first aid kit.',
                'priority' => 'high',
                'due_at' => $now->copy()->addDays(13)->setHour(18)->setMinute(0),
                'event_id' => $events['Weekend Hiking Trip']->id ?? null,
                'completed_at' => null,
                'sort_order' => 6,
            ],
            [
                'title' => 'Read "Clean Code" Chapter 3',
                'description' => 'Functions chapter - best practices.',
                'priority' => 'low',
                'due_at' => $now->copy()->addDays(5),
                'event_id' => null,
                'completed_at' => $now->copy()->subDay(),
                'sort_order' => 7,
            ],
            [
                'title' => 'Send Q3 report to client',
                'description' => 'Attach financial summary and project timeline.',
                'priority' => 'high',
                'due_at' => $now->copy()->addDays(2)->setHour(16)->setMinute(0),
                'event_id' => $events['Client Meeting']->id ?? null,
                'completed_at' => null,
                'sort_order' => 8,
            ],
            [
                'title' => 'Call insurance provider',
                'description' => 'Update policy details and confirm coverage.',
                'priority' => 'medium',
                'due_at' => $now->copy()->addDays(3),
                'event_id' => null,
                'completed_at' => $now->copy()->subDays(2),
                'sort_order' => 9,
            ],
            [
                'title' => 'Backup important files',
                'description' => 'Sync documents to cloud storage.',
                'priority' => 'low',
                'due_at' => $now->copy()->addDays(1),
                'event_id' => null,
                'completed_at' => null,
                'sort_order' => 10,
            ],
        ];

        foreach ($todos as $todoData) {
            $todoData['user_id'] = $user->id;
            $todoData['code'] = getCode('calendar_todo');
            CalendarTodo::create($todoData);
        }

        $this->command->info('Calendar todos seeded successfully!');
    }
}
