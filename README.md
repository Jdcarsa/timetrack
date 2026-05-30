# ⏱️ TimeTrack

Sistema de seguimiento de horas laborales con control de acceso por roles, planificación semanal, proyectos y exportación a Excel.

![Laravel](https://img.shields.io/badge/Laravel-11-FF2D20?style=flat-square&logo=laravel)
![Vue](https://img.shields.io/badge/Vue-3-42b883?style=flat-square&logo=vuedotjs)
![Vite](https://img.shields.io/badge/Vite-5-646CFF?style=flat-square&logo=vite)

---

## ✨ Funcionalidades

- 🔐 Login y logout con autenticación por token (Sanctum)
- 🕐 Dashboard del empleado con registro de entrada y salida
- 📋 Historial personal de horas trabajadas
- 📅 Planificación semanal con estado de tareas
- 🗂️ Proyectos y cronogramas por semana
- ✅ Creación y asignación de tareas dentro de cronogramas
- 👨‍💼 Módulos de administración:
  - Gestión de empleados y tarifas por hora
  - Registros globales
  - Calendario de tareas
  - Exportación a Excel

---

## 🏗️ Estructura del proyecto

| Carpeta | Descripción |
|---|---|
| `backend/` | API REST con Laravel 11 |
| `frontend/` | SPA con Vue 3 + Vite |

---

## 📋 Requisitos

- PHP 8.2 o superior
- Composer
- Node.js 18 o superior
- MySQL o MariaDB

---

## 🚀 Instalación

### 1. Backend

```bash
cd backend
composer install

# Linux / Mac
cp .env.example .env

# Windows PowerShell
copy .env.example .env

php artisan key:generate
```

Edita `.env` con los datos de tu base de datos:

```env
DB_DATABASE=timetrack
DB_USERNAME=tu_usuario
DB_PASSWORD=tu_password
```

Crea la base de datos y ejecuta migraciones con seeders:

```bash
php artisan migrate --seed
php artisan serve
```

> Backend disponible en `http://localhost:8000`

### 2. Frontend

```bash
cd frontend
npm install
npm run dev
```

> Frontend disponible en `http://localhost:5173`

---

## 👥 Usuarios de prueba

| Rol      | Email                  | Contraseña |
|----------|------------------------|------------|
| Admin    | admin@timetrack.com    | password   |
| Empleado | juan@timetrack.com     | password   |
| Empleado | maria@timetrack.com    | password   |
| Empleado | carlos@timetrack.com   | password   |

---

## 🔌 API Endpoints

### 🔓 Públicos

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| POST | `/api/login` | Iniciar sesión → token Sanctum |

### 🔐 Autenticados

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| POST | `/api/logout` | Cerrar sesión |
| GET | `/api/me` | Perfil del usuario autenticado |
| POST | `/api/clock-in` | Registrar entrada |
| POST | `/api/clock-out` | Registrar salida |
| GET | `/api/records` | Historial personal de registros |
| GET | `/api/status` | Estado actual (dentro/fuera) |
| GET | `/api/work-schedule` | Ver horario laboral |
| PUT | `/api/work-schedule` | Actualizar horario laboral |

### ✅ Tareas

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/api/tasks` | Listar tareas |
| POST | `/api/tasks` | Crear tarea |
| PUT | `/api/tasks/{id}` | Actualizar tarea |
| PATCH | `/api/tasks/{id}/status` | Cambiar estado |
| DELETE | `/api/tasks/{id}` | Eliminar tarea |

### 🗂️ Proyectos

| Método | Endpoint | Descripción | Auth |
|--------|----------|-------------|------|
| GET | `/api/projects` | Listar proyectos | 🔐 JWT |
| GET | `/api/projects/{id}` | Ver proyecto | 🔐 JWT |
| POST | `/api/projects` | Crear proyecto | 🔐 Admin |
| PUT | `/api/projects/{id}` | Actualizar proyecto | 🔐 Admin |
| DELETE | `/api/projects/{id}` | Eliminar proyecto | 🔐 Admin |

### 📅 Cronogramas — `/api/projects/{id}/schedules`

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/` | Listar cronogramas del proyecto |
| POST | `/` | Crear cronograma |
| PUT | `/{schedule}` | Actualizar cronograma |
| DELETE | `/{schedule}` | Eliminar cronograma |
| POST | `/{schedule}/tasks` | Agregar tarea al cronograma |
| DELETE | `/{schedule}/tasks/{task}` | Remover tarea del cronograma |
| GET | `/{schedule}/available-tasks` | Tareas disponibles para asignar |

### 👨‍💼 Administración — `/api/admin`

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/employees` | Listar empleados |
| POST | `/employees` | Crear empleado |
| PUT | `/employees/{id}/hourly-rate` | Actualizar tarifa por hora |
| GET | `/records` | Todos los registros |
| GET | `/summary?from=&to=` | Resumen por rango de fechas |
| GET | `/export?from=&to=` | Exportar a Excel |
| GET | `/calendar` | Calendario de tareas |

> Para la lista completa y actualizada de rutas ver `backend/routes/api.php`

---

## 💻 Notas del frontend

- El sidebar soporta modo colapsado (solo íconos) en escritorio
- En móvil el sidebar se abre como menú overlay
- Los modales son responsive y optimizados para pantallas pequeñas
- Al crear una tarea desde un día seleccionado en el cronograma, usa esa fecha por defecto

---

## 📄 Licencia

MIT — libre para uso personal y comercial.
