<?php

/*
 * Project Name: personal-v4
 * File: CalendarEventObserver.php
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

use App\Models\CalendarEvent;

class CalendarEventObserver
{
    public function creating(CalendarEvent $event): void
    {
        if (!$event->user_id) {
            $event->user_id = getUser()->id;
        }

        if (!$event->code) {
            $event->code = getCode('calendar_event');
        }
    }

    public function created(CalendarEvent $event): void
    {
        $this->_log('Created', $event);
    }

    public function updated(CalendarEvent $event): void
    {
        $this->_log('Updated', $event);
    }

    public function deleting(CalendarEvent $event): void
    {
        $event->recurringEvents()->update(['recurring_event_id' => null]);
        $event->todos()->update(['event_id' => null]);
        $event->reminders()->delete();
    }

    public function deleted(CalendarEvent $event): void
    {
        $this->_log('Deleted', $event);
    }

    public function restored(CalendarEvent $event): void
    {
        $this->_log('Restored', $event);
    }

    public function forceDeleted(CalendarEvent $event): void
    {
        $this->_log('Force Deleted', $event);
    }

    private function _log(string $event, CalendarEvent $calendarEvent): void
    {
        saveActivityLog([
            'event'        => $event,
            'model'        => 'Calendar Event',
            'subject_type' => CalendarEvent::class,
            'subject_id'   => $calendarEvent->id,
        ], $calendarEvent);
    }
}