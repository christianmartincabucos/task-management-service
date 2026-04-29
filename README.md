# Translation Management Service

A high-performance, API-driven Translation Management Service built with Laravel. Supports multi-locale translations, contextual tagging, search, and JSON export for frontend applications.

## Features

- **Multi-locale translations** — Store and manage translations across unlimited languages (en, fr, es, etc.)
- **Contextual tagging** — Tag translations for context (mobile, desktop, web, api, etc.)
- **Search & filtering** — Search by key, content, locale, or tag
- **JSON export** — Single endpoint returning translations formatted for frontend apps (Vue.js, React, etc.)
- **Token-based authentication** — Secured with Laravel Sanctum
- **High performance** — Sub-200ms response times on all endpoints; export handles 100k+ records under 500ms
- **Comprehensive testing** — 100+ tests with > 95% code coverage

## Tech Stack

- **PHP 8.4+** / **Laravel 13**
- **Laravel Sanctum** for API authentication
- **MySQL 8.0+** (production/Docker)
- **Redis** for caching (Docker setup)
- **PHPUnit 12** for testing

## Design Choices

### Architecture

The application follows **SOLID principles** with a clear separation of concerns:

- **Repository Pattern** (`TranslationRepository`) — Encapsulates all database queries, making it easy to swap data sources or optimize queries without touching business logic.
- **Service Layer** (`TranslationService`) — Handles business logic including cache management, delegating data access to the repository.
- **Form Requests** — Validation is decoupled from controllers into dedicated request classes.
- **API Resources** — Response formatting is handled by `TranslationResource`, ensuring consistent JSON output.

### Database Schema

```
translations (id, key, locale, content, created_at, updated_at)
    UNIQUE INDEX (key, locale)
    INDEX (key)
    INDEX (locale)

tags (id, name, created_at, updated_at)
    UNIQUE INDEX (name)

tag_translation (tag_id, translation_id)
    PRIMARY KEY (tag_id, translation_id)
    FOREIGN KEY tag_id → tags.id ON DELETE CASCADE
    FOREIGN KEY translation_id → translations.id ON DELETE CASCADE
```

This normalized schema avoids JSON columns for tags, enabling efficient JOIN-based filtering and proper indexing for 100k+ record performance.

### Performance Optimizations

1. **Composite indexes** on `(key, locale)` for fast lookups
2. **Eager loading** of relationships to eliminate N+1 queries
3. **Caching** — Export endpoint results are cached and automatically invalidated on any write operation
4. **Chunked inserts** in the seeder for efficient bulk data population
5. **CDN support** — Export responses include `Cache-Control` and `ETag` headers

### Security

- All CRUD and export endpoints require Sanctum token authentication
- Input validation via Form Requests with explicit rules
- Mass assignment protection via `$fillable`
- Password hashing with bcrypt

## Getting Started

### Prerequisites

- PHP 8.2 or higher
- Composer
- SQLite (included with PHP) or MySQL 8.0

### Local Setup

```bash
# Clone the repository
git clone <repository-url>
cd translation-management-service

# Install dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Create SQLite database
touch database/database.sqlite

# Run migrations
php artisan migrate

# Start the development server
php artisan serve
```

### Docker Setup

```bash
# Build and start containers
docker-compose up -d

# Install dependencies
docker-compose exec app composer install

# Run migrations
docker-compose exec app php artisan migrate

# The API will be available at http://localhost:8000
```

## API Endpoints

### Authentication

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/register` | Register a new user |
| POST | `/api/login` | Login and receive token |
| POST | `/api/logout` | Logout (revoke token) |

### Translations

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/translations` | List translations (with filters) |
| POST | `/api/translations` | Create a translation |
| GET | `/api/translations/{id}` | View a translation |
| PUT | `/api/translations/{id}` | Update a translation |
| DELETE | `/api/translations/{id}` | Delete a translation |

### Export

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/export` | Export all translations as JSON |
| GET | `/api/export/{locale}` | Export translations for a specific locale |

### Query Parameters (Index)

| Parameter | Description | Example |
|-----------|-------------|---------|
| `locale` | Filter by locale | `?locale=en` |
| `key` | Filter by exact key | `?key=auth.login` |
| `tag` | Filter by tag name | `?tag=mobile` |
| `search` | Search in key and content | `?search=welcome` |
| `per_page` | Results per page (max 100) | `?per_page=50` |

### Example Requests

```bash
# Register
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{"name":"John","email":"john@example.com","password":"password123","password_confirmation":"password123"}'

# Login
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"john@example.com","password":"password123"}'

# Create translation (use token from login response)
curl -X POST http://localhost:8000/api/translations \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"key":"auth.welcome","locale":"en","content":"Welcome!","tags":["web","mobile"]}'

# Search translations
curl http://localhost:8000/api/translations?search=welcome \
  -H "Authorization: Bearer YOUR_TOKEN"

# Export all translations
curl http://localhost:8000/api/export \
  -H "Authorization: Bearer YOUR_TOKEN"

# Export English translations only
curl http://localhost:8000/api/export/en \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## Seeding Test Data

Seed the database with 100k+ translation records for performance testing:

```bash
# Default: 100,000 records
php artisan translations:seed

# Custom count
php artisan translations:seed --count=200000
```

## Running Tests

```bash
# Run all tests
php vendor/phpunit/phpunit/phpunit

# Run with coverage report
php vendor/phpunit/phpunit/phpunit --coverage-text

# Run specific test suites
php vendor/phpunit/phpunit/phpunit --testsuite=Unit
php vendor/phpunit/phpunit/phpunit --testsuite=Feature
```

## API Documentation

Full OpenAPI 3.0 specification is available at [`docs/openapi.yaml`](docs/openapi.yaml).

You can view it using [Swagger Editor](https://editor.swagger.io/) or any OpenAPI-compatible tool.

## Project Structure

```
app/
├── Console/Commands/SeedTranslationsCommand.php
├── Http/
│   ├── Controllers/Api/
│   │   ├── AuthController.php
│   │   ├── TranslationController.php
│   │   └── ExportController.php
│   ├── Requests/
│   │   ├── StoreTranslationRequest.php
│   │   └── UpdateTranslationRequest.php
│   └── Resources/TranslationResource.php
├── Models/
│   ├── Translation.php
│   ├── Tag.php
│   └── User.php
├── Providers/AppServiceProvider.php
├── Repositories/TranslationRepository.php
└── Services/TranslationService.php
database/
├── factories/
│   ├── TranslationFactory.php
│   └── TagFactory.php
├── migrations/
└── seeders/TranslationSeeder.php
tests/
├── Unit/ (3 test classes, 30+ tests)
├── Feature/ (5 test classes, 70+ tests)
docs/openapi.yaml
```

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
