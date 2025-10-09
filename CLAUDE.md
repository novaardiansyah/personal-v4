# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview
This is a Laravel-based personal management system built with Filament admin panel. The project includes features for managing personal data, skills, galleries, payment systems, and URL shortening.

## Key Development Commands

### Testing
```bash
# Run all tests
composer test
# or
php artisan test

# Run tests with config clear
php artisan config:clear && php artisan test

# Run single test
php artisan test --filter=YourTestName
```

### Development Environment
```bash
# Start development environment (runs server, queue, and Vite)
composer dev

# Clear caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# Queue processing
php artisan queue:listen --tries=1

# Serve application (manually)
php artisan serve
```

### Code Quality
```bash
# Code formatting with Laravel Pint
./vendor/bin/pint

# Run specific linters via Pint
./vendor/bin/pint --test
```

### Filament-Specific Commands
```bash
# Discover Filament packages
php artisan package:discover --ansi

# Upgrade Filament
php artisan filament:upgrade
```

## Architecture Overview

### Core Models and Relationships
1. **Payment System** (Complex multi-table architecture):
   - `PaymentAccount` - Stores account balances and audit functionality
   - `PaymentType` - Types of transactions (income, expense, transfer)
   - `Payment` - Individual transactions with support for transfers between accounts
   - `Item` and `ItemType` - Products/services that can be attached to payments
   - `PaymentItem` - Pivot table connecting payments to items with pricing

2. **Settings Management**:
   - `Setting` model with key-value storage
   - Cached settings via `getSetting()` helper with `cache()->rememberForever()`
   - Observer pattern for automatic cache invalidation

3. **Code Generation**:
   - `Generate` model manages sequential code generation
   - `getCode()` helper generates formatted codes with date-based prefixes
   - Handles queue management with automatic reset on date change

### API Architecture
- **Authentication**: Laravel Sanctum with Bearer tokens
- **Response Format**: Standardized JSON responses with success/error structure
- **Controllers**: Each model has dedicated API controller with standardized CRUD operations
- **Resources**: Laravel API Resources for consistent response formatting
- **Route Organization**: Grouped by feature area with middleware protection

### Filament Admin Panel
- **Navigation Groups**: Organized by functionality (Payments, Productivity, Settings)
- **Resource Structure**: Each resource follows Filament v4 conventions
- **Forms and Tables**: Separated into schema files for maintainability
- **Actions**: Extensive use of ActionGroup for organizing record operations
- **Observers**: Used for activity logging and data consistency

### Helper Functions (app/Helpers/UtilsHelper.php)
- `getSetting()` - Cached setting retrieval
- `getCode()` - Sequential code generation
- `makePdf()` - PDF generation with temporary signed URLs
- `getUser()` - Current or specified user retrieval
- `getIpInfo()` - IP geolocation from ipinfo.io
- `saveActivityLog()` - Activity logging with change tracking
- `toIndonesianCurrency()` - Localized currency formatting

### Database Patterns
- **Soft Deletes**: Used across most models for data recovery
- **Foreign Key Constraints**: Properly configured with cascade deletes
- **Observer Pattern**: Automatic logging and cache management
- **Caching**: Heavy use of Laravel cache for settings and performance

## Important Conventions

### Code Formatting
- **Tab Size**: Always use 2 spaces for Laravel PHP files
- **File Structure**: Consistent namespace organization
- **Naming**: Follow Laravel conventions (snake_case for DB, camelCase for PHP)

### API Development
- **Authentication**: All protected routes require `auth:sanctum` middleware
- **Response Format**: Consistent JSON structure with success/error indicators
- **Validation**: Use Laravel Validator with proper error responses
- **Testing**: Manual API testing - do not automatically run `php artisan serve`

### Git Workflow
- **Multi-Remote**: Always push to both remotes:
  ```bash
  git push origin main && git push person main
  ```
- **Commit Format**: Conventional commits (feat:, fix:, chore:, etc.)
- **Branch Management**: Main branch is `main`

### Model Relationships
- **Payment System**: Complex many-to-many relationships via pivot tables
- **Soft Deletes**: Include `->withTrashed()` when needed for soft-deleted records
- **Observers**: Used for automatic activity logging on model changes

## Development Notes

### Settings System
- Settings are cached indefinitely for performance
- Observer pattern automatically invalidates cache on updates
- Key-value storage supports boolean casting and JSON options

### URL Shortening
- `ShortUrl` model with domain prefix functionality
- Accessor automatically prepends domain from settings
- Observer handles code generation and collision resolution

### Activity Logging
- Comprehensive logging system tracks all model changes
- Automatic IP geolocation and user tracking
- Soft delete support for audit trails

### File Management
- `File` model tracks generated files with expiration
- Temporary signed URLs for secure file downloads
- Automatic cleanup of expired files 