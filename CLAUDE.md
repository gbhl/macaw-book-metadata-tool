# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Macaw is a PHP/CodeIgniter 3 web application for collecting page-level metadata of digitized book-like objects (originally developed for Smithsonian Institution Libraries) for sharing with systems like the Internet Archive and Biodiversity Heritage Library (BHL).

## Environment & Setup

- **PHP** with Apache (mod_rewrite and mod_headers required)
- **MySQL** database (also has PostgreSQL SQL scripts in `application/sql/`)
- **ImageMagick**, CURL, PHP XSL extension, PHP PECL zip module required
- Environment is controlled via `CI_ENV` server variable (`development`, `testing`, or `production`)
- Config files: copy `*.default.php` → `*.php` (e.g., `application/config/config.default.php` → `config.php`). The three config files that are never overwritten on update are `config.php`, `macaw.php`, and `database.php`.

## Running / Deployment

There is no build step. Macaw is served directly by Apache. Access via browser after configuring Apache with `AllowOverride All`.

**Cron jobs** (run via CLI using `cron.php`):
```
php /path/to/cron.php --run=/cron/statistics --quiet
php /path/to/cron.php --run=/cron/calculate_sizes --quiet
php /path/to/cron.php --run=/cron/new_items --quiet
php /path/to/cron.php --run=/cron/export --quiet
```

**Database setup/upgrade**: Visit `install.php` for new installs. From v1.7+, DB upgrades run automatically on first page load. Manual SQL migration scripts are in `application/sql/`.

## Architecture

### CodeIgniter 3 MVC Structure

- `application/controllers/` — HTTP request handlers (Admin, Login, Main, Scan, Dashboard, Cron, etc.)
- `application/models/` — Database models: `Book`, `Exporter`, `Importer`, `Organization`, `User`
- `application/views/` — PHP view templates organized by controller name
- `application/libraries/` — Custom CI libraries autoloaded globally: `Common`, `Logging`, `Authentication`, `Bhl`, `Clicheck`, `Image_IPTC`
- `application/config/macaw.php` — Primary Macaw-specific configuration (paths, export modules, metadata fields, email, etc.)
- `application/classes/` — Standalone PHP classes (e.g., `ParseCSV.php`)

### Plugin System

`plugins/` contains three types of pluggable modules, each requiring both a `.php` and `.js` file:
- `plugins/export/` — Export modules (e.g., `Internet_archive.php`, `Isilon_archive.php`). Configured via `$config['macaw']['export_modules']` array.
- `plugins/import/` — Import modules. Configured via `$config['macaw']['import_modules']`.
- `plugins/metadata/` — Metadata form modules (e.g., `Standard_Metadata`, `BHL_Segments`). Configured via `$config['macaw']['metadata_modules']`.

See `plugins/export/export.default.php` and `plugins/metadata/_metadata-sample.*` for templates when creating new plugins.

### Book Workflow State Machine

Items (books) progress through statuses: `new` → `scanning` → `scanned` → `reviewing` → `qa-ready` → `qa-active` → `reviewed` → `exporting` → `completed` → `archived`. The `Main` controller redirects users based on the current book status stored in session.

### Key Configuration

The `$config['macaw']` array (in `application/config/macaw.php`) controls:
- File system paths: `base_directory`, `data_directory`, `incoming_directory`
- URL patterns for scans/previews/thumbnails (use `BARCODE` placeholder)
- Active export/import/metadata plugin modules
- Email SMTP settings
- Disk quota thresholds

### Autoloaded Resources

Every request loads: `database`, `session`, `common`, `logging`, `clicheck`, `bhl` libraries; `url`, `form`, `breadcrumb`, `file`, `directory`, `date` helpers; and the `macaw` config.

### Books & Incoming Directories

`/books/` and `/incoming/` are **not** in version control and are never overwritten by updates. `books/BARCODE/scans/`, `books/BARCODE/preview/`, and `books/BARCODE/thumbs/` must be web-accessible.

## Current Migration

This branch (`CI3-claude`) is actively migrating from CodeIgniter 2 to CodeIgniter 3. When modifying controllers or models, use CI3 patterns (e.g., `defined('BASEPATH') OR exit(...)` instead of `exit('No direct...')`).
