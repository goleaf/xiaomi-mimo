# Xiaomi Mimo

Xiaomi Mimo is an existing Laravel 13 and Inertia 3 workspace task application. It uses Vue 3, TypeScript, Pinia, Tailwind CSS 4, Reka UI components, Fortify, Sanctum, Wayfinder, Pest, Larastan, Pint, and SQLite.

## Development

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
npm run build
```

Laravel Herd serves the project at its configured `.test` domain. SQLite is the only supported relational database.

## Documentation

Start with [current state](docs/current-state.md), [product requirements](docs/product-requirements.md), [architecture](docs/architecture.md), and [progress](docs/progress.md). Security-sensitive production deployment should not proceed until the documented audit and hardening phases are complete.
