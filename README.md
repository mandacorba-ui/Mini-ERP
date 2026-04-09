# Mini ERP — Time & Workforce Management

A small ERP module for workforce management built with **Laravel 13**, **Livewire Volt**, and **SQLite**.

## Features

- **Authentication & Roles** — Employee, Manager, Admin (Laravel Breeze)
- **Time Tracking** — Clock in/out with weekly hours summary
- **Holiday / Leave Requests** — Request, approve, reject with overlap detection and annual balance enforcement
- **Task Management** — Create tasks, assign users, transition statuses, log hours
- **Shift Scheduling** — Create shifts, assign employees with overlap and holiday-aware validation
- **Dashboard** — Aggregated view of weekly hours, pending holidays, upcoming shifts, overdue tasks

## Tech Stack

| Layer       | Technology                  |
|-------------|-----------------------------|
| Framework   | Laravel 13 / PHP 8.3+      |
| Frontend    | Livewire 3 + Volt, Alpine.js, Tailwind CSS (CDN) |
| Database    | SQLite                      |
| Auth        | Laravel Breeze (Blade)      |
| Tests       | PHPUnit (65 tests)          |

## Architecture

DDD-inspired layered structure under `app/Domain/`:

```
app/Domain/
├── IdentityAndAccess/   # User model, roles enum
├── TimeTracking/        # TimeLog model, ClockIn/ClockOut actions
├── TaskManagement/      # Task, assignments, status transitions
└── WorkforcePlanning/   # Holidays, shifts, overlap detection, leave balance
```

Business logic lives in **Action** classes. Shared services (overlap detection, leave balance) are in **Services**. Livewire Volt components handle the UI.

## Setup

```bash
git clone <repo-url> mini-erp
cd mini-erp
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan serve
```

## Demo Accounts

| Email                 | Role     | Password   |
|-----------------------|----------|------------|
| admin@example.com     | Admin    | password   |
| manager@example.com   | Manager  | password   |
| alice@example.com     | Employee | password   |
| bob@example.com       | Employee | password   |
| carol@example.com     | Employee | password   |

## Running Tests

```bash
php artisan test
```

65 tests, 121 assertions covering time tracking, holidays, tasks, shifts, and authentication.

