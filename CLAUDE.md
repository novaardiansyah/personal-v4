# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a **Personal Filament v4 Admin Panel** built with Laravel 12 and Filament PHP 4. It's a personal utility management system with comprehensive admin functionality, including payment management, galleries, skills, contacts, and more.

## Development Commands

### Environment Setup
```bash
# Install dependencies
composer install
npm install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Run database migrations
php artisan migrate
```

### Development Server
```bash
# Start development server with queue and Vite (recommended)
composer run dev

# Individual services
php artisan serve                    # Laravel development server
php artisan queue:listen --tries=1  # Queue worker
npm run dev                         # Vite frontend development
```

### Testing
```bash
# Run all tests
composer run test
# or
php artisan test

# Run specific test suites
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit
```

### Code Quality
```bash
# Code formatting (Laravel Pint)
./vendor/bin/pint

# Clear caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

### Frontend Assets
```bash
# Build for production
npm run build

# Development mode
npm run dev
```

## Architecture Overview

### Core Technology Stack
- **Backend**: Laravel 12 with PHP 8.2+
- **Admin Panel**: Filament PHP 4
- **Frontend**: Vite with TailwindCSS 4
- **Database**: MySQL
- **Testing**: PestPHP with PHPUnit

### Key Application Components

#### 1. Filament Admin Panel Structure
The admin interface is organized into Filament Resources in `app/Filament/Resources/`:
- **Payments**: Complete payment management system with items, accounts, types, and goals
- **Galleries**: Image gallery management with tags
- **Users**: User management with roles and permissions
- **Settings**: Application configuration management
- **Files**: File management system
- **Skills**: Skills portfolio management
- **Contact Messages**: Contact form submissions
- **Short URLs**: URL shortening service
- **Push Notifications**: Notification management
- **Activity Logs**: System activity tracking

#### 2. API Architecture
Comprehensive REST API in `routes/api.php` with sanctum authentication:
- **Authentication**: Login, logout, token validation, password change
- **Payment Management**: Full CRUD operations with advanced features
- **Gallery System**: Galleries and tags management
- **User Profile**: Profile management and avatar handling
- **Notifications**: Push notification settings and testing
- **Reports**: Payment summaries and analytics

#### 3. Database Models
The application uses Eloquent models in `app/Models/`:
- Core models: User, Payment, PaymentAccount, PaymentType, PaymentGoal
- Content models: Gallery, GalleryTag, Skill, Item, ItemType
- System models: Setting, ActivityLog, File, ContactMessage
- Utility models: ShortUrl, PushNotification, Generate

#### 4. Job System
Scheduled tasks in `app/Jobs/`:
- **ScheduledPaymentJob**: Daily payment processing (00:05)
- **DailyReportJob**: Daily payment reports (23:59)
- **RemoveFileJob**: File cleanup every 2 hours
- **CleanExpiredTokens**: Token cleanup (23:59)

#### 5. Helper Functions
Custom helper functions in `app/Helpers/UtilsHelper.php` (auto-loaded via composer.json)

### File Structure Patterns

#### Filament Resource Organization
Each Filament resource follows a consistent structure:
```
app/Filament/Resources/{ResourceName}/
├── {ResourceName}Resource.php          # Main resource definition
├── Pages/
│   ├── Manage{ResourceName}.php       # List view
│   ├── Create{ResourceName}.php       # Create form
│   ├── Edit{ResourceName}.php         # Edit form
│   └── View{ResourceName}.php         # Detail view (when applicable)
├── Schemas/
│   ├── {ResourceName}Form.php         # Form schema
│   ├── {ResourceName}Infolist.php     # Detail view schema
│   └── {ResourceName}Action.php       # Bulk actions
└── Tables/
    └── {ResourceName}sTable.php        # Table configuration
```

#### API Controller Structure
API controllers in `app/Http/Controllers/Api/` follow RESTful conventions with additional methods:
- Standard CRUD: index(), store(), show(), update(), destroy()
- Custom endpoints for business logic (e.g., reports, analytics)
- Consistent JSON response patterns

## Development Guidelines

### Environment Configuration
- Default timezone: `Asia/Jakarta`
- Queue connection: `database`
- Session driver: `database`
- Filesystem disk: `local` with public disk for uploads

### Authentication & Security
- Uses Laravel Sanctum for API authentication
- Signed routes for file downloads
- Activity logging for audit trails
- User roles and permissions through Filament

### Frontend Development
- Uses Vite for asset bundling with HMR
- TailwindCSS 4 for styling
- Axios for HTTP requests
- Filament handles most admin UI components

### Testing Environment
- Uses SQLite in-memory database for testing
- PestPHP as the primary testing framework
- Separate Unit and Feature test suites
- PHPUnit configuration in `phpunit.xml`

### Scheduling & Background Jobs
The application includes several scheduled jobs defined in `routes/console.php`:
- Payment processing and reporting
- File cleanup and maintenance
- Token management and security

## Important Notes

- This is a public repository with MIT License
- Sensitive configuration (.env, database) is not included
- The project uses database-driven queues and sessions
- All Filament resources are properly namespaced and follow Laravel conventions
- The codebase includes comprehensive API documentation through route definitions