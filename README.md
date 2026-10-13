# Gojags Asrama

Sistem Manajemen Operasional Asrama GOJAGS (Pusdiklat BPS) berbasis web dengan pemisahan peran **Admin** (`admin`) dan **Penghuni** (`resident`) untuk pengelolaan alokasi kamar, check-in/check-out, izin keluar, keluhan fasilitas, laundry, serta catering dan rating.

- **Backend**: Laravel 10 JSON API (`routes/api.php`, sesi cookie + CSRF `web` middleware)
- **Frontend**: Nuxt 4 SPA (`frontend/`, Vue 3.5, TypeScript, Tailwind CSS v4)

## Cara Menjalankan

### 1. Persiapan Backend (Root Repo)

```powershell
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve --host=127.0.0.1 --port=8000
```

### 2. Persiapan Frontend (`frontend/`)

```powershell
npm --prefix frontend install
npm --prefix frontend run dev
```

Buka `http://127.0.0.1:3000` di peramban. Semua permintaan `/api/*` diteruskan oleh server proxy Nuxt (`frontend/server/routes/api/[...path].ts`) ke backend Laravel (`http://127.0.0.1:8000/api/*`).

## Akun Demo (Seed)

- **Admin**: `admin@example.id` / `admin123` (`role: admin`)
- **Penghuni**: `user@example.id` / `user123` (`role: resident`)
- **Penghuni tambahan**: `user1@example.id` s.d. `user10@example.id` / `user123` (`role: resident`)

## Pengujian

```powershell
# Backend (PHPUnit + Pint)
composer test
vendor/bin/pint --test

# Frontend (Typecheck + Vitest + Playwright)
npm --prefix frontend run typecheck
npm --prefix frontend run test:unit
npm --prefix frontend run test:browser
npm --prefix frontend run test:e2e
```
