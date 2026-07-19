# Debt Resource Documentation

*Version: v1.0*  
*Last Updated: July 19, 2026*

## 1. Introduction

The Debt Resource manages borrowed money and its repayment schedule (cicilan). When you record a debt, the system automatically splits it into monthly installments, calculates interest, service fees, and VAT for each installment, and tracks your payment progress until the debt is fully paid.

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
- `generateInstallments()` - Annuity calculation
- `calculateAnnuityPayment()` - Monthly installment formula
- `calculateStatus()` - Derive debt status from installments

### Key Relationships:
```
Debt (parent)
├── payment_account (BelongsTo) → disbursement source
├── installments (HasMany → DebtInstallment)
│     ├── debt (BelongsTo)
│     └── payment (BelongsTo → Payment, nullable)
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
- `interest_amount` - Interest for the month
- `service_fee` - Monthly share of total service fee
- `vat_amount` - 11% VAT on the service fee share
- `penalty_amount` - Late penalty (default 0)
- `total_amount` - `principal + interest + service_fee + vat + penalty`
- `status` - `unpaid` | `paid`
- `due_date` - `start_date + (number - 1)` months
- `paid_at` / `payment_id` - Set when paid

### 3.3 Disbursement Payment

On debt creation, a **draft** Payment (type_id = 2 / EXPENSE) is created with amount = `disbursement_amount`. This represents money received (the loan payout). It is a draft so it does not immediately mutate the account balance — the user approves drafts via the Payment Resource.

> **Why a draft?** The disbursement is income-like (you received money) but modeled as EXPENSE to offset the debt visually. Keeping it as draft lets the user control when balance mutation happens.

## 4. Component Deep Dive

### 4.1 Model & Relationships

```php
// Debt.php — fillable (key fields)
'user_id', 'payment_account_id', 'code',
'platform_name',   // e.g. "Bank BCA", "Akhir Pegadaian"
'name',            // e.g. "KTA 2026"
'principal_amount', 'admin_fee', 'disbursement_amount',
'interest_rate', 'service_fee_rate', 'tenor', 'start_date',
'status', 'description', 'paid_tenor',
```

**Casts**:
- `start_date` → date
- `interest_rate` / `service_fee_rate` → decimal:5

**Computed attributes** (accessors on `Debt`):
- `paid_installments_count` → count of `status = paid`
- `total_installments_count` → total installments
- `paid_amount` → sum of `total_amount` where paid
- `total_debt_amount` → sum of all `total_amount`
- `payment_progress` → string `"X / Y Installments Paid (Rp A / Rp B)"`

**Relationships**:
- `payment_account()` → BelongsTo PaymentAccount
- `installments()` → HasMany DebtInstallment
- `user()` → BelongsTo User

### 4.2 Form Schema (`DebtForm.php`)

**Section 1: Debt Information** (2-col grid, full width)
- `platform_name` *required* → lender/platform
- `name` *required* → debt label
- `principal_amount` *required, numeric* → live; auto-sets `disbursement_amount = principal - admin_fee`
- `admin_fee` *required, numeric, default 0* → live; recomputes disbursement
- `disbursement_amount` *required, numeric, readOnly* → computed (principal - admin_fee)
- `interest_rate` *required, numeric, default 0, suffix %*
- `service_fee_rate` *required, numeric, default 0, suffix %*
- `tenor` *required, numeric, default 1* → number of months
- `start_date` *required, date, default now()*

**Section 2: Details** (1-col, narrower)
- `code` → auto-generated, disabled, visible only on edit
- `payment_account_id` → Disbursement Account (relationship, user-scoped), live balance hint
- `status` → ongoing / partial_payment / paid, default ongoing, live
- `paid_tenor` → visible only when status = `partial_payment` OR on create; required then; validated `< tenor`
- `description` → textarea, 3 rows

**Live computation** (in `DebtForm`):
```php
// principal_amount afterStateUpdated
$set('disbursement_amount', $principal - $adminFee);

// admin_fee afterStateUpdated
$set('disbursement_amount', $principal - $adminFee);
```

### 4.3 Infolist (`DebtInfolist.php`)

Two-column layout (2 : 1):
- **Debt Information** section: code, platform, name, status (badge), principal, admin_fee, net disbursement, interest rate, service fee rate, tenor, start date, disbursement account, `payment_progress` (full width), description (full width).
- **System Information** section: created_at, updated_at (since tooltip), deleted_at.

### 4.4 Table Configuration (`DebtsTable.php`)

**Columns**: `#`, code (sortable, copyable, badge), platform (hidden default), name, principal, admin_fee (hidden), net disbursement (hidden), interest rate (hidden), service fee rate (hidden), tenor, start date (since tooltip), status (badge), **progress** (`paid/total` badge), deleted_at, created_at, updated_at.

**Filters**: TrashedFilter only.

**Record actions** (ActionGroup): View, Edit.

**Toolbar actions**: DeleteBulk, ForceDeleteBulk, RestoreBulk.

**Default sort**: `updated_at` desc.

### 4.5 Pages

- `ListDebts` — standard list with Create header action.
- `CreateDebt` — standard create.
- `EditDebt` — header actions: View, Delete, ForceDelete, Restore.
- `ViewDebt` — header actions: Edit, Delete, ForceDelete, Restore. Shows infolist + Installments relation manager.

### 4.6 Relation Manager — Installments

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

### 4.8 Observer & Service

#### DebtObserver
- `creating` → sets `code = getCode('debt')`, `user_id`.
- `created` → `DebtService::generateInstallments($debt)` then `recordDisbursementPayment()` then logs activity.
- `updated` / `deleted` → log activity only.

`recordDisbursementPayment()` creates the draft Payment (type_id = 2, amount = disbursement_amount, date = start_date, is_draft = true).

#### DebtService
- `generateInstallments()` — builds N installments using annuity math (see §5).
- `calculateAnnuityPayment($p, $r, $n, $extraFees)` — standard annuity formula + flat spread of extra fees.
- `calculateStatus()` — paid / partial_payment / ongoing from installment counts.

#### DebtInstallmentObserver
- `created` / `updated` → sync to calendar.
- `updated` → recalc parent debt status via `DebtService::calculateStatus()`.
- `deleted` → remove calendar source + log.

## 5. Installment Calculation (Annuity)

`DebtService::generateInstallments()` logic:

```
monthlyAnnuity = calculateAnnuityPayment(principal, rate/100, tenor, serviceFeeTotal + vatTotal)
               = [ P*r*(1+r)^n / ((1+r)^n - 1) ]  +  (extraFees / n)

totalServiceFee = round(principal * serviceFeeRate)
monthlyServiceFeeBase = floor(totalServiceFee / tenor)
totalVat = round(totalServiceFee * 0.11)
monthlyVatBase = floor(totalVat / tenor)

For each i in 1..tenor:
  serviceFee = last ? totalServiceFee - base*(tenor-1) : base
  vat        = last ? totalVat - baseVat*(tenor-1)     : baseVat
  if last:
    principalPart = remainingPrincipal
    interest      = monthlyAnnuity - principalPart - serviceFee - vat
  else:
    interest      = round(remainingPrincipal * rate)
    principalPart = monthlyAnnuity - interest - serviceFee - vat
    remainingPrincipal -= principalPart
  dueDate = start_date + (i-1) months
```

The last installment absorbs rounding remainders so totals stay exact.

## 6. Data Flow Examples

### 6.1 Creating a Debt
1. Fill form: platform "BCA", name "KTA", principal 10,000,000, admin_fee 200,000, interest 1%, service_fee 0.5%, tenor 12, start_date today.
2. `disbursement_amount` auto = 9,800,000.
3. Observer creates code `LDB-20260719-0001`, generates 12 installments, creates draft disbursement Payment (9,800,000).
4. Debt status = `ongoing`, progress `0 / 12`.

### 6.2 Paying an Installment
1. Open debt → Installments tab → click Pay on installment #1.
2. Choose payment account (balance hint shown).
3. Confirm → real Payment (EXPENSE) created, installment marked paid.
4. Debt progress becomes `1 / 12`; status flips to `partial_payment` automatically.

### 6.3 Fully Paying Off
When the last unpaid installment is paid, `calculateStatus()` returns `paid` and the debt badge turns green.

## 7. Key Patterns & Tips

### 7.1 Code Generation
```php
// DebtObserver::creating
$debt->code = getCode('debt');   // LDB-YYYYMMDD-####
```

### 7.2 Disbursement is a Draft
The auto Payment uses `is_draft = true`. Approve it in the Payment Resource to mutate the account balance.

### 7.3 Status is Derived, Not Stored Intentionally
`status` is stored on the debt but always reconciled by `DebtInstallmentObserver` after any installment change. Do not trust manual edits without the observer running.

### 7.4 Relation Manager Reuse
`InstallmentsRelationManager` calls `DebtInstallmentsTable::configure()` then unsets two columns — the canonical pattern for reusing a table class inside a relation manager (per project AGENTS.md).

### 7.5 Indonesian Currency
```php
->hint(fn(?string $state) => toIndonesianCurrency((float) ($state ?? 0)))
```

## 8. Common Use Cases

### 8.1 Bank Loan
Create debt with principal, interest rate, tenor. Pay installments monthly via PayAction.

### 8.2 Imported / Historical Debt
Set status = `partial_payment` and `paid_tenor` = already-paid count on create. Observer generates all installments and pre-marks the first N as paid (creating draft payments for them).

### 8.3 Track Credit Card Bill
Model each statement as a debt; installments = monthly minimum payments.

## 9. Troubleshooting

### 9.1 Installments Not Generated
**Cause**: Observer did not run (e.g. mass insert bypassing Eloquent).
**Solution**: Always create debts via Eloquent/`Debt::create()` so `DebtObserver::created` fires.

### 9.2 Status Stuck on ongoing After Payment
**Cause**: `DebtInstallmentObserver` skipped (direct DB update).
**Solution**: Update through Eloquent model so observer recalculates status.

### 9.3 Disbursement Payment Missing
**Cause**: Exception during `DebtObserver::created` after installment generation.
**Solution**: Check logs; recreate debt (observer is not transactional across both steps).

### 9.4 paid_tenor Validation Error
**Cause**: `paid_tenor >= tenor`.
**Solution**: `paid_tenor` must be strictly less than `tenor` (validated in `DebtForm`).

## 10. Comparison with Other Payment Resources

| Aspect | Debt | Payment | Subscription |
|--------|------|---------|--------------|
| Child records | Installments (auto) | Items (manual) | None |
| Auto Payment on create | Draft disbursement | No | No (manual MarkAsPaid) |
| Status derived | Yes (from installments) | No | Yes (paused toggle) |
| Relation managers | 1 (installments) | 2 | 0 |
| Balance mutation | Via installment Pay → Payment | Direct in observer | Via MarkAsPaid → Payment |

## 11. Testing Guidelines

**Manual**:
- Create debt for each status path (ongoing, partial with paid_tenor, paid).
- Verify installment count = tenor and totals sum correctly.
- Pay installments one by one; confirm status transitions.
- Confirm draft disbursement Payment appears in Payment Resource.
- Soft-delete and restore a debt; verify installments persist.

---

*Copyright © 2026 Nova Ardiansyah*  
*Website: [https://novaardiansyah.id](https://novaardiansyah.id)*  
*Email: [admin@novaardiansyah.id](mailto:admin@novaardiansyah.id)*  
*Phone: [0822 6111 1084](https://wa.me/6282261111084)*
