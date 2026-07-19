# Payment Resource Documentation

*Version: v1.0*  
*Last Updated: July 19, 2026*

## 1. Introduction

The Payment Resource is the core financial management system in the application. It handles all types of monetary transactions including income, expenses, transfers, and withdrawals. Think of it as the financial backbone that tracks where money comes from, goes to, and in what amounts.

Perfect for:
- **Personal finance tracking** - Monitor daily cash flow
- **Business accounting** - Track company expenses and income
- **Complex financial operations** - Handle transfers and scheduled transactions

> **New Contributor Tip**: You'll find the Payment Resource throughout multiple files. Each file focuses on a specific aspect but works together as a complete financial tracking system.

## 2. Architecture Overview

The Payment Resource follows a comprehensive Filament pattern with four main areas:

**1. Core Resource Components** (`app/Filament/Resources/Payments/`):
- `PaymentResource.php` - Resource configuration
- `PaymentForm.php` - Form schema for data entry
- `PaymentInfolist.php` - View page display
- `PaymentsTable.php` - Table configuration and filters
- Pages for list, create, edit, view, and details

**2. Actions** (`app/Filament/Resources/Payments/Actions/`):
- Managing drafts and approvals
- Replicating transactions
- Generating reports (PDF/Excel)
- Attaching items and products

**3. Related Resources** (`app/Filament/Resources/Payments/RelationManagers/`):
- Items - Products/services attached to payments
- Galleries - Image attachments and CDN management

**4. Supporting Components**:
- Model (`app/Models/Payment.php`)
- Observer (`app/Observers/PaymentObserver.php`)
- Service (`app/Services/PaymentResource/PaymentService.php`)
- Filters (`app/Filament/Resources/Payments/Schemas/PaymentFilter.php`)

### Key Relationships:

```md
Payment
├── payment_account (One → Many for sender)
├── payment_account_to (One → Many for receiver)
├── category (One → Many categories)
├── items (Many ↔ Many through PaymentItem)
├── galleries (One → Many attachments)
└── installments (One → Many recurring payments)
```

## 3. Core Concepts

### 3.1 Payment Types

| Type | Indonesian Term | Description | Balance Effect |
|------|----------------|-------------|----------------|
| **1** | **Income** | Money received | + Deposit |
| **2** | **Expense** | Money spent | - Deposit |
| **3** | **Transfer** | From one account to another | - From, + To |
| **4** | **Withdrawal** | Take money out of account | - From |

### 3.2 Payment States

| State | Code | Description | When Used |
|-------|------|-------------|-----------|
| **Regular** | - | Active transaction | Normal operations |
| **Draft** | `is_draft: true` | Work in progress | Initial creation |
| **Scheduled** | `is_scheduled: true` | Future transaction | Auto-process tomorrow |

### 3.3 Balance Mutation Rules

**Critical Pattern**: Each payment type affects account balances differently:

- **Income/Expense**: Only affects one account
- **Transfer/Withdrawal**: Affects two accounts
- **Validation**: Always checks account has sufficient funds
- **Rollback**: Updates reverse when payment changes

> **Key Rule**: When `is_draft` or `is_scheduled` is true, **balance mutations are skipped** to prevent premature financial changes.

## 4. Component Deep Dive

### 4.1 Model & Relationships

The Payment model is the foundation with these key fields:

```php
protected $fillable = [
    'type_id',      // Payment type (1-4)
    'user_id',      // Creator/owner
    'payment_account_id',      // From account
    'payment_account_to_id',   // To account (for transfers)
    'code',         // Auto-generated transaction ID
    'name',         // Notes/description
    'amount',       // Transaction amount
    'has_items',    // Toggle for products/services
    'date',         // Transaction date
    'is_scheduled', // Future transaction flag
    'is_draft',     // Work in progress flag
    'category_id',  // Expense/income category
];

// Boolean casts for easy checking
$has_items    => boolean
$is_scheduled => boolean
$is_draft     => boolean
```

**Essential Relationships**:
- `payment_account()` → Belongs to sender account
- `payment_account_to()` → Belongs to receiver account (nullable)
- `items()` → Many items/products attached
- `galleries()` → Images/attachments
- `category()` → Budget category

### 4.2 Form Schema

The PaymentForm handles data entry with dynamic fields based on context:

**Section 1: Transaction Information**
- **Toggle: "Product & Service"** → Shows/hides items relationship
- **Toggle: "Scheduled"** → Sets `is_scheduled: true`
- **Toggle: "Draft"** → Sets `is_draft: true`
- **Amount** → Auto-formatted to Indonesian currency
- **Date** → Today by default, calendar picker
- **Notes** → Required unless `has_items: true`

**Section 2: Financial Details**
- **Transaction ID** → Auto-generated, disabled on edit
- **Category** → Pre-selected to user's default
- **Type** → Default: Income (1), affects field visibility
- **Payment Account** → Default: Cash (TUNAI, value 1)
- **Payment To** → Only for Transfer/Withdrawal types

**Live Behavior**:
```php
// Amount calculation based on settings
->hint(fn(?string $state) => toIndonesianCurrency($state ?? 0))

// Account balance hint
->hint(fn(?string $state) => toIndonesianCurrency(PaymentAccount::find($state)?->deposit ?? 0))
```

### 4.3 Table Configuration

The PaymentsTable displays transactions with these key columns:

**Core Columns**:
- **#** → Row number for identification
- **Transaction ID** → Searchable, copyable, badge display
- **Nominal** → Indonesian currency format
- **Notes** → Wrapped text, searchable
- **Payment** → From account
- **Payment To** → Hidden by default
- **Category** → Hidden by default  
- **Type** → Color-coded badges (success/danger/info/warning)

**Status Indicators**:
- **Scheduled** → Boolean icon (hidden by default)
- **Draft** → Boolean icon (toggleable)

**Filters**:
- **Payment** → Select from account
- **Payment To** → Select to account  
- **Category** → User-specific categories only
- **Date** → Date range picker with indication

**Actions**:
- Header: PDF Report, Excel Report
- Bulk: Details view, Restore deleted

### 4.4 Pages

#### ListPayments

**Key Feature**: Tabs for quick filtering by transaction type:

```php
return [
  'All' => Tab::make(),
  'Expenses' => Tab::make()->modifyQueryUsing(fn($query) => $query->where('type_id', PaymentType::EXPENSE)),
  'Income' => Tab::make()->modifyQueryUsing(fn($query) => $query->where('type_id', PaymentType::INCOME)),
  'Transfer' => Tab::make()->modifyQueryUsing(fn($query) => $query->where('type_id', PaymentType::TRANSFER)),
  'Withdrawal' => Tab::make()->modifyQueryUsing(fn($query) => $query->where('type_id', PaymentType::WITHDRAWAL)),
  'Scheduled' => Tab::make()->modifyQueryUsing(fn($query) => $query->where('is_scheduled', true)),
];
```

#### CreatePayment

Simple page that delegates to Filament's default create functionality with custom redirect URL after creation.

#### EditPayment

**Special Features**:
- Multiple header actions: View, Create, Delete, Force Delete, Restore
- Auto-refresh form when Livewire event dispatched
- **ReplicateAction** → Clone payments with optional date changes

#### ViewPayment

Standard Filament view page with Create and Edit actions available.

#### PaymentDetails

**Unique Feature**: Bulk action page for viewing multiple payments:

```php
#[Url]
public string $ids = '';
// Converted to array in mount()
public array $recordIds = [];

public function mount(): void
{
    $this->recordIds = array_filter(explode(',', $this->ids));
}
```

### 4.5 Actions

#### ManageDraft

**Purpose**: Approve draft payments with optional balance mutation:

**Workflow**:
1. Submit form with amount, type, and accounts
2. System validates account balances
3. Either: Mutate balances immediately OR just mark as approved

**Schema includes**:
- Amount field (numeric validation)
- Type selector
- Payment account options
- Payment to account (conditional)
- Balance mutation toggle
- Approve draft toggle

#### ReplicateAction

**Perfect for**: When you want to create similar transactions:

**Features**:
- Replicate existing payment
- Customize notes
- Add multiple dates (repeater)
- Clone associated items automatically
- Show only for draft payments

#### PrintPdf

**Reports Module**:
```php
Options:
- Daily Report: Transactions for one day
- Monthly Report: Transactions for one month  
- Custom Date Range: Flexible period
```

**Output**: Professional PDF with totals, formatting, and Indonesian locale.

#### Item Management

**Two ways to add products**:
1. **Create New**: Form with name, type, amount, quantity
2. **Attach Existing**: Select from catalog with amount/quantity pre-filled

### 4.6 Observer & Service

#### PaymentObserver

**Critical Event Handlers**:

**Creating**:
- Auto-generate transaction code via `getCode('payment')`
- **Balance validation** for income/expense/transfer
- Skip drafts and scheduled payments

**Updating**:
- Reversable balance changes
- Complex logic for account changes
- Calendar integration sync

**Deleting**:
- Balance restoration
- Item detachment
- Attachment cleanup

#### PaymentService

**Business Logic Central**:

**Key Methods**:
- `mutateBalance()` → Core balance calculation
- `manageDraft()` → Draft approval workflow
- `make_pdf()` → Report generation
- `scheduledPayment()` → Auto-process tomorrow
- `processScheduledPayment()` → Execute scheduled transactions

## 5. Data Flow Examples

### 5.1 Creating a Regular Payment

**Scenario**: Income of IDR 2,500,000 from Investment account

**Flow**:
1. Create → Form with default income type
2. Observer validates balance (System: 10,000,000)
3. Service mutates balance (System: 12,500,000)
4. Save → Code LPP-20250719-0001
5. Calendar sync triggered

### 5.2 Creating a Draft Payment

**Scenario**: Planned expense of IDR 500,000

**Key Differences**:
- Balance mutation skipped during creation
- Status: `is_draft: true`
- Can edit without affecting finances
- **ManageDraftAction** → Approve with balance check

### 5.3 Processing Scheduled Payment

**Scenario**: Tomorrow's scheduled expense processed today

**Steps**:
1. Job runs at midnight check
2. Service finds scheduled payments within 24 hours
3. Balance validation and mutation
4. Status: `is_scheduled: false`
5. Calendar sync

### 5.4 Replicating Payment

**Scenario**: Clone last month's utility payment with new date

**Workflow**:
1. Open ReplicateAction on original payment
2. Adjust dates in repeater
3. Modify notes if needed
4. Confirm → System creates new payment
5. All items automatically cloned

## 6. Key Patterns & Tips

### 6.1 Code Generation

```php
// In PaymentObserver creating()
$record->code = getCode('payment');
// Pattern: PREFIX + DATE + SEQUENCE
// Example: LPP-20250719-0001
```

### 6.2 Balance Mutation Security

**Critical Pattern**: Always validate before changing:

```php
// In PaymentObserver creating() line 66-68
if ($depositChange < $amount) {
    throw ValidationException::withMessages($insufficientBalanceError);
}
```

### 6.3 Balance Restoration (Update/Soft Delete)

**Pattern in Observer**:
```php
// When updating payment back to original state
// First revert old balances, then apply new
```

### 6.4 Indonesian Currency Format

**Pattern everywhere**:
```php
->hint(fn(?string $state) => toIndonesianCurrency($state ?? 0))
```

### 6.5 Live Form Updates

**Dynamic field behavior**:
```php
// Toggle "has_items" shows/hides amount field
// Change "type_id" shows/hides "payment_account_to"
```

## 7. Common Use Cases

### 7.1 Expense Tracking
1. Expense category for dining out
2. Amount: IDR 150,000
3. From bank account
4. Save → Balance updated

### 7.2 Transfer Between Accounts
1. Type: Transfer
2. From: Investment  
3. To: Savings
4. Amount: IDR 5,000,000
5. Both account balances updated

### 7.3 Bulk Payment View
1. Use `ListPayments` with custom filter
2. Select multiple payments
3. Click **Details** bulk action
4. `PaymentDetails` page shows all selected

### 7.4 Monthly Financial Report
1. Click **PDF** action
2. Select monthly report
3. Choose account
4. Generate → Email attachment

### 7.5 Product/Service Sales
1. Payment has `has_items: true`
2. Add products through Items relation
3. Each product updates payment total
4. Auto-calculates total with quantity × price

## 8. Troubleshooting Common Issues

### 8.1 "Insufficient Balance" Error
**Cause**: Account balance too low for transaction
**Solution**: Verify account deposit amount or use different account

### 8.2 "Payment Account Mismatch"
**Cause**: Same account for From and To in transfer
**Solution**: Select different accounts for transfer type

### 8.3 "Cannot Replicate Non-Draft"
**Cause**: ReplicateAction only visible for drafts
**Solution**: Convert to draft (`is_draft: true`), replicate, then approve

### 8.4 Form Not Updating After Actions
**Cause**: Need to refresh page or trigger event
**Solution**: Look for `refreshForm` dispatch in EditPayment

## 9. Performance Considerations

- **Database**: Index on `date`, `type_id`, `is_draft`, `is_scheduled`
- **Queries**: Use eager loading (`$with`) to prevent N+1
- **Reports**: Chunk large result sets in services
- **CDN**: Galleries use queue for uploads (concurrently limit: 5)

## 10. Testing Guidelines

**Manual Testing**:
- Create each payment type (income, expense, transfer, withdrawal)
- Test draft flow end-to-end
- Verify balance mutations
- Check scheduled payment processing
- Test item attachment workflows

**Report Verification**:
- PDFs have correct totals
- Excel contains all expected data
- Both formats send to email when enabled

---

**Next Steps**: With this documentation, you should be able to create, manage, and understand payments in the system. The key is understanding the balance mutation rules and when payments are processed vs when they're drafted.

Need clarification on any specific part of the documentation?

---

*Copyright © 2026 Nova Ardiansyah*  
*Website: [https://novaardiansyah.id](https://novaardiansyah.id)*  
*Email: [admin@novaardiansyah.id](mailto:admin@novaardiansyah.id)*  
*Phone: [0822 6111 1084](https://wa.me/6282261111084)*