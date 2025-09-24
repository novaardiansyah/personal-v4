# Personal v4 Project Documentation

## Project Overview
This is a Laravel-based personal management system built with Filament admin panel. The project includes various features for managing personal data, skills, galleries, payment systems, and URL shortening.

## Tech Stack
- **PHP**: ^8.2
- **Laravel**: ^12.0
- **Filament**: ^4.0 (Admin Panel)
- **Database**: MySQL (presumably)
- **Authentication**: Laravel Sanctum
- **PDF Generation**: mPDF

## Key Features

### 1. Admin Panel (Filament)
- **Navigation Groups**: Productivity, Settings
- **Resources**: Multiple management interfaces for different data types

### 2. Core Models & Features

#### ShortUrl Management
- **Purpose**: URL shortening service with analytics
- **Key Fields**:
  - `code`: Unique identifier
  - `short_code`: The actual short code (with domain prefix via accessor)
  - `long_url`: Original URL
  - `str_code`: Random string for generation
  - `is_active`: Status toggle
  - `clicks`: Access counter
- **Features**:
  - Auto-generate unique codes (3 attempts on collision)
  - Domain prefix via `getSetting('short_url_domain')`
  - Click tracking
  - Soft deletes
- **API**: `GET /api/short-urls/{short_code}` (protected, requires auth)

#### Gallery System
- **Models**: Gallery, GalleryTag
- **Features**: Image gallery with tagging system
- **API Endpoints**:
  - `/api/galleries/` - List galleries
  - `/api/galleries/{id}` - Get specific gallery
  - `/api/galleries/tag/{tagId}` - Get galleries by tag
  - `/api/gallery-tags/` - List tags

#### Skill Management
- **Model**: Skill
- **API Endpoints**:
  - `/api/skills/` - List skills
  - `/api/skills/{id}` - Get specific skill

#### Payment System
- **Models**: Payment, PaymentType, PaymentAccount, PaymentItem, Item, ItemType
- **Complete payment tracking and management system

#### Settings Management
- **Model**: Setting
- **Key-value configuration system**
- **Domain setting**: `short_url_domain` used for URL shortening

### 3. API Structure
- **Authentication**: Bearer token (Sanctum)
- **Base URL**: `/api/`
- **Protected Routes**: Require authentication token
- **Public Routes**: Login endpoint

#### Current API Endpoints
- `POST /api/auth/login` - Authentication
- `GET /api/user` - Get current user (protected)
- `GET /api/skills/` - List skills (protected)
- `GET /api/skills/{id}` - Get skill (protected)
- `GET /api/gallery-tags/` - List gallery tags (protected)
- `GET /api/gallery-tags/{id}` - Get gallery tag (protected)
- `GET /api/galleries/` - List galleries (protected)
- `GET /api/galleries/{id}` - Get gallery (protected)
- `GET /api/galleries/tag/{tagId}` - Get galleries by tag (protected)
- `GET /api/short-urls/{short_code}` - Get short URL data (protected)

### 4. Helper Functions
- `getSetting($key)` - Retrieve setting values
- `getCode($type)` - Generate codes based on type

## Database Migrations
- Recent ShortUrl improvements:
  - `2025_09_24_134957_create_short_urls_table.php` - Initial table
  - `2025_09_24_140435_add_unique_constraint_to_short_urls_code_column.php` - Add unique constraint
  - `2025_09_24_144148_add_str_code_to_short_urls_table.php` - Add str_code column
  - `2025_09_24_144925_rename_short_url_to_short_code_in_short_urls_table.php` - Rename column

## File Structure
```
app/
├── Http/Controllers/Api/          # API Controllers
├── Models/                        # Eloquent Models
├── Filament/Resources/            # Filament Admin Resources
│   ├── ShortUrls/                # Short URL management
│   └── Settings/                 # Settings management
└── Filament/Resources/Settings/   # Settings components (Forms, Tables, etc.)
```

## Recent Development
- Added ShortUrl resource with Filament integration
- Implemented retry logic for unique code generation (3 attempts)
- Added API endpoint for short URL resolution
- Updated table structure for better URL management
- Added domain prefix functionality via model accessors

## Development Notes
- Uses Filament's ActionGroup for organizing record actions
- Implements rowIndex for better table UX
- Soft deletes enabled on appropriate models
- Code generation uses both `code` and `short_code` fields
- Settings system for dynamic configuration