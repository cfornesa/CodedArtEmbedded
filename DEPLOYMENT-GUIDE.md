# CodedArtEmbedded Deployment Guide
**Updated:** 2026-01-20
**Branch:** `claude/consolidate-duplicate-variables-c0kaZ`

## Quick Summary

✅ **ALL ISSUES RESOLVED**
- Database initialization now works on Replit (SQLite)
- Gallery pages load correctly (config.php loading fixed)
- Slug system fully functional
- All 11 art pieces seeded with auto-generated slugs

---

## Part 1: Deploy to Replit (Development)

### Step 1: Import from GitHub

1. Go to **https://replit.com/**
2. Click **"Create Repl"** → **"Import from GitHub"**
3. Enter:
   - **Repository URL:** `https://github.com/cfornesa/CodedArtEmbedded`
   - **Branch:** `claude/consolidate-duplicate-variables-c0kaZ`
4. Click **"Import from GitHub"**

### Step 2: Create config.php

⚠️ **CRITICAL:** The `config/config.php` file is NOT in Git (security)

In Replit Shell:

```bash
cp config/config.example.php config/config.php
```

Edit `config/config.php` with:

```php
<?php
// Replit Development Configuration

define('ENVIRONMENT', 'development');

// SQLite Database (Replit)
define('DB_TYPE', 'sqlite');
define('DB_PATH', __DIR__ . '/../codedart.db');
define('DB_HOST', '');
define('DB_NAME', '');
define('DB_USER', '');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// SMTP (Optional for development)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');
define('SMTP_FROM_EMAIL', 'admin@codedart.org');
define('SMTP_FROM_NAME', 'CodedArt Admin');

// reCAPTCHA (Get free keys from google.com/recaptcha)
define('RECAPTCHA_SITE_KEY', 'your-recaptcha-site-key');
define('RECAPTCHA_SECRET_KEY', 'your-recaptcha-secret-key');

// Security
define('SESSION_LIFETIME', 3600);
define('PASSWORD_MIN_LENGTH', 8);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900);

// CORS Proxy
define('CORS_PROXY_ENABLED', true);
define('CORS_CACHE_DIR', __DIR__ . '/../cache/cors/');
define('CORS_CACHE_LIFETIME', 86400);
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/webp', 'image/jpg']);

// Application
define('SITE_URL', 'https://codedartembedded.your-username.repl.co');
define('ADMIN_URL', SITE_URL . '/admin');
define('TIMEZONE', 'America/New_York');

// Notifications
define('SEND_EMAIL_NOTIFICATIONS', false); // Disabled for dev
define('ADMIN_EMAIL', 'admin@codedart.org');

date_default_timezone_set(TIMEZONE);

// Development error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

### Step 3: Initialize Database

Run these commands in Replit Shell:

```bash
# 1. Initialize database (SQLite version)
php config/init_db_sqlite.php

# 2. Add slug system
php config/migrate_add_slugs_sqlite.php

# 3. Seed with art pieces
php config/seed_data.php

# 4. Generate slugs for seeded pieces
php -r "
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'config/slug_utils.php';
\$pdo = getDBConnection();
\$tables = ['aframe_art', 'c2_art', 'p5_art', 'threejs_art'];
foreach (\$tables as \$table) {
    \$type = str_replace('_art', '', \$table);
    \$stmt = \$pdo->query(\"SELECT id, title FROM \$table WHERE slug IS NULL\");
    foreach (\$stmt->fetchAll() as \$piece) {
        \$slug = generateUniqueSlug(\$piece['title'], \$type, \$piece['id']);
        \$pdo->prepare(\"UPDATE \$table SET slug = ? WHERE id = ?\")->execute([\$slug, \$piece['id']]);
        echo \"✓ Slug: \$slug\n\";
    }
}
"

# 5. Create cache directories
mkdir -p cache/cors logs
chmod 755 cache/cors logs
```

**Expected Output:**
```
✅ Database connection successful
✅ Table 'aframe_art' created successfully
✅ Table 'c2_art' created successfully
✅ Table 'p5_art' created successfully
✅ Table 'threejs_art' created successfully
✅ Table 'users' created successfully
✅ Table 'site_config' created successfully
✅ Table 'activity_log' created successfully
✅ Database initialization complete!
```

### Step 4: Test the Application

Click **"Run"** button in Replit.

**Test URLs:**
```
Main Site:     https://your-repl-name.your-username.repl.co/
A-Frame:       /a-frame/
C2:            /c2/
P5:            /p5/
Three.js:      /three-js/
Admin Login:   /admin/login.php
```

**Test Checklist:**
- [ ] Homepage loads without errors
- [ ] All 4 gallery pages load (no "config not loaded" error)
- [ ] Gallery pages show art pieces from database
- [ ] Admin login page accessible
- [ ] Can access `/admin/register.php`

### Step 5: Create Admin User

**Option A: Via Registration Page**
1. Go to `/admin/register.php`
2. Enter email and password
3. Complete reCAPTCHA (if configured)
4. Login at `/admin/login.php`

**Option B: Direct Database Insert (Dev Only)**
```bash
php -r "
require 'config/config.php';
require 'config/database.php';
\$pdo = getDBConnection();
\$hash = password_hash('admin123', PASSWORD_BCRYPT);
\$stmt = \$pdo->prepare('INSERT INTO users (email, password_hash, first_name, last_name, status, email_verified) VALUES (?, ?, ?, ?, ?, ?)');
\$stmt->execute(['admin@codedart.org', \$hash, 'Admin', 'User', 'active', 1]);
echo \"✓ Admin user created: admin@codedart.org / admin123\n\";
"
```

### Step 6: Test Slug System with Custom Slug

1. Login to admin: `/admin/login.php`
2. Navigate to: `/admin/aframe.php?action=create`
3. Fill in form:
   - **Title:** "My Test Piece"
   - **Slug:** "piece-1" (custom slug)
   - **Description:** "Testing custom slug"
   - **File Path:** "/a-frame/test.php"
   - **Status:** Active
4. Submit form
5. Verify:
   - Piece appears in list with slug: `piece-1`
   - Can access via: `/a-frame/piece-1` (after creating actual file)

---

## Part 2: Deploy to Hostinger (Production)

### Step 1: Download from Replit

In Replit Shell:

```bash
# Create deployment package
zip -r codedart-hostinger.zip . \
  -x "*.git*" \
  -x "*codedart.db*" \
  -x "*cache/*" \
  -x "*logs/*"
```

Download the zip file:
1. In Replit Files panel
2. Find `codedart-hostinger.zip`
3. Right-click → Download

### Step 2: Set Up Hostinger Database

Log into **Hostinger cPanel** → **MySQL Databases**

1. **Create Database:**
   - Name: `u123456789_codedart`
   - Click "Create"

2. **Create User:**
   - Username: `u123456789_admin`
   - Password: [Generate strong password]
   - Click "Create User"

3. **Add User to Database:**
   - Grant ALL PRIVILEGES
   - Click "Add"

**Save these credentials:**
```
DB_HOST: localhost
DB_NAME: u123456789_codedart
DB_USER: u123456789_admin
DB_PASS: [your generated password]
```

### Step 3: Upload to Hostinger

**File Manager** → `public_html/`:

1. Click "Upload"
2. Select `codedart-hostinger.zip`
3. Wait for upload
4. Right-click zip → "Extract"
5. Extract to `/public_html/`
6. Delete zip file

### Step 4: Create config.php on Hostinger

Create `/public_html/config/config.php`:

```php
<?php
// Hostinger Production Configuration

define('ENVIRONMENT', 'production');

// MySQL Database (Hostinger)
define('DB_TYPE', 'mysql');
define('DB_HOST', 'localhost');
define('DB_NAME', 'u123456789_codedart');  // ← Your DB name
define('DB_USER', 'u123456789_admin');     // ← Your DB user
define('DB_PASS', 'YOUR_DB_PASSWORD');     // ← Your DB password
define('DB_CHARSET', 'utf8mb4');

// SMTP (Hostinger Email)
define('SMTP_HOST', 'smtp.hostinger.com');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');
define('SMTP_USERNAME', 'admin@codedart.org');
define('SMTP_PASSWORD', 'YOUR_EMAIL_PASSWORD');
define('SMTP_FROM_EMAIL', 'admin@codedart.org');
define('SMTP_FROM_NAME', 'CodedArt Admin');

// reCAPTCHA (Production keys)
define('RECAPTCHA_SITE_KEY', 'your-production-site-key');
define('RECAPTCHA_SECRET_KEY', 'your-production-secret-key');

// Security
define('SESSION_LIFETIME', 3600);
define('PASSWORD_MIN_LENGTH', 8);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900);

// CORS Proxy
define('CORS_PROXY_ENABLED', true);
define('CORS_CACHE_DIR', __DIR__ . '/../cache/cors/');
define('CORS_CACHE_LIFETIME', 86400);
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/webp', 'image/jpg']);

// Application
define('SITE_URL', 'https://codedart.org');
define('ADMIN_URL', SITE_URL . '/admin');
define('TIMEZONE', 'America/New_York');

// Notifications
define('SEND_EMAIL_NOTIFICATIONS', true); // Enabled for prod
define('ADMIN_EMAIL', 'admin@codedart.org');

date_default_timezone_set(TIMEZONE);

// Production error handling (log, don't display)
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php_errors.log');
```

**Set Permissions:**
```bash
chmod 600 config/config.php
```

### Step 5: Initialize MySQL Database

Via **SSH** or **cPanel Terminal**:

```bash
cd public_html

# Initialize MySQL database (uses init_db.php, NOT init_db_sqlite.php)
php config/init_db.php

# Run slug migration
php config/migrate_add_slugs.php

# Seed data
php config/seed_data.php

# Generate slugs
php -r "
require 'config/config.php';
require 'config/database.php';
require 'config/slug_utils.php';
\$pdo = getDBConnection();
foreach (['aframe_art', 'c2_art', 'p5_art', 'threejs_art'] as \$table) {
    \$type = str_replace('_art', '', \$table);
    \$stmt = \$pdo->query(\"SELECT id, title FROM \$table WHERE slug IS NULL\");
    foreach (\$stmt->fetchAll() as \$p) {
        \$slug = generateUniqueSlug(\$p['title'], \$type, \$p['id']);
        \$pdo->prepare(\"UPDATE \$table SET slug=? WHERE id=?\")->execute([\$slug, \$p['id']]);
    }
}
"

# Create directories
mkdir -p cache/cors logs
chmod 755 cache/cors logs
```

### Step 6: Set Up Cron Job

**Hostinger cPanel** → **Cron Jobs**:

- **Schedule:** `0 2 * * *` (daily at 2 AM)
- **Command:**
  ```bash
  /usr/bin/php /home/u123456789/public_html/config/cleanup_old_slugs.php
  ```

### Step 7: Configure SSL & Domain

**cPanel** → **SSL/TLS**:
1. Install free Let's Encrypt certificate
2. Force HTTPS redirect
3. Update DNS if using custom domain

**Test Production Site:**
```
https://codedart.org/
https://codedart.org/a-frame/
https://codedart.org/admin/login.php
```

---

## Part 3: Using Custom Slugs

### Example: Creating Piece with Slug "piece-1"

**Admin Flow:**

1. **Login:** `/admin/login.php`
2. **Navigate:** `/admin/aframe.php?action=create`
3. **Fill Form:**
   ```
   Title: My Art Piece
   Slug: piece-1                    ← Custom slug
   Description: Description here
   File Path: /a-frame/piece-1.php
   Thumbnail URL: https://...
   Status: Active
   ```
4. **Submit:** Piece created with slug "piece-1"

**Result:**
- **Database:** `aframe_art` table, `slug='piece-1'`
- **Admin List:** Shows slug in table
- **Public URL:** `/a-frame/piece-1`

### Editing Slug (Creates Redirect)

1. **Edit Piece:** `/admin/aframe.php?action=edit&id=1`
2. **Change Slug:** From "piece-1" to "new-piece-1"
3. **Submit**

**Result:**
- **Database Updates:** `slug='new-piece-1'`
- **Redirect Created:** `slug_redirects` table maps `piece-1` → `new-piece-1`
- **Old URL Works:** `/a-frame/piece-1` automatically redirects to `/a-frame/new-piece-1`

### Soft Deleting Piece

1. **Click Delete:** In admin list
2. **Confirm:** Piece soft-deleted

**Result:**
- **Database:** `deleted_at='2026-01-20 13:00:00'`
- **Slug Reserved:** 30 days (configurable)
- **Deleted Items:** View at `/admin/deleted.php?type=aframe`
- **Can Restore:** Within 30 days

### Restoring Deleted Piece

1. **Navigate:** `/admin/deleted.php?type=aframe`
2. **Click Restore:** On piece
3. **Confirm**

**Result:**
- **Database:** `deleted_at=NULL`, `status='draft'`
- **Back in List:** Appears in active pieces
- **URL Works Again:** `/a-frame/new-piece-1`

---

## Environment Differences

| Feature | Replit (Dev) | Hostinger (Prod) |
|---------|-------------|------------------|
| **Database** | SQLite | MySQL |
| **Init Script** | `init_db_sqlite.php` | `init_db.php` |
| **Migration** | `migrate_add_slugs_sqlite.php` | `migrate_add_slugs.php` |
| **DB File** | `codedart.db` | Remote MySQL |
| **Email** | Disabled | Enabled (SMTP) |
| **Errors** | Displayed | Logged to file |
| **HTTPS** | Repl.co domain | Custom + SSL |
| **Cron** | Manual | Automated |

---

## Troubleshooting

### Issue: "Configuration not loaded"
**Fixed:** Gallery pages now load `config.php` before `database.php`

### Issue: Database tables not created
**Fixed:** Use `init_db_sqlite.php` for Replit (not `init_db.php`)

### Issue: Pieces have no slugs
**Run:**
```bash
php -r "
require 'config/config.php';
require 'config/database.php';
require 'config/slug_utils.php';
\$pdo = getDBConnection();
foreach (['aframe_art','c2_art','p5_art','threejs_art'] as \$t) {
    \$type = str_replace('_art','',\$t);
    foreach (\$pdo->query(\"SELECT id,title FROM \$t WHERE slug IS NULL\")->fetchAll() as \$p) {
        \$slug = generateUniqueSlug(\$p['title'],\$type,\$p['id']);
        \$pdo->prepare(\"UPDATE \$t SET slug=? WHERE id=?\")->execute([\$slug,\$p['id']]);
        echo \"✓ \$slug\n\";
    }
}
"
```

### Issue: Permission denied on cache/logs
```bash
chmod 755 cache/cors logs
```

---

## Success Checklist

### Replit (Dev)
- [ ] Imported from GitHub
- [ ] Created `config/config.php`
- [ ] Ran `init_db_sqlite.php` ✓
- [ ] Ran `migrate_add_slugs_sqlite.php` ✓
- [ ] Ran `seed_data.php` ✓
- [ ] Generated slugs for pieces ✓
- [ ] Homepage loads
- [ ] Gallery pages load (no config error)
- [ ] Admin login accessible
- [ ] Can create piece with custom slug "piece-1"

### Hostinger (Prod)
- [ ] Created MySQL database
- [ ] Uploaded files via zip
- [ ] Created `config/config.php`
- [ ] Ran `init_db.php` (MySQL version)
- [ ] Ran `migrate_add_slugs.php` (MySQL version)
- [ ] Seeded and generated slugs
- [ ] Set up cron job
- [ ] Configured SSL
- [ ] Tested all URLs
- [ ] Email notifications working

---

## Quick Reference

**Replit Commands:**
```bash
# Fresh start
rm -f codedart.db
php config/init_db_sqlite.php
php config/migrate_add_slugs_sqlite.php
php config/seed_data.php

# Test slug system
php test_slug_core.php
```

**Hostinger Commands:**
```bash
# Initialize
php config/init_db.php
php config/migrate_add_slugs.php
php config/seed_data.php

# Cron job path
/usr/bin/php /home/u123456789/public_html/config/cleanup_old_slugs.php
```

---

**Status:** ✅ ALL SYSTEMS OPERATIONAL
**Last Updated:** 2026-01-20
**Contact:** Review PHASE7-SLUG-SYSTEM.md for detailed documentation
