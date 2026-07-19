---
goal: Subscription Tracker — Track recurring subscriptions (Netflix, Spotify, etc.) with reminders
version: 2.0
date_created: 2026-07-19
last_updated: 2026-07-19
status: Planned
tags: feature, subscription, recurring, payment
---

# Introduction

![Status: Planned](https://img.shields.io/badge/status-Planned-blue)

Subscription Tracker lets users track recurring subscription payments (Netflix, Spotify, etc.) with auto-reminder before billing date. Uses a dedicated `subscriptions` table (separate from `payments`). When a subscription is paid, a `Payment` record is created with the transaction details. Reminders run daily at 05:00 via scheduled job in `console.php` (Laravel 12) and send both Telegram and Email notifications.

---

## 1. Requirements & Constraints

- **REQ-001**: Users can create a subscription with name, amount, payment account, category, billing cycle (monthly/quarterly/yearly), next billing date, and reminder days before.
- **REQ-002**: Subscriptions stored in dedicated `subscriptions` table — independent of `payments` table.
- **REQ-003**: Scheduled queue job runs daily at 05:00, checks subscriptions with `next_date` within `reminder_days_before`, dispatches reminders via Telegram and Email.
- **REQ-004**: Admin panel shows upcoming subscriptions (next 7 days) in dedicated `SubscriptionResource`.
- **REQ-005**: Subscriptions can be paused/resumed without deleting.
- **REQ-006**: When a subscription is marked as paid, a regular `Payment` record is created (expense type), `next_date` advances by cycle period, and subscription code is generated via `getCode('subscription')`.
- **CON-001**: New `subscriptions` table via migration — no modifications to existing `payments` table.
- **CON-002**: Follow existing Filament resource pattern (`Schemas/`, `Tables/`, `Pages/`, `Actions/`).
- **CON-003**: Use existing notification channels (Telegram, Email, Database).
- **GUD-001**: Follow existing code style — 2 spaces, no comments, aligned assignments.
- **PAT-001**: Follow `PaymentResource` pattern for the new `SubscriptionResource`.

---

## 2. Implementation Steps

### Implementation Phase 1 — Database, Model & Filament Resource

- **GOAL-001**: Create `subscriptions` table, `Subscription` model with observer, and full Filament CRUD resource.

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-001 | Create migration for `subscriptions` table: id, user_id, code, name, amount, payment_account_id, category_id, cycle (enum: monthly/quarterly/yearly), next_date, reminder_days_before (default 3), is_paused (default false), last_reminded_at, timestamps, softDeletes | Yes | 2026-07-18 |
| TASK-002 | Create `Subscription` model with fillable, casts, relationships to `User`, `PaymentAccount`, `PaymentCategory`, add `#[ObservedBy]` attribute | Yes | 2026-07-18 |
| TASK-003 | Create `SubscriptionObserver` with `created`/`updated` hooks for `code` generation via `getCode('subscription')` | Yes | 2026-07-18 |
| TASK-004 | Create `SubscriptionResource` with navigation icon, group 'Payments', sort after `PaymentResource` | Yes | 2026-07-18 |
| TASK-005 | Create `Schemas/SubscriptionForm.php`: name, amount, payment_account, category, cycle select, next_date, reminder_days_before, is_paused toggle — reuse `PaymentForm` patterns | Yes | 2026-07-18 |
| TASK-006 | Create `Tables/SubscriptionsTable.php`: name, amount, next_date, cycle, is_paused, category, payment_account columns, actions (view/edit/delete/pause/resume/mark-as-paid) | Yes | 2026-07-18 |
| TASK-007 | Create `Pages/ListSubscriptions.php`, `CreateSubscription.php`, `EditSubscription.php` — follow `PaymentResource` page pattern | Yes | 2026-07-18 |
| TASK-008 | Create `Actions/PauseResumeAction.php` — toggles `is_paused` column | Yes | 2026-07-18 |
| TASK-009 | Create `Actions/MarkAsPaidAction.php` — creates `Payment` record (expense type), advances `next_date` by cycle, generates subscription code via `getCode('subscription')` | Yes | 2026-07-18 |
| TASK-010 | Create filter for active/paused/all subscriptions | Yes | 2026-07-18 |

### Implementation Phase 2 — Scheduled Reminder Job

- **GOAL-002**: Auto-reminder queue job runs at 05:00 daily, sends Telegram + Email.

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-011 | Create `Jobs/SubscriptionReminderJob.php` — queries active subscriptions where `next_date` within `reminder_days_before` and `last_reminded_at` is null or before current cycle, dispatches notification | Yes | 2026-07-19 |
| TASK-012 | Register job in `bootstrap/app.php` or `routes/console.php` schedule — runs daily at 05:00 | Yes | 2026-07-19 |
| TASK-013 | Create notification class using existing Telegram + Email channels (reuse `sendTelegramNotification` + Mail) | Yes | 2026-07-19 |
| TASK-014 | Update `last_reminded_at` on subscription after successful notification dispatch | Yes | 2026-07-19 |

### Implementation Phase 3 — Dashboard Widget

- **GOAL-003**: Show upcoming subscriptions on the admin dashboard.

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-015 | Create dashboard widget `UpcomingSubscriptionsWidget` listing subscriptions due within 7 days | | |

---

## 3. Alternatives

- **ALT-001**: Extend existing `payments` table — rejected per revision decision; separate table keeps subscription metadata clean and avoids polluting payment queries.
- **ALT-002**: Reuse `is_scheduled` on `payments` — rejected; lacks cycle logic, pause/resume, and proactive reminders.
- **ALT-003**: Use existing scheduled payments — rejected; they are one-time scheduled, not recurring with cycle management.

---

## 4. Dependencies

- **DEP-001**: Existing `PaymentCategory` model (subscriptions use same categories)
- **DEP-002**: Existing `PaymentAccount` model (subscriptions use same accounts)
- **DEP-003**: Existing `sendTelegramNotification()` helper
- **DEP-004**: Existing code generation `getCode('subscription')` — new alias needs to be added to `generates` table seeder
- **DEP-005**: Existing Mail configuration for Email notifications
- **DEP-006**: Existing `Payment` model — MarkAsPaid creates expense payment from subscription data

---

## 5. Files

- **FILE-001**: `database/migrations/YYYY_MM_DD_HHMMSS_create_subscriptions_table.php`
- **FILE-002**: `app/Models/Subscription.php`
- **FILE-003**: `app/Observers/SubscriptionObserver.php`
- **FILE-004**: `database/seeders/GenerateSeeder.php` — add 'subscription' alias for code generation
- **FILE-005**: `app/Filament/Resources/Subscriptions/SubscriptionResource.php`
- **FILE-006**: `app/Filament/Resources/Subscriptions/Schemas/SubscriptionForm.php`
- **FILE-007**: `app/Filament/Resources/Subscriptions/Tables/SubscriptionsTable.php`
- **FILE-008**: `app/Filament/Resources/Subscriptions/Pages/ListSubscriptions.php`
- **FILE-009**: `app/Filament/Resources/Subscriptions/Pages/CreateSubscription.php`
- **FILE-010**: `app/Filament/Resources/Subscriptions/Pages/EditSubscription.php`
- **FILE-011**: `app/Filament/Resources/Subscriptions/Actions/PauseResumeAction.php`
- **FILE-012**: `app/Filament/Resources/Subscriptions/Actions/MarkAsPaidAction.php`
- **FILE-013**: `app/Jobs/SubscriptionReminderJob.php`
- **FILE-014**: `app/Notifications/SubscriptionReminderNotification.php`
- **FILE-015**: `routes/console.php` — schedule job at 05:00 daily
- **FILE-016**: `app/Filament/Widgets/UpcomingSubscriptionsWidget.php`

---

## 6. Testing

- Testing will be performed manually by the user. No automated unit/feature tests required.

---

## 7. Risks & Assumptions

- **RISK-001**: Separate `subscriptions` table means subscription data is not directly visible in existing Payment reports — may need a combined view in future.
- **RISK-002**: Queue driver is `database` — `SubscriptionReminderJob` must use `$tries=3` and `backoff` to avoid infinite retries on notification failure.
- **RISK-003**: Laravel 12 uses `routes/console.php` for scheduling — ensure compatibility with existing queue worker (`composer dev` runs `queue:listen`).
- **ASSUMPTION-001**: All subscriptions are expense-type (type_id=1). Matches existing `is_scheduled` expense patterns.
- **ASSUMPTION-002**: Reminder sends once per billing period — job uses `last_reminded_at` to prevent duplicate notifications.
- **ASSUMPTION-003**: Email notification uses existing Mail setup — no new mailable class needed if reusing generic notification template.

---

## 8. Related Specifications / Further Reading

- `app/Filament/Resources/Payments/` — Reference implementation for all payment resource patterns.
- `routes/console.php` — Laravel 12 scheduling (replaces Kernel.php).