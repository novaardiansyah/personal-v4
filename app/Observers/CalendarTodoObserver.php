<?php

/*
 * Project Name: personal-v4
 * File: CalendarTodoObserver.php
 * Created Date: Monday July 14th 2026
 * 
 * Author: Nova Ardiansyah admin@novaardiansyah.id
 * Website: https://novaardiansyah.id
 * MIT License: https://github.com/novaardiansyah/personal-v4/blob/main/LICENSE
 * 
 * Copyright (c) 2026 Nova Ardiansyah, Org
 */

declare(strict_types=1);

namespace App\Observers;

use App\Models\CalendarReminder;
use App\Models\CalendarTodo;

class CalendarTodoObserver
{
    public function creating(CalendarTodo $todo): void
    {
        if (!$todo->user_id) {
            $todo->user_id = getUser()->id;
        }

        if (!$todo->code) {
            $todo->code = getCode('calendar_todo');
        }
    }

    public function created(CalendarTodo $todo): void
    {
        $this->_log('Created', $todo);
    }

    public function updated(CalendarTodo $todo): void
    {
        $this->_log('Updated', $todo);
    }

    public function deleting(CalendarTodo $todo): void
    {
        CalendarReminder::where('todo_id', $todo->id)->delete();
    }

    public function deleted(CalendarTodo $todo): void
    {
        $this->_log('Deleted', $todo);
    }

    public function restored(CalendarTodo $todo): void
    {
        $this->_log('Restored', $todo);
    }

    public function forceDeleted(CalendarTodo $todo): void
    {
        $this->_log('Force Deleted', $todo);
    }

    private function _log(string $event, CalendarTodo $todo): void
    {
        saveActivityLog([
            'event'        => $event,
            'model'        => 'Calendar Todo',
            'subject_type' => CalendarTodo::class,
            'subject_id'   => $todo->id,
        ], $todo);
    }
}