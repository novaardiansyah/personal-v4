# Subscription Resource Documentation

*Version: v1.0*  
*Last Updated: July 19, 2026*

## 1. Introduction

The Subscription Resource manages recurring financial obligations — regular payments that happen on a monthly, quarterly, or yearly cycle. Think of it as your automated bill manager: Netflix, Spotify, cloud hosting, insurance premiums, gym memberships, or any subscription-based expense.

**Key distinction from Payment Resource**: While the Payment Resource handles individual financial transactions, the Subscription Resource manages the **recurring pattern** — defining what, when, and how much to pay on a repeating schedule.

Perfect for:
- **Recurring bills** - Automate tracking of monthly/quarterly/yearly expenses
- **Budget forecasting** - Know exactly what payments are coming up
- **Payment automation** - Mark subscriptions as paid and auto-advance dates
- **Reminder management** - Get notified before payment is due

> **New Contributor Tip**: The Subscription Resource is the simplest resource in the Payments group. It focuses on managing the subscription lifecycle (create → pause/resume → mark as paid) without complex balance mutations found in the Payment Resource.

## 2. Architecture Overview

The Subscription Resource follows a focused Filament pattern with three main areas:

**1. Core Resource Components** (`app/Filament/Resources/Subscriptions/`):
- `SubscriptionResource.php` - Resource configuration
- `SubscriptionForm.php` - Form schema for data entry
- `SubscriptionsTable.php` - Table configuration and filters
- Pages for list, create, and edit

**2. Actions** (`app/Filament/Resources/Subscriptions/Actions/`):
- `MarkAsPaidAction` - Create a payment from subscription
- `PauseResumeAction` - Toggle subscription active/paused

**3. Supporting Components**:
- Model (`app/Models/Subscription.php`)
- Observer (`app/Observers/SubscriptionObserver.php`)
- Filter (`app/Filament/Resources/Subscriptions/Filters/SubscriptionsFilter.php`)

### Key Relationships:
```
Subscription
├── payment_account (One → Many for payment source)
└── category (One → Many budget categories)
```

No relation managers — subscriptions are independent records.

## 3. Core Concepts

### 3.1 Billing Cycles

| Cycle | Frequency | Advance Calculation |
|-------|-----------|-------------------|
| **Monthly** | Every 1 month | `addMonth()` |
| **Quarterly** | Every 3 months | `addMonths(3)` |
| **Yearly** | Every 12 months | `addYear()` |

### 3.2 Subscription States

| State | Code | Description |
|-------|------|-------------|
| **Active** | `is_paused: false` | Subscription is active, payments tracked normally |
| **Paused** | `is_paused: true` | Subscription temporarily suspended, no payment creation |
| **Paid** | - | Marked via MarkAsPaid action, next_date advances |

### 3.3 Relationship to Payments

When a subscription is marked as paid, the system creates a **Payment record** with:
- Type: **Expense**
- Name: `"Subscription: {subscription_name}"`
- Amount: The subscription amount
- Account: The subscription's payment account

This creates an audit trail: every payment made for a subscription is recorded in the Payment Resource.

## 4. Component Deep Dive

### 4.1 Model & Relationships

The Subscription model tracks recurring payment definitions:

```php
protected $fillable = [
    'user_id',            // Creator/owner
    'code',               // Auto-generated subscription ID
    'name',               // Subscription name (e.g., "Netflix")
    'amount',             // Recurring amount
    'payment_account_id', // Payment source account
    'category_id',        // Budget category (nullable)
    'cycle',              // monthly | quarterly | yearly
    'next_date',          // Next scheduled payment date
    'reminder_days_before', // Days before next_date to remind (default: 3)
    'is_paused',           // Paused/resumed status
    'last_reminded_at',    // Last reminder sent timestamp
];

// Casts for type safety
$amount               => integer
$is_paused            => boolean
$reminder_days_before => integer
$next_date            => date (Carbon)
$last_reminded_at     => datetime (Carbon)
```

**Default eager loads** (`$with`):
- `payment_account` — Always loaded for display
- `category` — Always loaded for display

**Essential Relationships**:
- `payment_account()` → Belongs to payment source (scoped to user)
- `category()` → Belongs to budget category

### 4.2 Form Schema

The SubscriptionForm handles data entry with these fields:

**Fields inside a single Section**:
- **Name** *required* → Subscription name (full width)
- **Amount** *required, numeric* → Recurring payment amount, auto-formatted to Indonesian currency
- **Payment Account** *required* → Source account with live balance hint
- **Category** *nullable* → Budget category, pre-selected to user's default
- **Cycle** *required* → monthly/quarterly/yearly selector
- **Next Date** *required* → Date picker for next payment date
- **Reminder (days before)** *required, numeric, default: 3* → Days before next_date to send reminder
- **Paused** *toggle, default: false* → Pause/resume subscription

**Live Behavior**:
```php
// Amount formatted to Indonesian currency
->hint(fn(?string $state) => toIndonesianCurrency($state ?? 0))

// Account balance hint
->hint(fn(?string $state) => toIndonesianCurrency(PaymentAccount::find($state)?->deposit ?? 0))
```

### 4.3 Table Configuration

The SubscriptionsTable displays subscriptions with these key columns:

**Core Columns**:
- **#** → Row number
- **Subscription ID** → Searchable, copyable, badge display
- **Name** → Wrapped text, searchable
- **Amount** → Indonesian currency format
- **Next Date** → Formatted with `M d, Y`
- **Cycle** → Badge display (Monthly/Quarterly/Yearly)
- **Category** → Category name
- **Payment Account** → Source account name
- **Paused** → Boolean icon
- **Updated** → Since tooltip (hidden by default)

**Default sort**: `next_date` ascending — upcoming subscriptions first.

**Filters**:
- **Trashed** → Soft-deleted records
- **Status** → Active (default) or Paused

**Record Actions** (via ActionGroup):
- **Edit** → Modify subscription
- **Pause/Resume** → Toggle subscription status
- **Mark as Paid** → Create a payment and advance next_date
- **Delete** → Soft delete
- **Restore** → Restore soft-deleted

### 4.4 Pages

#### ListSubscriptions

Standard Filament list page with a single **Create** header action. No custom tabs — all subscriptions displayed in one list.

#### CreateSubscription

Standard Filament create record page. Observer handles code generation and user assignment.

#### EditSubscription

Standard edit page with header actions:
- **Delete** (soft)
- **Force Delete** (permanent)
- **Restore** (from trash)

### 4.5 Actions

#### MarkAsPaidAction

**Purpose**: Create a payment record from a subscription and advance the next billing date.

**Workflow**:
1. Modal opens with pre-filled amount and payment account
2. User can adjust amount or select different account
3. System creates a Payment record (type: EXPENSE)
4. Subscription `next_date` advanced based on cycle:
   - Monthly: `+1 month`
   - Quarterly: `+3 months`
   - Yearly: `+1 year`

**Error Handling**:
- `ValidationException` → Insufficient balance → shows "Payment Failed" notification
- `\Exception` → Any other error → shows error message

```php
// The core logic in MarkAsPaidAction::action()
Payment::create([
    'code'               => getCode('payment'),
    'type_id'            => PaymentType::EXPENSE,
    'payment_account_id' => $data['payment_account_id'],
    'name'               => 'Subscription: ' . $record->name,
    'amount'             => $data['amount'],
    'date'               => Carbon::now()->format('Y-m-d'),
]);

$record->update(['next_date' => self::getNextDate($record->next_date, $record->cycle)]);
```

#### PauseResumeAction

**Purpose**: Toggle subscription between active and paused states.

**Dynamic Behavior**:
- **Active → Paused**: Label "Pause", icon PauseCircle, color warning, confirmation required
- **Paused → Active**: Label "Resume", icon PlayCircle, color success, confirmation required

No form — just confirmation dialog.

```php
$record->update(['is_paused' => !$record->is_paused]);
```

### 4.6 Observer

#### SubscriptionObserver

Simple observer with two lifecycle hooks:

**creating()**:
- Auto-generates subscription code via `getCode('subscription')`
- Assigns `user_id` from authenticated user

**created() / updated()**:
- Logs activity via `saveActivityLog()`

No balance mutation logic — subscriptions don't directly affect account balances.

## 5. Data Flow Examples

### 5.1 Creating a Subscription

**Scenario**: Monthly Netflix subscription of IDR 200,000

**Flow**:
1. Create → Fill: Name "Netflix", Amount 200000, Account "Bank BCA", Cycle Monthly, Next Date "Aug 19, 2026", Reminder 3 days
2. Observer auto-generates code: `LPS-20260719-0001`
3. Save → Subscription record created
4. Activity log: "Created Subscription"

### 5.2 Marking as Paid

**Scenario**: Netflix payment due, process the payment

**Flow**:
1. Open PauseResumeAction on Netflix subscription
2. Confirm pause → `is_paused: true`
3. Later, resume → `is_paused: false`
4. Notification: "Subscription resumed successfully"

### 5.3 Pausing a Subscription

**Scenario**: Temporarily pause subscription during travel

**Flow**:
1. Open MarkAsPaidAction on Netflix subscription
2. Verify amount (200,000) and payment account
3. Confirm → System creates a Payment record
4. Subscription `next_date` advances to Aug 19, 2026 + 1 month = Sep 19, 2026
5. Notification: "Payment has been created and next date has been advanced"

## 6. Key Patterns & Tips

### 6.1 Code Generation

```php
// In SubscriptionObserver creating()
$subscription->code = getCode('subscription');
// Pattern: PREFIX + DATE + SEQUENCE
// Example: LPS-20260719-0001
```

### 6.2 Cycle Date Calculation

```php
// In MarkAsPaidAction
private static function getNextDate(string $currentNextDate, string $cycle): string
{
    $date = Carbon::parse($currentNextDate);
    return match ($cycle) {
        'monthly'   => $date->addMonth()->format('Y-m-d'),
        'quarterly' => $date->addMonths(3)->format('Y-m-d'),
        'yearly'    => $date->addYear()->format('Y-m-d'),
    };
}
```

### 6.3 Relationship to Payment Resource

Subscriptions are **not** directly linked to payments via foreign key. When a subscription is marked as paid:
- A new `Payment` record is created independently
- The subscription's `next_date` is advanced
- No Payment `type` field explicitly links back to the subscription

**Implication**: To find all payments made for a specific subscription, search Payment records with name containing `"Subscription: {name}"`.

### 6.4 Dynamic Action Labels

```php
// PauseResumeAction
->label(fn(Subscription $record): string => $record->is_paused ? 'Resume' : 'Pause')
->icon(fn(Subscription $record) => $record->is_paused ? Heroicon::OutlinedPlayCircle : Heroicon::OutlinedPauseCircle)
->color(fn(Subscription $record): string => $record->is_paused ? 'success' : 'warning')
```

## 7. Common Use Cases

### 7.1 Managing Cloud Subscriptions
1. Create subscription: AWS, IDR 500,000, monthly
2. Track next billing date
3. Mark as paid when invoice arrives

### 7.2 Insurance Premiums
1. Create subscription: Car Insurance, IDR 3,000,000, yearly
2. Set reminder 7 days before
3. Mark as paid annually

### 7.3 Temporary Pause
1. Pause gym membership during travel
2. Resume when back
3. Mark as paid on return

## 8. Troubleshooting Common Issues

### 8.1 "Payment Failed — Insufficient Balance"
**Cause**: Payment account doesn't have enough funds
**Solution**: Check account balance or switch to a different payment account

### 8.2 Subscription Not Advancing Date
**Cause**: Only happens via MarkAsPaid action
**Solution**: Use MarkAsPaid to create payment and auto-advance next_date

### 8.3 Missing Old Payments
**Cause**: No link back from Payment to Subscription
**Solution**: Search Payments for `"Subscription: {name}"` pattern in notes

## 9. Comparison: Subscription vs Payment Resource

| Aspect | Subscription Resource | Payment Resource |
|--------|---------------------|------------------|
| **Purpose** | Manage recurring patterns | Handle individual transactions |
| **Balance Mutation** | None (manual via MarkAsPaid) | Automatic (creating/updating/deleting) |
| **Relations** | No relation managers | Items, Galleries, Installments |
| **Pages** | List, Create, Edit | List, Create, Edit, View, Details |
| **Soft Deletes** | Yes | Yes |
| **States** | Active, Paused | Regular, Draft, Scheduled |

## 10. Testing Guidelines

**Manual Testing**:
- Create subscription for each cycle type (monthly, quarterly, yearly)
- Mark as paid and verify Payment record created
- Verify next_date advances correctly
- Test pause/resume toggling
- Verify soft delete and restore

---

*Copyright © 2026 Nova Ardiansyah*  
*Website: [https://novaardiansyah.id](https://novaardiansyah.id)*  
*Email: [admin@novaardiansyah.id](mailto:admin@novaardiansyah.id)*  
*Phone: [0822 6111 1084](https://wa.me/6282261111084)*