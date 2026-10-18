# Debt Resource Documentation

*Version: v2.0*  
*Last Updated: October 19, 2026*

## 1. Introduction

The Debt Resource manages borrowed money and its repayment schedule (cicilan). When you record a debt, the system automatically splits it into monthly installments, calculates interest based on the difference between the total payable amount and the principal, and tracks your payment progress until the debt is fully paid.

**Key distinction from Payment/Subscription Resource**: The Debt Resource tracks a **liability with a schedule**. It is the only resource that auto-generates child records (installments) and auto-creates a draft Payment record on creation.

Perfect for:
- **Loans** - Bank loans, peer-to-peer lending, personal loans
- **Credit cards** - Track monthly bill cycles
- **Financing** - Motorcycle/car installments, gadget financing
- **Repayment tracking** - Know exactly how many installments left and how much paid

> **New Contributor Tip**: The Debt Resource is tightly coupled to two other modules: `DebtInstallment` (child records) and `Payment` (auto-created disbursement record). Understanding the installment generation in `DebtService` is the key to understanding this resource.

## 2. Architecture Overview

The Debt Resource follows the Filament pattern with six areas:

**1. Core Resource Components** (`app/Filament/Resources/Debts/`):
- `DebtResource.php` - Resource configuration
- `DebtForm.php` - Form schema for data entry
- `DebtInfolist.php` - View page display
- `DebtsTable.php` - Table configuration
- Pages for list, create, view, and edit

**2. Relation Manager** (`app/Filament/Resources/Debts/RelationManagers/`):
- `InstallmentsRelationManager` - Child installments (reuses DebtInstallments table)

**3. Child Resource** (`app/Filament/Resources/DebtInstallments/`):
- `DebtInstallmentResource.php` - Standalone installments resource
- `DebtInstallmentForm.php` / `DebtInstallmentInfolist.php`
- `DebtInstallmentsTable.php` - Table reused inside relation manager
- `PayAction.php` - Pay an installment (creates a Payment)

**4. Model** (`app/Models/Debt.php`, `app/Models/DebtInstallment.php`):
- `Debt` has many `DebtInstallment`

**5. Observer** (`app/Observers/DebtObserver.php`, `app/Observers/DebtInstallmentObserver.php`):
- `DebtObserver` - Generates installments + disbursement payment on create
- `DebtInstallmentObserver` - Recalculates debt status + calendar sync

**6. Service** (`app/Services/DebtResource/DebtService.php`):
- `generateInstallments()` - Equal installment calculation across tenor
- `calculateStatus()` - Derive debt status from installments

### Key Relationships:
```
Debt (parent)
├── payment_account (BelongsTo) -> disbursement source
├── installments (HasMany -> DebtInstallment)
│     ├── debt (BelongsTo)
│     └── payment (BelongsTo -> Payment, nullable)
└── (auto) Payment record created on creation (is_draft: true)
```

## 3. Core Concepts

### 3.1 Debt Status

| Status | Trigger | Meaning |
|--------|---------|---------|
| `ongoing` | 0 installments paid | Just created, nothing paid yet |
| `partial_payment` | 1..N-1 installments paid | Some paid, not all |
| `paid` | All installments paid | Fully settled |

Status is **derived automatically** by `DebtService::calculateStatus()` whenever an installment is updated. It is never set manually except during initial creation (when `paid_tenor` is provided for seeded/imported debts).

### 3.2 Installment Anatomy

Each `DebtInstallment` record contains:
- `installment_number` - Sequence 1..N
- `principal_amount` - Portion of principal repaid this month
- `interest_amount` - Portion of interest repaid this month
- `penalty_amount` - Late penalty (default 0)
- `total_amount` - `principal + interest + penalty`
- `status` - `unpaid` | `paid`
- `due_date` - `start_date + (number - 1)` months
- `paid_at` / `payment_id` - Set when paid

### 3.3 Disbursement Payment

On debt creation, a **draft** Payment (type_id = 2 / EXPENSE) is created with amount = `principal_amount`. This represents money received (the loan payout). It is a draft so it does not immediately mutate the account balance — the user approves drafts via the Payment Resource.

> **Why a draft?** The disbursement is income-like (you received money) but modeled as EXPENSE to offset the debt visually. Keeping it as draft lets the user control when balance mutation happens.

## 4. Component Deep Dive

### 4.1 Model and Relationships

```php
// Debt.php - fillable (key fields)
"user_id", "payment_account_id", "code",
"platform_name",         // e.g. "Bank BCA", "Akhir Pegadaian"
"name",                  // e.g. "KTA 2026"
"principal_amount",      // Pokok pinjaman
"total_payable_amount",  // Nilai yang harus dibayar
"interest_rate",         // Bunga otomatis (%)
"tenor", "start_date",
"status", "description", "paid_tenor",
```

**Casts**:
- `start_date` -> date
- `interest_rate` -> decimal:5

**Computed attributes** (accessors on `Debt`):
- `paid_installments_count` -> count of `status = paid`
- `total_installments_count` -> total installments
- `paid_amount` -> sum of `total_amount` where paid
- `total_debt_amount` -> sum of all `total_amount`
- `payment_progress` -> string `"X / Y Installments Paid (Rp A / Rp B)"`

**Relationships**:
- `payment_account()` -> BelongsTo PaymentAccount
- `installments()` -> HasMany DebtInstallment
- `user()` -> BelongsTo User

### 4.2 Form Schema (`DebtForm.php`)

**Section 1: Debt Information** (2-col grid, full width)
- `platform_name` *required* -> lender/platform
- `name` *required* -> debt label
- `principal_amount` *required, numeric* -> live; auto-computes `interest_rate`
- `total_payable_amount` *required, numeric* -> live; auto-computes `interest_rate`
- `interest_rate` *required, numeric, readOnly, suffix %* -> computed: `((total - principal) / principal) * 100`
- `tenor` *required, numeric, default 1* -> number of months
- `start_date` *required, date, default now()*

**Section 2: Details** (1-col, narrower)
- `code` -> auto-generated, disabled, visible only on edit
- `payment_account_id` -> Disbursement Account (relationship, user-scoped), live balance hint
- `status` -> ongoing / partial_payment / paid, default ongoing, live
- `paid_tenor` -> visible only when status = `partial_payment` OR on create; required then; validated `< tenor`
- `description` -> textarea, 3 rows

### 4.3 Infolist (`DebtInfolist.php`)

Two-column layout (2 : 1):
- **Debt Information** section: code, platform, name, status (badge), principal, total payable, interest rate, tenor, start date, disbursement account, `payment_progress` (full width), description (full width).
- **System Information** section: created_at, updated_at (since tooltip), deleted_at.

### 4.4 Table Configuration (`DebtsTable.php`)

**Columns**: `#`, code (sortable, copyable, badge), platform (hidden default), name, principal, total payable, interest rate, tenor, start date (since tooltip), status (badge), **progress** (`paid/total` badge), deleted_at, created_at, updated_at.

**Filters**: TrashedFilter only.

**Record actions** (ActionGroup): View, Edit.

**Toolbar actions**: DeleteBulk, ForceDeleteBulk, RestoreBulk.

**Default sort**: `updated_at` desc.

### 4.5 Pages

- `ListDebts` - standard list with Create header action.
- `CreateDebt` - standard create.
- `EditDebt` - header actions: View, Delete, ForceDelete, Restore.
- `ViewDebt` - header actions: Edit, Delete, ForceDelete, Restore. Shows infolist + Installments relation manager.

### 4.6 Relation Manager - Installments

`InstallmentsRelationManager` reuses `DebtInstallmentsTable::configure()` then **unsets** `debt.name` and `payment.name` columns (redundant inside the debt context). Adds a `PayAction` per row. Default sort: `installment_number` desc.

### 4.7 Installment Pay Action (`DebtInstallments/Actions/PayAction.php`)

Visible only when `status = unpaid`. Modal asks for:
- `payment_account_id` (user accounts, live balance hint)
- `total_due` (disabled, shows `total_amount`)
- `remaining_balance` (disabled, live = account deposit - total_amount)

On submit:
1. Creates a **Payment** (type_id = EXPENSE, real, not draft) for `total_amount`.
2. Marks installment `status = paid`, `paid_at = now()`, `payment_id = payment.id`.
3. `DebtInstallmentObserver::updated` recalculates the parent debt status automatically.

### 4.8 Observer and Service

#### DebtObserver
- `creating` -> sets `code = getCode("debt")`, `user_id`.
- `created` -> `DebtService::generateInstallments($debt)` then `recordDisbursementPayment()` then logs activity.
- `updated` / `deleted` -> log activity only.

`recordDisbursementPayment()` creates the draft Payment (type_id = 2, amount = principal_amount, date = start_date, is_draft = true).

#### DebtService
- `generateInstallments()` - builds N installments dividing principal and total payable equally.
- `calculateStatus()` - paid / partial_payment / ongoing from installment counts.

#### DebtInstallmentObserver
- `created` / `updated` -> sync to calendar.
- `updated` -> recalc parent debt status via `DebtService::calculateStatus()`.
- `deleted` -> remove calendar source + log.

## 5. Installment Calculation

`DebtService::generateInstallments()` logic:

```php
monthlyPrincipal = intdiv(principal, tenor);
monthlyTotal     = intdiv(totalPayable, tenor);

for i in 1..tenor:
  isLast = (i === tenor);
  installmentPrincipal = isLast ? (principal - (monthlyPrincipal * (tenor - 1))) : monthlyPrincipal;
  installmentTotal     = isLast ? (totalPayable - (monthlyTotal * (tenor - 1))) : monthlyTotal;
  installmentInterest  = installmentTotal - installmentPrincipal;
  dueDate              = start_date + (i - 1) months;
```

The last installment absorbs any remainder from rounding/integer division so totals match exactly.

## 6. Data Flow Examples

### 6.1 Creating a Debt
1. Fill form: platform "BCA", name "KTA", principal 100,000, total payable 110,000 (interest rate auto = 10%), tenor 10, start_date today.
2. Observer creates code `LDB-YYYYMMDD-####`, generates 10 installments of Rp11,000 each (Rp10,000 principal + Rp1,000 interest), creates draft disbursement Payment (Rp100,000).
3. Debt status = `ongoing`, progress `0 / 10`.

### 6.2 Paying an Installment
1. Open debt -> Installments tab -> click Pay on installment #1.
2. Choose payment account (balance hint shown).
3. Confirm -> real Payment (EXPENSE) created, installment marked paid.
4. Debt progress becomes `1 / 10`; status flips to `partial_payment` automatically.

### 6.3 Fully Paying Off
When the last unpaid installment is paid, `calculateStatus()` returns `paid` and the debt badge turns green.

## 7. Key Patterns and Tips

### 7.1 Code Generation
```php
// DebtObserver::creating
$debt->code = getCode("debt");   // LDB-YYYYMMDD-####
```

### 7.2 Disbursement is a Draft
The auto Payment uses `is_draft = true`. Approve it in the Payment Resource to mutate the account balance.

### 7.3 Status is Derived, Not Stored Intentionally
`status` is stored on the debt but always reconciled by `DebtInstallmentObserver` after any installment change. Do not trust manual edits without the observer running.

### 7.4 Relation Manager Reuse
`InstallmentsRelationManager` calls `DebtInstallmentsTable::configure()` then unsets two columns - the canonical pattern for reusing a table class inside a relation manager (per project AGENTS.md).

### 7.5 Indonesian Currency
```php
->hint(fn(?string $state) => toIndonesianCurrency((float) ($state ?? 0)))
```

---

*Copyright (c) 2026 Nova Ardiansyah*  
*Website: https://novaardiansyah.id*  
*Email: admin@novaardiansyah.id*
