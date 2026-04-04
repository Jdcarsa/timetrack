# ⏱ TimeTrack MVP — Laravel 11 + Vue 3

Sistema de control de horas de trabajo con roles (admin/empleado),
exportación a Excel y gestión de tarifas por hora.

---

## 📁 Estructura del proyecto

```
timetrack/
├── backend/    → API REST en Laravel 11
└── frontend/   → SPA en Vue 3 + Vite
```

---

## 🚀 Instalación paso a paso

### 1. Requisitos previos

- PHP >= 8.2
- Composer
- Node.js >= 18
- MySQL o MariaDB

---

### 2. Backend (Laravel)

```bash
# 1. Entra a la carpeta
cd backend

# 2. Instala dependencias PHP
composer install

# 3. Copia el archivo de entorno
cp .env.example .env

# 4. Genera la clave de la app
php artisan key:generate

# 5. Edita .env con tus datos de base de datos:
#    DB_DATABASE=timetrack
#    DB_USERNAME=tu_usuario
#    DB_PASSWORD=tu_password

# 6. Crea la base de datos en MySQL
#    CREATE DATABASE timetrack;

# 7. Ejecuta las migraciones y seeders
php artisan migrate --seed

# 8. Inicia el servidor
php artisan serve
# → corre en http://localhost:8000
```

---

### 3. Frontend (Vue 3)

```bash
# 1. En otra terminal, entra a la carpeta
cd frontend

# 2. Instala dependencias JS
npm install

# 3. Inicia el servidor de desarrollo
npm run dev
# → corre en http://localhost:5173
```

Abre **http://localhost:5173** en tu navegador.

---

## 👤 Usuarios de prueba (creados por el seeder)

| Rol      | Email                  | Contraseña | Tarifa/h   |
|----------|------------------------|------------|------------|
| Admin    | admin@timetrack.com    | password   | —          |
| Empleado | juan@timetrack.com     | password   | $15.000    |
| Empleado | maria@timetrack.com    | password   | $18.000    |
| Empleado | carlos@timetrack.com   | password   | $12.000    |

---

## 🔌 Endpoints de la API

### Públicos
| Método | Ruta        | Descripción         |
|--------|-------------|---------------------|
| POST   | /api/login  | Iniciar sesión       |

### Autenticados (Bearer Token)
| Método | Ruta            | Descripción                  |
|--------|-----------------|------------------------------|
| POST   | /api/logout     | Cerrar sesión                |
| GET    | /api/me         | Datos del usuario actual      |
| POST   | /api/clock-in   | Marcar entrada               |
| POST   | /api/clock-out  | Marcar salida                |
| GET    | /api/records    | Historial del empleado        |
| GET    | /api/status     | Estado actual (activo/inactivo)|

### Solo Admin
| Método | Ruta                                    | Descripción                  |
|--------|-----------------------------------------|------------------------------|
| GET    | /api/admin/employees                    | Listar empleados             |
| PUT    | /api/admin/employees/{id}/hourly-rate   | Actualizar tarifa/h          |
| GET    | /api/admin/records                      | Todos los registros           |
| GET    | /api/admin/summary?from=&to=            | Resumen por empleado          |
| GET    | /api/admin/export?from=&to=             | Descargar Excel               |

---

## 🏗️ Conceptos de Laravel que aprenderás

- **Migraciones** → Crear y modificar tablas (`database/migrations/`)
- **Modelos Eloquent** → ORM para interactuar con la BD (`app/Models/`)
- **Controladores** → Lógica de negocio (`app/Http/Controllers/`)
- **Rutas API** → Definición de endpoints (`routes/api.php`)
- **Middleware** → Protección de rutas (`app/Http/Middleware/`)
- **Sanctum** → Autenticación por tokens
- **Exports** → Generación de archivos Excel (`app/Exports/`)

## 🏗️ Conceptos de Vue que aprenderás

- **Composition API** → `ref`, `computed`, `onMounted`
- **Vue Router** → Navegación y guards de rutas
- **Pinia** → Manejo de estado global (auth)
- **Axios** → Llamadas a la API con interceptores
- **Componentes** → Reutilización y organización

---

## 📦 Dependencias principales

**Backend**
- `laravel/sanctum` → Autenticación por tokens
- `maatwebsite/excel` → Exportar a Excel

**Frontend**
- `vue` → Framework reactivo
- `vue-router` → Enrutamiento SPA
- `pinia` → Store de estado
- `axios` → Cliente HTTP

---

## 🧠 Flujo de la aplicación

```
[Vue Login] → POST /api/login → [Token JWT]
     ↓
[Dashboard] → GET /api/status → ¿está fichado?
     ↓
[Botón Entrada] → POST /api/clock-in → guarda clock_in + tarifa actual
     ↓
[Botón Salida]  → POST /api/clock-out → guarda clock_out + calcula horas
     ↓
[Admin Export]  → GET /api/admin/export?from=&to= → descarga .xlsx
```
