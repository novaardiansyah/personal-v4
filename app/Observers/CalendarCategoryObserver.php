<?php

/*
 * Project Name: personal-v4
 * File: CalendarCategoryObserver.php
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

use App\Models\CalendarCategory;

class CalendarCategoryObserver
{
    public function creating(CalendarCategory $category): void
    {
        if (!$category->user_id) {
            $category->user_id = getUser()->id;
        }

        if (!$category->code) {
            $category->code = getCode('calendar_category');
        }
    }

    public function created(CalendarCategory $category): void
    {
        $this->_log('Created', $category);
    }

    public function updated(CalendarCategory $category): void
    {
        $this->_log('Updated', $category);
    }

    public function deleting(CalendarCategory $category): void
    {
        $category->events()->update(['category_id' => null]);
    }

    public function deleted(CalendarCategory $category): void
    {
        $this->_log('Deleted', $category);
    }

    public function restored(CalendarCategory $category): void
    {
        $this->_log('Restored', $category);
    }

    public function forceDeleted(CalendarCategory $category): void
    {
        $this->_log('Force Deleted', $category);
    }

    private function _log(string $event, CalendarCategory $category): void
    {
        saveActivityLog([
            'event'        => $event,
            'model'        => 'Calendar Category',
            'subject_type' => CalendarCategory::class,
            'subject_id'   => $category->id,
        ], $category);
    }
}