<?php

/*
 * Project Name: personal-v4
 * File: CalendarReminderObserver.php
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
use App\Jobs\SendCalendarReminderJob;

class CalendarReminderObserver
{
    public function creating(CalendarReminder $reminder): void
    {
        if (!$reminder->user_id) {
            $reminder->user_id = getUser()->id;
        }

        if (!$reminder->code) {
            $reminder->code = getCode('calendar_reminder');
        }
    }

    public function created(CalendarReminder $reminder): void
    {
        $delay = $reminder->remind_at->diffInSeconds(now());
        if ($delay > 0) {
            SendCalendarReminderJob::dispatch($reminder)->delay(now()->addSeconds($delay));
        } else {
            SendCalendarReminderJob::dispatch($reminder);
        }
        $this->_log('Created', $reminder);
    }

    public function updated(CalendarReminder $reminder): void
    {
        $this->_log('Updated', $reminder);
    }

    public function deleted(CalendarReminder $reminder): void
    {
        $this->_log('Deleted', $reminder);
    }

    public function restored(CalendarReminder $reminder): void
    {
        $this->_log('Restored', $reminder);
    }

    public function forceDeleted(CalendarReminder $reminder): void
    {
        $this->_log('Force Deleted', $reminder);
    }

    private function _log(string $event, CalendarReminder $reminder): void
    {
        saveActivityLog([
            'event'        => $event,
            'model'        => 'Calendar Reminder',
            'subject_type' => CalendarReminder::class,
            'subject_id'   => $reminder->id,
        ], $reminder);
    }
}