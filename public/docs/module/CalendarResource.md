# Calendar Resource Documentation

*Version: v1.0*  
*Last Updated: July 19, 2026*

## 1. Introduction

The Calendar Resource is a comprehensive event and task management system that tracks appointments, meetings, deadlines, and recurring events. It works as the scheduling backbone for the application, automatically integrating with other resources (Payments, Debts) to keep your financial timeline organized.

**Key distinction from other resources**: While Payment/Subscription/Debt resources handle money, the Calendar Resource handles **time** — when things happen and when they're due. It is the only resource that:
- Supports **recurrence** (daily, weekly, monthly, yearly events)
- Has **MorphTo relationships** to link events to any other resource
- Has **todos** that track action items within events
- Has **categories** with custom colors for visual organization

Perfect for:
- **Event management** - Schedule meetings, appointments, deadlines
- **Task tracking** - Manage action items within events
- **Recurring appointments** - Automatically repeat events on a schedule
- **Cross-resource sync** - Auto-create events from Payments, Subscriptions, Debts

> **New Contributor Tip**: The Calendar Resource has three distinct parts: `CalendarEvent` (the main event), `CalendarTodo` (action items), and `CalendarCategory` (color-coded grouping). The magic happens in `CalendarRecurrenceService` and `CalendarIntegrationService` that handle recurrence and cross-resource integration.

## 2. Architecture Overview

The Calendar Resource follows a comprehensive Filament pattern with four main areas:

**1. Core Resource Components**:
- `CalendarEventResource.php` - Event configuration
- `CalendarTodoResource.php` - Todo/task configuration  
- `CalendarCategoryResource.php` - Category configuration
- Forms, tables, and infolists for each

**2. Relation Managers**:
- `TodosRelationManager` - Attach todos to events
- `EventRelationManager` - Attach events to todos (inverse)

**3. Supporting Components**:
- Models (`app/Models/Calendar*.php`)
- Observers (`app/Observers/Calendar*Observer.php`)
- Services (`app/Services/CalendarRecurrenceService.php`, `app/Services/CalendarIntegrationService.php`)
- Enums (`app/Enums/RecurrenceType.php`, `app/Enums/TodoPriority.php`)

**4. Integration Layer**:
- `CalendarIntegrationService` - Syncs events from other resources
- MorphTo `source` relationship links events to Payments/Subscriptions/Debts

### Key Relationships:
```
CalendarEvent
├── category (BelongsTo → CalendarCategory)
├── source (MorphTo → Payment/Subscription/Debt)
├── todos (HasMany → CalendarTodo)
├── reminders (HasMany → CalendarReminder)
├── recurringEvents (HasMany → CalendarEvent, for parent)
└── recurringEvent (BelongsTo → CalendarEvent, for child)

CalendarTodo
├── event (BelongsTo → CalendarEvent)
└── priority (Cast → TodoPriority enum)

CalendarCategory
├── events (HasMany → CalendarEvent)
└── user (BelongsTo → User)
```

## 3. Core Concepts

### 3.1 Event Lifecycle

| Stage | Description | Key Fields |
|-------|-------------|------------|
| **Created** | Event created manually or synced | `title`, `start_at`, `category_id` |
| **Recurring** | Event repeats on schedule | `recurrence_type`, `recurrence_interval` |
| **Linked** | Event connects to other resources | `source_type`, `source_id` |
| **Todo-ified** | Event contains action items | `todos` relation |
| **Completed** | Event passed (no explicit status) | Date-based completion |

### 3.2 Recurrence Types

| Type | Code | Description | Example |
|------|------|-------------|---------|
| **Daily** | `daily` | Every N days | Every 2 days |
| **Weekly** | `weekly` | Every N weeks | Every 1 week |
| **Monthly** | `monthly` | Every N months | Every 1 month |
| **Yearly** | `yearly` | Every N years | Every 1 year |

**How recurrence works**: The system stores the "parent" event with `recurrence_type` set and `recurring_event_id = null`. Child instances are **not stored** in the database — they are generated on-the-fly by `CalendarRecurrenceService::expand()` when querying events in a date range.

### 3.3 Source Integration (MorphTo)

Events can link to other resources via polymorphic relationship:

```php
// From PaymentObserver
'payment' → CalendarEvent [
    'source_type' => Payment::class,
    'source_id'   => $payment->id,
]
```

**Supported sources**:
- **Payment** - Payment date events (auto-created by PaymentObserver)
- **Subscription** - Subscription billing dates (auto-created by SubscriptionObserver)
- **Debt** - Debt installment due dates (auto-created by DebtInstallmentObserver)

### 3.4 Todo Priority

| Priority | Color | Use Case |
|----------|-------|----------|
| **High** | danger/red | Urgent, deadline-sensitive tasks |
| **Medium** | warning/yellow | Important but not urgent |
| **Low** | gray | Nice-to-have, optional tasks |

### 3.5 Category Colors

Categories have custom colors for visual distinction in calendar views. Each event can have one category (optional).

## 4. Component Deep Dive

### 4.1 CalendarEvent Model

```php
protected $fillable = [
    'user_id',              // Creator
    'title',                // Event name
    'description',          // Rich text details
    'location',             // Physical/virtual location
    'start_at',             // When event starts
    'end_at',               // When event ends (nullable)
    'is_all_day',           // All-day flag
    'category_id',          // Category (optional)
    'color',                // Custom hex color (optional)
    'recurrence_type',      // daily/weekly/monthly/yearly
    'recurrence_interval',  // Number between recurrences
    'recurrence_end_at',    // When recurrence stops
    'recurring_event_id',   // Parent event (for instances)
    'source_type',          // Morph class (Payment/Subscription/Debt)
    'source_id',            // Morph ID
    'metadata',             // JSON data
    'code',                 // Auto-generated event ID
];
```

**Key Relationships**:
- `category()` → BelongsTo CalendarCategory
- `source()` → MorphTo (Payment/Subscription/Debt)
- `todos()` → HasMany CalendarTodo
- `reminders()` → HasMany CalendarReminder
- `recurringEvent()` → BelongsTo self (parent)
- `recurringEvents()` → HasMany self (children)

### 4.2 CalendarTodo Model

```php
protected $fillable = [
    'user_id',          // Creator
    'event_id',         // Parent event (nullable)
    'title',            // Task name
    'description',      // Task details
    'priority',         // high/medium/low (TodoPriority enum)
    'due_at',           // When task is due
    'completed_at',     // When task was completed
    'sort_order',       // Position in list
    'code',             // Auto-generated todo ID
];
```

**Key Relationships**:
- `event()` → BelongsTo CalendarEvent
- `user()` → BelongsTo User

### 4.3 CalendarCategory Model

```php
protected $fillable = [
    'name',        // Category name
    'user_id',     // Owner
    'color',       // Hex color for visual
    'is_default',  // Is this the default category?
    'code',        // Auto-generated category ID
];
```

**Key Relationships**:
- `events()` → HasMany CalendarEvent
- `user()` → BelongsTo User

### 4.4 Form Schemas

#### CalendarEventForm
**Section 1: Event Information** (2-col, full width)
- `title` *required* → Event name
- `location` *default: null* → Where it happens
- `start_at` *required, DateTimePicker* → When it starts
- `end_at` *DateTimePicker, nullable* → When it ends
- `is_all_day` *Toggle, default: false* → All-day event flag
- `category_id` *Select* → Category (relationship, searchable)
- `color` *ColorPicker* → Custom hex color (overrides category)
- `description` *RichEditor* → Full event details

**Section 2: Recurrence** (1-col, narrower)
- `recurrence_type` *Select* → RecurrenceType enum options
- `recurrence_interval` *numeric, min: 1* → Number between recurrences
- `recurrence_end_at` *DateTimePicker* → When to stop recurring

**Section 3: Source** (1-col, visible only if record has source)
- `source_type` *disabled* → Morph class name
- `source_id` *disabled, numeric* → Morph record ID

#### CalendarTodoForm
**Section 1: Todo Information**
- `title` *required* → Task name
- `event_id` *Select* → Parent event (optional)
- `priority` *Select* → high/medium/low (TodoPriority enum)
- `due_at` *DateTimePicker* → When task is due
- `completed_at` *DateTimePicker* → When completed (nullable)
- `sort_order` *numeric* → Position in list
- `description` *Textarea* → Task details

#### CalendarCategoryForm
**Simple form**:
- `name` *required* → Category name
- `color` *ColorPicker* → Category color
- `is_default` *Toggle, default: false* → Is default category?

### 4.5 Table Configurations

#### CalendarEventsTable
**Columns**: `#`, code (searchable, copyable, badge), title (searchable, wrapped, limit 50), **start_end** (computed: "Jan 01, 2026 09:00 → Jan 01, 2026 17:00"), is_all_day (boolean), category (badge with color), recurrence_type (badge), source_link (computed), created_at, updated_at, deleted_at.

**Filters**: Category (relationship), Trashed.

**Actions**: View, Edit, Delete, ForceDelete, Restore (bulk).

**Default sort**: `start_at` desc.

#### CalendarTodosTable
**Columns**: `#`, code (searchable, copyable, badge), title (searchable, sortable), event (relationship title), priority (badge), due_at (date with since tooltip), completed_at (datetime), sort_order.

**Actions**: View, Edit, Delete (bulk).

**Default sort**: `sort_order` asc.

#### CalendarCategoriesTable
**Columns**: `#`, code (searchable, copyable, badge), name (searchable), color (badge), is_default (boolean).

**Actions**: View, Edit, Delete (bulk).

**Default sort**: `name` asc.

### 4.6 Relation Managers

#### TodosRelationManager (on CalendarEventResource)
Shows all todos attached to the current event. Table includes: `#`, code, title, event (hidden), priority (badge), due_at, completed_at, sort_order.

**Actions per row**: View, Edit, Pay (from DebtInstallment resource context, probably not applicable here).

#### EventRelationManager (on CalendarTodoResource)
Shows all events attached to the current todo. Table includes: `#`, code, title, start_end, category (badge), recurrence (badge), created_at, updated_at.

**Actions per row**: View, Edit.

### 4.7 Pages

#### ListCalendarEvents
Standard Filament list with Create header action. No custom tabs or bulk actions beyond defaults.

#### CreateCalendarEvent
**Special Features**:
- `afterFill()` → Reads `start_at` from query string to pre-fill the start date
- `getRedirectUrl()` → Redirects to `/admin/calendar` (calendar dashboard) after creation

#### EditCalendarEvent
**Header actions**: View, Delete, ForceDelete, Restore.

#### ViewCalendarEvent
**Header actions**: Edit, Delete, ForceDelete, Restore.

#### ListCalendarTodos
Standard Filament list with Create header action.

#### CreateCalendarTodo
Standard create, redirects to todo list.

#### EditCalendarTodo
**Header actions**: View, Delete, ForceDelete, Restore.

#### ViewCalendarTodo
**Header actions**: Edit, Delete, ForceDelete, Restore.

#### ManageCalendarCategories
Simple management page (Create/Edit only, no View page).

## 5. Key Services

### 5.1 CalendarRecurrenceService

**Purpose**: Generates event instances from recurring events.

**Key Methods**:
```php
expand(CalendarEvent $event, Carbon $start, Carbon $end): Collection
// Generates all occurrences of a recurring event within a date range

generateInstances(CalendarEvent $event, Carbon $start, Carbon $end): Collection
// Core recurrence expansion logic
```

**How it works**:
1. Checks `recurrence_type` (daily/weekly/monthly/yearly)
2. Applies `recurrence_interval` between occurrences
3. Stops at `recurrence_end_at` (or never if null)
4. Returns virtual instances (not stored in DB)

### 5.2 CalendarIntegrationService

**Purpose**: Syncs events from other resources.

**Key Methods**:
```php
syncFromPayment(Payment $payment): void
// Creates/updates calendar event for payment date

syncFromSubscription(Subscription $subscription): void  
// Creates/updates calendar event for subscription next_date

syncFromDebtInstallment(DebtInstallment $installment): void
// Creates/updates calendar event for installment due_date

removeSource(string $type, int $id): void
// Removes calendar event linked to a source record
```

**Integration Points**:
- `PaymentObserver::created` → `syncFromPayment()`
- `SubscriptionObserver::created` → `syncFromSubscription()`
- `DebtInstallmentObserver::created/updated` → `syncFromDebtInstallment()`
- `DebtInstallmentObserver::deleted` → `removeSource('debt', $installment->id)`

## 6. Data Flow Examples

### 6.1 Creating a Manual Event

**Scenario**: Schedule a team meeting for Friday.

**Flow**:
1. Create → Fill form: title "Team Meeting", start_at "2026-07-25 09:00", end_at "2026-07-25 10:00", category "Work"
2. Observer auto-generates code `LCE-20260719-0001`
3. Save → Event appears in calendar and events list
4. Redirect to `/admin/calendar` (calendar dashboard)

### 6.2 Creating a Recurring Event

**Scenario**: Weekly standup every Monday at 9am.

**Flow**:
1. Create → Fill: title "Standup", start_at "2026-07-21 09:00", recurrence_type "weekly", recurrence_interval 1
2. System stores ONE event record with `recurrence_type = weekly`
3. When querying events in a date range, `CalendarRecurrenceService::expand()` generates virtual instances
4. No child records in DB — saves storage, computes on-the-fly

### 6.3 Payment Creates Calendar Event

**Scenario**: User creates a Payment for 2026-08-01.

**Flow**:
1. `PaymentObserver::created` fires
2. `CalendarIntegrationService::syncFromPayment()` called
3. Creates CalendarEvent with:
   - title: "Payment: {payment_name}"
   - start_at: payment.date
   - source_type: Payment::class
   - source_id: payment.id
4. Event appears in calendar, linked to payment

### 6.4 Debt Installment Creates Calendar Event

**Scenario**: Debt with installments on 1st of each month.

**Flow**:
1. `DebtInstallmentObserver::created` fires
2. `CalendarIntegrationService::syncFromDebtInstallment()` called
3. Creates CalendarEvent with:
   - title: "Cicilan ke-{number}: {debt_name}"
   - start_at: installment.due_date
   - source_type: DebtInstallment::class
   - source_id: installment.id
4. User sees installment deadlines in calendar
5. If installment due_date changes, `updated` fires and event syncs again

## 7. Key Patterns & Tips

### 7.1 Code Generation

```php
// In CalendarEventObserver
$event->code = getCode('calendar_event');
// Pattern: PREFIX + DATE + SEQUENCE
// Example: LCE-20260719-0001
```

### 7.2 Source Linking Pattern

```php
// CalendarEvent source relationship
public function source(): MorphTo
{
    return $this->morphTo('source', 'source_type', 'source_id');
}

// Usage in form
TextInput::make('source_type')
    ->label('Source Type')
    ->disabled()
    ->dehydrated(),  // Still saves even though disabled
```

### 7.3 Recurrence Expansion

```php
// CalendarEvent scope
public function scopeGetEventsInRange(Builder $query, Carbon $start, Carbon $end)
{
    // 1. Get non-recurring events in range
    // 2. Get all recurring parents
    // 3. Expand each parent within range
    // 4. Merge and sort by start_at
}
```

### 7.4 Color Override

```php
// In table column
TextColumn::make('category.name')
    ->color(fn($record) => $record->category?->color ?? 'gray')
    // Category color is default, but event.color can override
```

### 7.5 Todo Sorting

```php
// Sort by custom order
->defaultSort('sort_order', 'asc')
// User controls position via sort_order field
```

### 7.6 Query String Pre-fill

```php
// CreateCalendarEvent
protected function afterFill(): void
{
    if ($startAt = request()->query('start_at')) {
        $this->data['start_at'] = $startAt;
    }
}
// Calendar view can link to create form with pre-filled date
```

## 8. Common Use Cases

### 8.1 Schedule a Meeting
1. Create event with title, start/end times
2. Assign category "Meetings"
3. Add todos for action items (e.g., "Prepare presentation")
4. Set color for visual distinction

### 8.2 Track Subscription Renewals
1. Subscription is created with next_date
2. CalendarIntegrationService creates event
3. User sees renewal in calendar
4. When MarkAsPaid advances next_date, event syncs

### 8.3 Manage Debt Deadlines
1. Debt installments have due_dates
2. CalendarIntegrationService creates events
3. User sees all deadlines in calendar view
4. Color-code by debt type or urgency

### 8.4 Create Recurring Weekly Tasks
1. Create event with recurrence_type "weekly"
2. Set recurrence_interval = 1
3. System generates instances on-the-fly
4. No database bloat from thousands of records

## 9. Comparison with Other Resources

| Aspect | Calendar | Payment | Subscription | Debt |
|--------|----------|---------|--------------|------|
| **Primary Focus** | Time scheduling | Financial transactions | Recurring payments | Liability tracking |
| **Recurrence** | Native support | No | No (manual) | No (installments) |
| **Integration** | Receives from others | Sends to calendar | Sends to calendar | Sends to calendar |
| **Child Records** | Todos | Items, Galleries | None | Installments |
| **Source Linking** | MorphTo (receives) | None | None | None |

## 10. Troubleshooting

### 10.1 Recurring Event Not Showing in Range

**Cause**: `recurrence_end_at` passed before the query range.
**Solution**: Extend `recurrence_end_at` or remove it to make recurrence indefinite.

### 10.2 Calendar Event Not Created from Payment

**Cause**: `PaymentObserver` not firing or `CalendarIntegrationService` exception.
**Solution**: Check observer registration and logs; ensure Payment has `date` field.

### 10.3 Todo Not Sorting Correctly

**Cause**: `sort_order` field not set or null.
**Solution**: Ensure all todos have numeric `sort_order`; default sort is `sort_order asc`.

### 10.4 Color Not Appearing

**Cause**: Hex color invalid or category color not set.
**Solution**: Verify hex format (e.g., `#FF5733`); check category has color.

## 11. Testing Guidelines

**Manual Testing**:
- Create event with recurrence; verify instances generate correctly
- Create event linked to Payment; verify source fields populate
- Change Payment date; verify calendar event syncs
- Delete DebtInstallment; verify calendar event removes
- Create todo with high priority; verify badge shows red
- Filter events by category; verify only matching events show
- Test all-day events vs timed events
- Verify soft delete and restore behavior

**Integration Testing**:
- Create Payment → Calendar event created
- Update Payment date → Calendar event updates
- Delete Payment → Calendar event remains (source deleted but event persists?)

---

*Copyright © 2026 Nova Ardiansyah*  
*Website: [https://novaardiansyah.id](https://novaardiansyah.id)*  
*Email: [admin@novaardiansyah.id](mailto:admin@novaardiansyah.id)*  
*Phone: [0822 6111 1084](https://wa.me/6282261111084)*
