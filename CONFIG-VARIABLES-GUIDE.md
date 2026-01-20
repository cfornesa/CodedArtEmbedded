# Configuration Variables Guide

**Updated:** 2026-01-20

---

## Your Questions Answered

### 1. ✅ reCAPTCHA v3 Fix

**Problem:** "ERROR for site owner: Invalid key type"

**Cause:** You have reCAPTCHA v2 keys but the code now uses v3 (score-based)

**Solution:** Get new reCAPTCHA v3 keys

#### Steps to Fix:

1. **Go to:** https://www.google.com/recaptcha/admin
2. **Click:** "+" to create a new site
3. **Configure:**
   - **Label:** CodedArt Registration
   - **reCAPTCHA type:** Select **"Score based (v3)"** ← CRITICAL
   - **Domains:** Add your domains:
     - `localhost` (for testing)
     - `codedart.org`
     - `augmenthumankind.com`
     - Your Replit domain (e.g., `codedartembedded.yourusername.repl.co`)
4. **Submit** and copy the keys
5. **Update config.php:**
```php
define('RECAPTCHA_SITE_KEY', 'your_v3_site_key_here');
define('RECAPTCHA_SECRET_KEY', 'your_v3_secret_key_here');
define('RECAPTCHA_MIN_SCORE', 0.5); // 0.0 = likely bot, 1.0 = likely human
```

**Files Updated:**
- `admin/register.php` - Now uses v3 API with score-based validation
- `config/config.example.php` - Added RECAPTCHA_MIN_SCORE setting

---

### 2. ✅ Hostinger SMTP with TLS

**Question:** Does Hostinger's SMTP server allow for TLS?

**Answer:** **YES!** Hostinger fully supports TLS on port 587 (recommended).

#### Hostinger SMTP Settings for contact@augmenthumankind.com:

```php
define('SMTP_HOST', 'mail.augmenthumankind.com'); // Use your domain
define('SMTP_PORT', 587); // TLS port (recommended)
define('SMTP_SECURE', 'tls'); // Use TLS encryption
define('SMTP_USERNAME', 'contact@augmenthumankind.com');
define('SMTP_PASSWORD', 'your_email_password'); // From cPanel
define('SMTP_FROM_EMAIL', 'contact@augmenthumankind.com');
define('SMTP_FROM_NAME', 'CodedArt Admin');
```

#### Alternative SSL Configuration (Port 465):

```php
define('SMTP_PORT', 465); // SSL port
define('SMTP_SECURE', 'ssl'); // Use SSL encryption
```

**Recommendation:** Use TLS on port 587 (more widely supported and modern).

**Where to Get Password:**
- Log into Hostinger cPanel
- Go to **Email Accounts**
- Find `contact@augmenthumankind.com`
- Click "Manage" → "Email Client Configuration"
- Copy the password (or reset if needed)

---

### 3. ✅ Replit Installation Without MySQL Details

**Question:** Does Replit work even if DB_HOST and other MySQL details are missing?

**Answer:** **YES!** With the new auto-detection feature, Replit automatically uses SQLite and ignores MySQL credentials.

#### How It Works:

1. **Domain Detection:** System checks `$_SERVER['HTTP_HOST']`
2. **Auto-Detection:**
   - `localhost` or Replit domains → SQLite
   - `codedart.org` → MySQL
3. **MySQL Credentials Ignored:** When using SQLite, DB_HOST/DB_NAME/DB_USER/DB_PASS are not checked

#### Minimal Replit config.php:

```php
<?php
define('ENVIRONMENT', 'development');

// SQLite Database (auto-detected on Replit)
define('DB_PATH', __DIR__ . '/../codedart.db');

// MySQL credentials (present but unused on Replit)
define('DB_HOST', 'localhost'); // Not used with SQLite
define('DB_NAME', 'codedart_db'); // Not used with SQLite
define('DB_USER', 'root'); // Not used with SQLite
define('DB_PASS', ''); // Not used with SQLite
define('DB_CHARSET', 'utf8mb4');

// ... rest of config
```

**Result:** Works perfectly on Replit even with dummy/empty MySQL credentials.

---

### 4. ✅ Config Variables: Keep or Delete?

#### Your Current List (with corrections):

| Variable | Status | Notes |
|----------|--------|-------|
| `ENVIRONMENT` | ✅ **KEEP - REQUIRED** | Auto-detected or manual |
| `DB_TYPE` | ✅ **KEEP - OPTIONAL** | Auto-detected if omitted |
| `DB_PATH` | ✅ **KEEP - REQUIRED for SQLite** | Path to SQLite database file |
| `DB_HOST` | ✅ **KEEP** | Required for MySQL, ignored for SQLite |
| `DB_NAME` | ✅ **KEEP** | Required for MySQL, ignored for SQLite |
| `DB_USER` | ✅ **KEEP** | Required for MySQL, ignored for SQLite |
| `DB_PASS` | ✅ **KEEP** | Required for MySQL, ignored for SQLite |
| `DB_CHARSET` | ✅ **KEEP** | Character encoding (utf8mb4) |
| `SMTP_HOST` | ✅ **KEEP - REQUIRED** | Your SMTP server hostname |
| `SMTP_PORT` | ✅ **KEEP - REQUIRED** | 587 (TLS) or 465 (SSL) |
| `SMTP_SECURE` | ✅ **KEEP - REQUIRED** | 'tls' or 'ssl' |
| `SMTP_USERNAME` | ✅ **KEEP - REQUIRED** | Email account username |
| `SMTP_PASSWORD` | ✅ **KEEP - REQUIRED** | Email account password |
| `SMTP_FROM_EMAIL` | ✅ **KEEP - REQUIRED** | From address for emails |
| `RECAPTCHA_SITE_KEY` | ✅ **KEEP - REQUIRED** | reCAPTCHA v3 public key |
| `RECAPTCHA_SECRET_KEY` | ✅ **KEEP - REQUIRED** | reCAPTCHA v3 secret key |
| `SESSION_LIFETIME` | ✅ **KEEP** | Session timeout (3600 = 1 hour) |
| `PASSWORD_MIN_LENGTH` | ✅ **KEEP** | Minimum password length (8) |
| `MAX_LOGIN_ATTEMPTS` | ✅ **KEEP** | Max failed logins before lockout (5) |
| `LOGIN_LOCKOUT_TIME` | ✅ **KEEP** | Lockout duration in seconds (900 = 15 min) |
| `CORS_PROXY_ENABLED` | ✅ **KEEP** | Enable/disable CORS proxy (true) |
| ~~`CROS_CACHE_DIR`~~ | ❌ **DELETE - TYPO** | You have a typo here! |
| `CORS_CACHE_DIR` | ✅ **ADD THIS** | Correct spelling (was CROS) |
| `CORS_CACHE_LIFETIME` | ✅ **KEEP** | Cache lifetime in seconds (86400 = 24h) |
| `ALLOWED_IMAGE_TYPES` | ✅ **KEEP** | Array of allowed MIME types |
| `SITE_URL` | ✅ **KEEP - REQUIRED** | Base URL of your site |
| `ADMIN_URL` | ✅ **KEEP - REQUIRED** | Admin panel URL |
| `TIMEZONE` | ✅ **KEEP - REQUIRED** | Your timezone (America/New_York) |
| `SEND_EMAIL_NOTIFICATIONS` | ✅ **KEEP** | Enable/disable email notifications |
| `ADMIN_EMAIL` | ✅ **KEEP - REQUIRED** | Admin email for notifications |

#### Variables Missing from Your List (Consider Adding):

| Variable | Importance | Purpose |
|----------|-----------|---------|
| `SMTP_FROM_NAME` | ⭐⭐⭐ **Recommended** | Friendly name in "From" field |
| `RECAPTCHA_MIN_SCORE` | ⭐⭐⭐ **Required for v3** | Minimum score threshold (0.5) |
| `DB_PORT` | ⭐⭐ **Recommended** | MySQL port (3306) |
| `DEBUG_MODE` | ⭐⭐ **Useful** | Show detailed errors in dev |
| `CORS_MAX_FILE_SIZE` | ⭐ Optional | Max file size for CORS proxy |
| `ALLOWED_IMAGE_EXTENSIONS` | ⭐ Optional | Array of allowed file extensions |

#### Variables You Can SAFELY OMIT:

The following are defined in `config.example.php` but are **optional** (have defaults):

- `PASSWORD_REQUIRE_UPPERCASE` - Defaults to true
- `PASSWORD_REQUIRE_LOWERCASE` - Defaults to true
- `PASSWORD_REQUIRE_NUMBER` - Defaults to true
- `PASSWORD_REQUIRE_SPECIAL` - Defaults to false
- `SESSION_NAME` - Defaults to 'codedart_session'
- `CSRF_TOKEN_LENGTH` - Defaults to 32
- `CSRF_TOKEN_LIFETIME` - Defaults to 3600
- `NOTIFICATION_BCC` - Optional BCC address
- `EMAIL_TEMPLATE_DIR` - Has default path
- `MAX_IMAGE_URL_LENGTH` - Defaults to 500
- `IMAGE_URL_VALIDATION` - Defaults to true
- `ALLOW_EXTERNAL_IMAGES` - Defaults to true
- `RATE_LIMIT_ENABLED` - Defaults to true
- `RATE_LIMIT_REQUESTS` - Defaults to 100
- `RATE_LIMIT_WINDOW` - Defaults to 3600
- All `TABLE_*` constants - Use defaults
- All `*_PATH` constants - Calculated automatically
- `CURRENT_DOMAIN` - Calculated automatically
- `SITE_DOMAIN` - Calculated automatically
- `FORCE_SQLITE_IN_PRODUCTION` - Only needed to force SQLite on production

---

## Recommended Minimal config.php

### For Replit (Development):

```php
<?php
// Environment
define('ENVIRONMENT', 'development');

// Database (SQLite - auto-detected)
define('DB_PATH', __DIR__ . '/../codedart.db');
define('DB_HOST', 'localhost'); // Ignored for SQLite
define('DB_NAME', 'codedart_db'); // Ignored for SQLite
define('DB_USER', 'root'); // Ignored for SQLite
define('DB_PASS', ''); // Ignored for SQLite
define('DB_CHARSET', 'utf8mb4');

// SMTP (Optional for dev - set to disable emails)
define('SMTP_HOST', 'mail.augmenthumankind.com');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');
define('SMTP_USERNAME', 'contact@augmenthumankind.com');
define('SMTP_PASSWORD', 'your_password');
define('SMTP_FROM_EMAIL', 'contact@augmenthumankind.com');
define('SMTP_FROM_NAME', 'CodedArt Admin');

// reCAPTCHA v3
define('RECAPTCHA_SITE_KEY', 'your_v3_site_key');
define('RECAPTCHA_SECRET_KEY', 'your_v3_secret_key');
define('RECAPTCHA_MIN_SCORE', 0.5);

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

// Site URLs
define('SITE_URL', 'https://yourrepl.yourusername.repl.co');
define('ADMIN_URL', SITE_URL . '/admin');
define('TIMEZONE', 'America/New_York');

// Notifications
define('SEND_EMAIL_NOTIFICATIONS', false); // Disable in dev
define('ADMIN_EMAIL', 'contact@augmenthumankind.com');

// Error handling
date_default_timezone_set(TIMEZONE);
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

### For Hostinger (Production):

```php
<?php
// Environment
define('ENVIRONMENT', 'production');

// Database (MySQL - auto-detected on codedart.org)
define('DB_HOST', 'localhost');
define('DB_NAME', 'u123456789_codedart'); // Your database name
define('DB_USER', 'u123456789_admin'); // Your database user
define('DB_PASS', 'your_db_password'); // Your database password
define('DB_CHARSET', 'utf8mb4');

// SMTP (Hostinger Email with TLS)
define('SMTP_HOST', 'mail.augmenthumankind.com');
define('SMTP_PORT', 587); // TLS
define('SMTP_SECURE', 'tls');
define('SMTP_USERNAME', 'contact@augmenthumankind.com');
define('SMTP_PASSWORD', 'your_email_password'); // From cPanel
define('SMTP_FROM_EMAIL', 'contact@augmenthumankind.com');
define('SMTP_FROM_NAME', 'CodedArt Admin');

// reCAPTCHA v3 (Production Keys)
define('RECAPTCHA_SITE_KEY', 'your_v3_production_site_key');
define('RECAPTCHA_SECRET_KEY', 'your_v3_production_secret_key');
define('RECAPTCHA_MIN_SCORE', 0.5);

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

// Site URLs
define('SITE_URL', 'https://codedart.org');
define('ADMIN_URL', SITE_URL . '/admin');
define('TIMEZONE', 'America/New_York');

// Notifications
define('SEND_EMAIL_NOTIFICATIONS', true); // Enable in production
define('ADMIN_EMAIL', 'contact@augmenthumankind.com');

// Error handling
date_default_timezone_set(TIMEZONE);
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php_errors.log');
```

---

## Summary

### ✅ Actions Required:

1. **Fix reCAPTCHA:**
   - Create NEW reCAPTCHA v3 keys at https://www.google.com/recaptcha/admin
   - Select "Score based (v3)" during setup
   - Update config.php with v3 keys
   - Add `RECAPTCHA_MIN_SCORE` (0.5 recommended)

2. **Fix Typo:**
   - Change `CROS_CACHE_DIR` → `CORS_CACHE_DIR` in your config.php

3. **Email Configuration:**
   - Hostinger DOES support TLS on port 587 ✅
   - Use `SMTP_HOST: mail.augmenthumankind.com`
   - Use `SMTP_USERNAME: contact@augmenthumankind.com`
   - Get password from Hostinger cPanel → Email Accounts

4. **Replit Configuration:**
   - Works perfectly WITHOUT valid MySQL credentials ✅
   - Auto-detects SQLite on localhost/Replit
   - MySQL details can remain as dummy values

### 📋 Config Variables Decision:

**KEEP ALL** variables from your list (they're all used), EXCEPT:
- ❌ Delete: `CROS_CACHE_DIR` (typo)
- ✅ Add: `CORS_CACHE_DIR` (correct spelling)
- ✅ Add: `SMTP_FROM_NAME` (missing but useful)
- ✅ Add: `RECAPTCHA_MIN_SCORE` (required for v3)

**All other variables are actively used by the system and should be retained.**

---

## Testing After Changes

```bash
# 1. Test auto-detection
php test_auto_detection.php

# 2. Test database connection
php -r "
require 'config/config.php';
require 'config/database.php';
\$pdo = getDBConnection();
echo 'Connected: ' . \$pdo->getAttribute(PDO::ATTR_DRIVER_NAME) . PHP_EOL;
"

# 3. Test registration page
# Visit: /admin/register.php
# - Should NOT show "Invalid key type" error
# - Should show no visible reCAPTCHA (v3 is invisible)
# - Check browser console for reCAPTCHA v3 execution
```

---

**Files Modified:**
- `admin/register.php` - Updated to reCAPTCHA v3
- `config/config.example.php` - Updated defaults for your setup
