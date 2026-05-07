# TimeTrack MVP - Laravel 11 + Vue 3

TimeTrack is a work hour tracking system with role based access (admin and empleado), weekly planning, project schedules, and Excel export.

## Project structure

```text
timetrack/
|-- backend/   # Laravel 11 REST API
`-- frontend/  # Vue 3 + Vite SPA
```

## Main features

- Login and logout with token auth
- Employee dashboard with clock in and clock out
- Personal history of worked hours
- Weekly planning with task status
- Projects and schedules by week
- Create and assign tasks inside schedules
- Admin modules:
  - Employees
  - Global records
  - Calendar
  - Excel export

## Requirements

- PHP 8.2 or higher
- Composer
- Node.js 18 or higher
- MySQL or MariaDB

## Setup

### 1) Backend

```bash
cd backend
composer install

# Linux/Mac
cp .env.example .env

# Windows PowerShell
copy .env.example .env

php artisan key:generate
```

Edit `.env` with your database config:

```env
DB_DATABASE=timetrack
DB_USERNAME=tu_usuario
DB_PASSWORD=tu_password
```

Create database and run migrations + seed:

```bash
php artisan migrate --seed
php artisan serve
```

Backend default URL:

- http://localhost:8000

### 2) Frontend

```bash
cd frontend
npm install
npm run dev
```

Frontend default URL:

- http://localhost:5173

## Test users (seed)

| Rol      | Email                | Password |
|----------|----------------------|----------|
| Admin    | admin@timetrack.com  | password |
| Empleado | juan@timetrack.com   | password |
| Empleado | maria@timetrack.com  | password |
| Empleado | carlos@timetrack.com | password |

## Core API routes

Public:

- `POST /api/login`

Authenticated:

- `POST /api/logout`
- `GET /api/me`
- `POST /api/clock-in`
- `POST /api/clock-out`
- `GET /api/records`
- `GET /api/status`

Admin:

- `GET /api/admin/employees`
- `PUT /api/admin/employees/{id}/hourly-rate`
- `GET /api/admin/records`
- `GET /api/admin/summary?from=&to=`
- `GET /api/admin/export?from=&to=`

Note: For the complete and latest route list, check `backend/routes/api.php`.

## Frontend notes

- The sidebar supports collapse mode (icons only) in desktop.
- On mobile, sidebar opens as an overlay menu.
- Modals are responsive and optimized for small screens.
- In schedules, creating a task from a selected day uses that day date by default.
