# IndicLex

IndicLex lets users search, browse, compare, and validate dictionary entries across multiple Indic languages, with a full admin panel for managing dictionaries and entries.

---

## Features

### Public
| Page | Description |
|---|---|
| Home | Project overview and about section |
| Catalog | Browse all active dictionaries |
| Search | Search entries across dictionaries with autocomplete |
| Preferences | Set language and display preferences |
| Compare | Select two dictionaries and view shared entries, unique entries, and overlapping translations side-by-side |
| Validation | Run duplicate detection and missing-entry analysis against other dictionaries |
| Reports | Visual statistics — bar charts and pie charts for entry counts, dictionary types, and entry status using Chart.js |

### Admin (login required)
| Page | Description |
|---|---|
| Dashboard | Summary stats and quick links |
| Manage Dictionaries | Create, update, and delete dictionaries |
| Manage Entries | Create, update, and delete dictionary entries |
| Import | Upload `.xlsx` files to bulk-import entries |
| Export | Export dictionary data |

---

## Tech Stack

- **Backend:** PHP 8 (no framework)
- **Database:** MySQL via PDO
- **Frontend:** HTML, CSS, Bootstrap 5.3, vanilla JavaScript
- **Charts:** Chart.js (CDN)
- **Icons:** Font Awesome 4.7
- **Excel Import:** [PhpSpreadsheet](https://phpspreadsheet.readthedocs.io/) via Composer
- **Hosting:** Bluehost (live), XAMPP (local dev)

---

## Local Setup (XAMPP)

### Requirements
- XAMPP with Apache and MySQL
- PHP 8.0+
- Composer

### Steps

1. **Clone the repo** into your XAMPP `htdocs` folder:
   ```bash
   git clone <repo-url> xampp/htdocs/IndicLex
   ```

2. **Install Composer dependencies:**
   ```bash
   cd xampp/htdocs/IndicLex
   composer install
   ```

3. **Create the database:**
   - Open phpMyAdmin at `http://localhost/phpmyadmin`
   - Create a new database named `indiclex_db_d`
   - Import `indiclex_db_d.sql` (includes sample data) or `indiclex_db.sql` (schema only)

4. **Configure the database connection** in `config/database.php`:
   ```php
   $host     = "localhost";
   $dbname   = "indiclex_db_d";
   $username = "root";
   $password = "";        // your XAMPP MySQL password
   ```


---

## Bluehost Deployment

1. Upload all files to your Bluehost `public_html` directory (or a subdirectory).
2. Create a MySQL database and user via the Bluehost control panel.
3. Import `indiclex_db_d.sql` via phpMyAdmin.
4. Update `config/database.php` with your Bluehost database credentials.
5. `config/app.php` auto-detects `BASE_URL` from the request — no manual changes needed.

---

## Admin Access

Navigate to `/public/admin/login.php`. Admin accounts are managed directly in the `admins` table in the database. Passwords are hashed with `password_hash()`.

To generate a hashed password, use the included utility:
```
http://localhost/IndicLex/make_hash.php
```
Remove or restrict `make_hash.php` before deploying to production.

---

## Database

The main tables are:

| Table | Description |
|---|---|
| `dictionaries` | Dictionary metadata (name, type, is_active) |
| `dictionary_entries` | Entries with `lang_1`, `lang_2`, `lang_3` columns |
| `admins` | Admin user accounts |

---
