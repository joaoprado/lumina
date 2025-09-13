# Lumina – Fintech Assets Explorer

A full‑stack Laravel 12 + Vue 3 (Inertia) application that lists cryptocurrency assets via CoinGecko, shows detailed information, lets users favorite assets (SQLite), and displays a 7‑day sparkline chart through an isolated React widget.

This repository implements the core features from the technical assessment plus a few optional extras. It is designed to run locally without Docker or external databases.


## Stack
- Backend: Laravel 12 (PHP ^8.2)
- Frontend: Vue 3 + TypeScript + Inertia.js, Vite ^7
- Styling: Tailwind CSS v4 (via @tailwindcss/vite)
- Data Source: CoinGecko public API (no auth)
- Persistence: SQLite (favorites)
- Tests: PHPUnit 11 (sqlite in‑memory)
- Optional extra: React 18 island for sparkline chart (@vitejs/plugin-react)

Octane: Laravel Octane is installed in this project. You can run the app using Octane for a high‑performance dev server (see the Octane section below).


## Features
- GET /api/assets – Top 10 assets with price and image (cached ~60s). Includes is_favorite flag derived from persistence.
- GET /api/assets/{id} – Asset details: name, symbol, large image, USD price, 24h change (cached ~60s).
- GET /api/assets/{id}/history?days=7 – Historical USD prices normalized as { timestamp, price } (cached ~60s).
- Favorites API (SQLite):
  - POST /api/favorites { assetId }
  - GET /api/favorites
  - DELETE /api/favorites/{assetId}
- Pages (Inertia):
  - Home: lists assets, favorite/unfavorite action.
  - Favorites: lists favorites with unfavorite action.
  - Asset Details: shows core info, 7‑day sparkline chart (React), and a historical list below the chart.


## Quick Start
1) Install dependencies
- PHP deps: composer install
- JS deps: npm install

2) Environment
- cp .env.example .env
- php artisan key:generate

3) SQLite database
- touch database/database.sqlite
- Ensure .env contains:
  - DB_CONNECTION=sqlite
  - DB_DATABASE=./database/database.sqlite
  - QUEUE_CONNECTION=sync
  - CACHE_STORE=file (for development)
- Run migrations: php artisan migrate

4) Run in development
- Option A (Octane, recommended): composer run dev:octane
  - Runs: php artisan octane:start --watch, pail logs, and npm run dev (Vite) concurrently
- Option B (classic): composer run dev
  - Runs: php artisan serve, pail logs, and npm run dev (Vite) concurrently
- Manual alternatives:
  - Terminal A: php artisan octane:start --watch (or php artisan serve)
  - Terminal B: npm run dev

5) Open the app
- Visit http://localhost:8000


## Testing
- Run all tests: php artisan test
- Select tests:
  - By file: php artisan test tests/Feature/AssetsApiTest.php
  - By filter: php artisan test --filter=AssetHistoryApiTest

Test configuration (phpunit.xml) uses an in‑memory sqlite database and array cache. CoinGecko calls are faked with Http::fake() to ensure determinism.


## API Overview (internal)
- GET /api/assets
  - Returns: [{ id, name, symbol, image, current_price, price_change_percentage_24h, is_favorite }]
  - Cache: 60s
- GET /api/assets/{id}
  - Returns: { id, name, symbol, image, market_data: { current_price: { usd }, price_change_percentage_24h }, description? }
  - Cache: 60s
- GET /api/assets/{id}/history?days=7
  - Returns: { id, days, prices: [{ timestamp, price }] }
  - Cache: 60s
- Favorites
  - POST /api/favorites { assetId }
  - GET /api/favorites -> [{ asset_id, created_at }]
  - DELETE /api/favorites/{assetId}


## Extras Implemented
- React island: A sparkline chart is rendered in the Asset Details page via a small React component mounted from a Vue wrapper. Development includes the React Fast Refresh preamble for Vite (@viteReactRefresh) in the Blade layout.
- History chart: 7‑day price history endpoint and UI. The list is displayed below the chart in a separate card and is sorted newest‑first, while the chart maintains natural left‑to‑right order.


## Octane
Laravel Octane is installed in this project. To run with Octane locally you still need one of the supported servers installed (Swoole PHP extension, RoadRunner binary, or FrankenPHP). If none are present, you can continue using the classic PHP server.

Usage:
- Install a server (pick one):
  - Swoole: pecl install swoole && enable in php.ini
  - RoadRunner: see https://roadrunner.dev for installation (rr binary)
  - FrankenPHP: see https://frankenphp.dev
- Start Octane: php artisan octane:start --watch
- Or use the convenience script: composer run dev:octane

Notes:
- Avoid sharing non‑bootstrapped state across requests. The app primarily uses Laravel services and short‑lived caches.
- You can switch back to the classic server anytime using composer run dev.


## Troubleshooting
- 500 on '/': Ensure APP_KEY is set (php artisan key:generate)
- sqlite driver missing: enable pdo_sqlite and sqlite3 in php.ini
- Vite HMR not updating: ensure npm run dev is active and APP_URL is correct
- CoinGecko rate limits: responses are cached ~60s; clear cache with php artisan cache:clear during development


