# VapeGeh — Vape Store Management System

Web + Android app for vape store management: POS cashier, multi-location inventory, bookkeeping, and reporting.

**Stack**: Laravel 13 · Filament 5 · Livewire 4 · Tailwind 4 · SQLite · Capacitor 8 (Android)

---

## Features

### POS / Cashier
- Product grid with real-time stock, location filter
- Multi-price: outlet price, default, reseller, price 1, price 2
- Cart with qty controls, live subtotals, change calculation
- Receipt: Bluetooth thermal printing (58/80mm), save as image, share

### Inventory
- Multi-location stock (warehouse + outlet)
- Stock in (from vendor) — auto average purchase price
- Stock out (to buyer) — supports additional costs
- Stock transfer between locations
- Stock opname — reconcile physical vs system stock, auto journal

### Bookkeeping
- Income & expense tracking per category
- Auto-journal from stock in/out/opname
- Sales report (POS + stock out combined)

### Reports & Export
- Sales, stock, bookkeeping reports
- Export PDF, Excel, CSV
- Period filter (today, 7 days, 30 days, all time)

### Settings
- Store info: name, address, phone, tagline, receipt footer
- Vendors, buyers, product categories, locations
- Users with Admin & Staff roles

---

## Prerequisites

- PHP 8.3+
- Node.js 22+
- Bun or npm
- Composer

## Installation

```bash
# Clone
git clone https://github.com/wevlra/VapeGeh.git
cd VapeGeh

# Setup (installs deps, migrates DB, builds assets)
composer run setup

# Start dev server
composer run dev
```

`composer run setup` will:
1. Install PHP & JS dependencies
2. Create `.env` from `.env.example`
3. Generate `APP_KEY`
4. Run database migrations (SQLite)
5. Generate Capacitor config from `APP_URL`
6. Build Vite assets

## Environment (.env)

```
APP_URL=http://localhost:8000
STORE_NAME="VapeGeh"
STORE_TAGLINE="Your Vaping Partner"
STORE_ADDRESS="..."
STORE_PHONE="0812-3456-7890"
STORE_RECEIPT_FOOTER="Terima kasih! Barang yang sudah dibeli tidak dapat dikembalikan."
```

## Android (Capacitor)

```bash
# Update WebView URL from APP_URL
php artisan capacitor:config
# or
composer capacitor

# Build APK
cd mobile
npx cap sync android
npx cap open android
```

## GitHub Actions

The CI workflow builds the Android APK on every push. Set the WebView URL via:

- **GitHub Variables** (recommended): `Settings → Actions → Variables → APP_URL`
- **Secrets** (fallback): `Settings → Actions → Secrets → APP_URL`

## Production

```bash
# Scheduler (required — cleans old generated reports every hour)
* * * * * cd /home/*/VapeGeh && php artisan schedule:run >> /dev/null 2>&1
```

## Project Structure

```
app/
├── Actions/              # Business logic (CreateSale, CompleteStockTransfer, etc.)
├── Filament/
│   ├── Admin/            # Admin panel — resources, pages, widgets
│   └── Staff/            # Staff panel — restricted access
├── Http/Controllers/     # Receipt & Invoice controllers
├── Models/               # Eloquent models
├── Settings/             # StoreSettings (database-backed)
└── Exports/              # Excel/CSV exports
config/store.php          # Store config (env fallback)
mobile/                   # Android app (Capacitor)
database/settings/        # Settings migrations
```
