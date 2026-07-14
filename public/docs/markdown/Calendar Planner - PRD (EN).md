# Calendar / Planner - PRD

## 1. Summary

Calendar / Planner module for managing schedules, events, to-dos, and reminders in a unified calendar view. Integrates with existing modules (Notes, Payments, Debts) so items from those modules appear on the calendar.

## 2. Goals

- Monthly/weekly/daily calendar view
- Create & manage one-time or recurring events
- To-do list with priority, deadline, and status
- Reminders via database & push notifications
- Integration: show Payments, Debts, Notes deadlines on calendar
- CRUD API for external frontend integration

## 3. User Stories

| ID    | Story                                                                  |
|-------|------------------------------------------------------------------------|
| US-1  | I can view a monthly calendar with events & tasks                      |
| US-2  | I can create events with title, description, time, category            |
| US-3  | I can create recurring events (daily/weekly/monthly/yearly)            |
| US-4  | I can create to-dos with deadline, priority, and checklist             |
| US-5  | I can mark to-dos as complete                                          |
| US-6  | I get database notifications before events/tasks                       |
| US-7  | I can see Payments & Debts deadlines on calendar                       |
| US-8  | I can filter view by category or source module                         |
| US-9  | I can drag & drop events to another date                               |
| US-10 | I can export calendar to iCal format                                   |

## 4. Database Schema

### 4.1. `calendar_events`

Table for one-time and recurring events.

| Column             | Type                 | Constraints                            | Notes                                      |
|--------------------|----------------------|----------------------------------------|--------------------------------------------|
| id                 | bigIncrements        | PK                                     |                                            |
| user_id            | foreignId            | constrained, cascade                   |                                            |
| title              | string(255)          | required                               |                                            |
| description        | text                 | nullable                               |                                            |
| location           | string(255)          | nullable                               |                                            |
| start_at           | datetime             | required                               |                                            |
| end_at             | datetime             | nullable                               | null = no duration set                     |
| is_all_day         | boolean              | default false                          |                                            |
| category_id        | foreignId            | nullable, constrained -> calendar_categories |                                     |
| color              | string(9)            | nullable                               | hex color #RRGGBB                          |
| recurrence_type    | enum                 | nullable                               | daily, weekly, monthly, yearly             |
| recurrence_interval| smallInteger         | default 1, nullable                    | every X days/weeks/months/years            |
| recurrence_end_at  | date                 | nullable                               | when recurring stops                       |
| recurring_event_id | foreignId            | nullable, self-referencing             | parent event for recurring series          |
| source_type        | string(50)           | nullable                               | polymorphic: payment, debt, note           |
| source_id          | bigInteger           | nullable                               | polymorphic ID                              |
| metadata           | json                 | nullable                               | extra data                                  |
| created_at         | timestamp            |                                        |                                            |
| updated_at         | timestamp            |                                        |                                            |
| deleted_at         | timestamp            | nullable                               | soft deletes                                |

Indexes:
- `user_id + start_at` for calendar queries
- `source_type + source_id` for polymorphic lookup

### 4.2. `calendar_categories`

| Column     | Type            | Constraints          | Notes                |
|------------|-----------------|----------------------|----------------------|
| id         | bigIncrements   | PK                   |                      |
| user_id    | foreignId       | constrained, nullable| null = system default|
| name       | string(100)     | required             |                      |
| color      | string(9)       | required             | hex color #RRGGBB    |
| is_default | boolean         | default false        |                      |
| created_at | timestamp       |                      |                      |
| updated_at | timestamp       |                      |                      |

### 4.3. `calendar_todos`

| Column       | Type            | Constraints                        | Notes                       |
|--------------|-----------------|------------------------------------|-----------------------------|
| id           | bigIncrements   | PK                                 |                             |
| user_id      | foreignId       | constrained                        |                             |
| event_id     | foreignId       | nullable, -> calendar_events       | link to event               |
| title        | string(255)     | required                           |                             |
| description  | text            | nullable                           |                             |
| priority     | enum            | required                           | low, medium, high           |
| due_at       | datetime        | nullable                           |                             |
| completed_at | datetime        | nullable                           | null = not completed        |
| sort_order   | smallInteger    | default 0                          |                             |
| created_at   | timestamp       |                                    |                             |
| updated_at   | timestamp       |                                    |                             |
| deleted_at   | timestamp       | nullable                           | soft deletes                |

### 4.4. `calendar_reminders`

| Column      | Type            | Constraints                        | Notes                        |
|-------------|-----------------|------------------------------------|------------------------------|
| id          | bigIncrements   | PK                                 |                              |
| user_id     | foreignId       | constrained                        |                              |
| event_id    | foreignId       | nullable, -> calendar_events       |                              |
| todo_id     | foreignId       | nullable, -> calendar_todos        |                              |
| remind_at   | datetime        | required                           | trigger time                 |
| reminded_at | datetime        | nullable                           | sent time                    |
| created_at  | timestamp       |                                    |                              |
| updated_at  | timestamp       |                                    |                              |

## 5. Models

| Model             | File                             | Notes                                    |
|-------------------|----------------------------------|------------------------------------------|
| CalendarEvent     | app/Models/CalendarEvent.php     | #[ObservedBy([CalendarEventObserver::class])] |
| CalendarCategory  | app/Models/CalendarCategory.php  | #[ObservedBy([CalendarCategoryObserver::class])] |
| CalendarTodo      | app/Models/CalendarTodo.php      | #[ObservedBy([CalendarTodoObserver::class])] |
| CalendarReminder  | app/Models/CalendarReminder.php  | #[ObservedBy([CalendarReminderObserver::class])] |

Relations:
- `CalendarEvent` belongsTo `User`, belongsTo `CalendarCategory` (nullable), morphTo `source`
- `CalendarEvent` hasMany `CalendarTodo`
- `CalendarEvent` hasMany `CalendarReminder`
- `CalendarCategory` belongsTo `User`
- `CalendarTodo` belongsTo `User`, belongsTo `CalendarEvent` (nullable)
- `CalendarReminder` belongsTo `User`, belongsTo `CalendarEvent` (nullable), belongsTo `CalendarTodo` (nullable)

## 6. Enums

| Enum               | File                                  | Values                       |
|--------------------|---------------------------------------|------------------------------|
| RecurrenceType     | app/Enums/RecurrenceType.php          | Daily, Weekly, Monthly, Yearly |
| TodoPriority       | app/Enums/TodoPriority.php            | Low, Medium, High            |
| CalendarSource     | app/Enums/CalendarSource.php          | Payment, Debt, Note, Manual  |

## 7. Observers

| Observer                   | File                                          | Events                             |
|----------------------------|-----------------------------------------------|------------------------------------|
| CalendarEventObserver      | app/Observers/CalendarEventObserver.php       | creating: generate code            |
| CalendarCategoryObserver   | app/Observers/CalendarCategoryObserver.php    | creating: generate code            |
| CalendarTodoObserver       | app/Observers/CalendarTodoObserver.php        | creating: generate code            |
| CalendarReminderObserver   | app/Observers/CalendarReminderObserver.php    | creating, created: send notif      |

- `CalendarReminderObserver::created()` → send Filament database notification
- `CalendarEventObserver::creating()` → generate code via `getCode('calendar_event')`
- `CalendarTodoObserver::creating()` → generate code via `getCode('calendar_todo')`

## 8. Filament Resources

| Resource             | Navigation Group | Icon                           |
|----------------------|------------------|--------------------------------|
| CalendarEventsResource    | Calendar         | Heroicon::OutlinedCalendar     |
| CalendarTodosResource     | Calendar         | Heroicon::OutlinedCheckCircle  |
| CalendarCategoriesResource| Calendar         | Heroicon::OutlinedTag          |

### CalendarEventsResource - Layout

Form:
- Title, Description (RichEditor), Location
- Start at, End at, Is all day
- Category (select), Color (picker)
- Recurrence: type (select), interval, end at
- Source: read-only polymorphic link (if from another module)

Table:
- # (row index)
- Code (copyable)
- Title
- Start at → end at
- All day (icon)
- Category (color badge)
- Recurrence (badge)
- Source (link to origin module)
- Created at
- Updated at

### CalendarTodosResource - Layout

Form:
- Title, Description
- Related event (select, nullable)
- Priority (select enum)
- Due at
- Completed at (toggle, auto-set on check)

Table:
- # (row index)
- Code (copyable)
- Title
- Priority (icon/color badge)
- Due at
- Status (completed icon)
- Event (link)
- Created at

### CalendarCategoriesResource - Layout

Form:
- Name, Color (picker)
- Is default (toggle)

Table:
- Name
- Color (visual swatch)
- Is default (icon)
- Event count

## 9. Relation Managers

- `CalendarEventsResource` → `TodosRelationManager` (reuse `CalendarTodosTable`)
- `CalendarTodosResource` → `EventRelationManager`

## 10. API Routes

File: `routes/api/calendar.php` (require from `routes/api.php`)

```
GET    /api/events                    # index (with ?month=, ?category=, ?source=)
POST   /api/events                    # store
GET    /api/events/{event}            # show
PUT    /api/events/{code}             # update
DELETE /api/events/{code}             # soft delete
DELETE /api/events/{id}/force         # force delete
POST   /api/events/{id}/restore       # restore
PATCH  /api/events/{id}/duplicate     # duplicate event

GET    /api/todos                     # index
POST   /api/todos                     # store
GET    /api/todos/{todo}              # show
PUT    /api/todos/{code}              # update
DELETE /api/todos/{code}              # soft delete
PATCH  /api/todos/{id}/toggle         # toggle completed

GET    /api/calendar/categories       # index
POST   /api/calendar/categories       # store
PUT    /api/calendar/categories/{id}  # update
DELETE /api/calendar/categories/{id}  # delete

GET    /api/calendar/upcoming         # upcoming events+todos (next 7/14/30 days)
GET    /api/calendar/export           # export iCal
```

## 11. Controllers

| Controller                     | File                                                  |
|--------------------------------|-------------------------------------------------------|
| Api\\CalendarEventController   | app/Http/Controllers/Api/CalendarEventController.php  |
| Api\\CalendarTodoController    | app/Http/Controllers/Api/CalendarTodoController.php   |
| Api\\CalendarCategoryController| app/Http/Controllers/Api/CalendarCategoryController.php|

## 12. Services (optional)

| Service                        | File                                           | Notes                          |
|--------------------------------|------------------------------------------------|--------------------------------|
| CalendarRecurrenceService      | app/Services/CalendarRecurrenceService.php     | Generate recurring instances   |
| CalendarReminderService        | app/Services/CalendarReminderService.php       | Schedule & send reminders      |
| CalendarIntegrationService     | app/Services/CalendarIntegrationService.php    | Pull data from Payments, Debts, Notes |

## 13. Integration with Existing Modules

### Payments
- When Payment created, auto-create `CalendarEvent` with `source_type=payment`.
- When Payment deleted, delete related event.

### Debts
- When DebtInstallment created, auto-create `CalendarEvent` with `source_type=debt`.
- Installment deadlines appear on calendar.

### Notes
- Optional: Note with `is_pinned=true` can become event via "Add to calendar" button.
- Notes with dates can appear as all-day events.

Implementation via existing module observers:
- `PaymentObserver::created()` → call `CalendarIntegrationService::sync(payment)`
- `DebtInstallmentObserver::created()` → call `CalendarIntegrationService::sync(installment)`

## 14. Recurring Logic

- `CalendarRecurrenceService::expand($event, $start, $end)`:
  1. Read `recurrence_type`, `interval`, `recurrence_end_at`
  2. Generate virtual instances between `$start` and `$end`
  3. Each instance has `original_start_at` (calculated from pattern)
  4. Instances are virtual (not stored in DB) - cached or calculated at runtime
  5. Override instances (change title/time for one occurrence) stored as `CalendarEvent` with `recurring_event_id` pointing to parent

## 15. Reminder / Notification

- `CalendarReminderService::schedule($event, $minutesBefore)`:
  1. Create `CalendarReminder` record with `remind_at` = `start_at - minutesBefore`
  2. `CalendarReminderObserver::created()` → dispatch `SendCalendarReminderJob`
  3. Job: send Filament database notification + push notification

Default reminder times:
- 15 minutes before
- 30 minutes before
- 1 hour before
- 1 day before (for all-day events)

## 16. Migration Order

1. `create_calendar_categories_table`
2. `create_calendar_events_table`
3. `create_calendar_todos_table`
4. `create_calendar_reminders_table`

## 17. Seeders

- `CalendarCategorySeeder`: default categories (Personal, Work, Health, Finance, Other)
- `CalendarEventSeeder`: sample events for development
- `CalendarTodoSeeder`: sample todos for development

## 18. Custom Filament View

Main Calendar page → use `CalendarWidget` (Livewire component) on dashboard or dedicated page:

- Monthly view (7-column grid)
- Prev/next month navigation
- Click date → see events & todos for that day
- Click event → edit modal
- Drag event → change date (API PATCH)

Implementation details TBD - custom page or widget.

## 19. Implementation Priority

| Phase | Item                                                                |
|-------|---------------------------------------------------------------------|
| 1     | Models + migrations + enums + observers                             |
| 2     | Filament resources (CRUD events, categories, todos)                 |
| 3     | API controllers + routes                                            |
| 4     | Recurring service + integration service (Payments, Debts, Notes)    |
| 5     | Reminder service + notification                                     |
| 6     | Calendar custom page / widget                                       |
| 7     | Export iCal + drag & drop                                           |

## 20. Generate Alias Table

| Alias          | Model            |
|----------------|------------------|
| calendar_event | CalendarEvent    |
| calendar_todo  | CalendarTodo     |

Add seeder for these aliases in `GenerateSeeder` or a separate seeder.

---

## Constraints & Notes

- All models use SoftDeletes
- Use `#[ObservedBy]` attribute, do not register in provider
- 2-space indentation, aligned assignments
- API routes required from `routes/api.php`
- All file uploads via `Storage::disk('public')`
- Timezone: Asia/Jakarta
- Codes via `getCode('alias')`
- Polymorphic relation for cross-module integration
