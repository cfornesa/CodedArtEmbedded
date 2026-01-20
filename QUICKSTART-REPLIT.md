# CodedArt Quick Start Guide (Replit)

**For Development on Replit**

---

## 🚀 Step 1: Copy config.example.php to config.php

```bash
cp config/config.example.php config/config.php
```

**Then edit `config/config.php`** with your actual credentials:
- reCAPTCHA v3 keys (from https://www.google.com/recaptcha/admin)
- Email settings (optional for development)

---

## 🗄️ Step 2: Initialize the Database

**Option A: Interactive Setup (Recommended)**

```bash
php setup-database.php
```

This will:
- ✅ Detect your database type (SQLite for Replit)
- ✅ Check which tables are missing
- ✅ Offer to initialize the database
- ✅ Run the appropriate init script

**Option B: Direct Initialization**

```bash
php config/init_db_sqlite.php
```

This will create all required tables in SQLite.

---

## 👤 Step 3: Create Your First Admin User

Visit: **`/admin/register.php`**

Fill in:
- First Name
- Last Name
- Email (e.g., `admin@example.com`)
- Password (minimum 8 characters)

**Note:** The first user is auto-verified and can login immediately.

---

## ✅ Step 4: Verify Setup

**Option 1: Check Database**
```bash
php setup-database.php
```

Should show: ✓ Database is fully initialized!

**Option 2: Try Login**

Visit: **`/admin/login.php`**

Login with the credentials you just created.

---

## 🔧 Troubleshooting

### Error: "no such table: users"

**Cause:** Database not initialized

**Fix:** Run Step 2 above
```bash
php setup-database.php
```

### Error: "RECAPTCHA verification failed: invalid-keys"

**Cause:** Using v2 keys instead of v3, or keys not configured

**Fix:**
1. Get reCAPTCHA v3 keys from https://www.google.com/recaptcha/admin
2. Make sure to select **"Score based (v3)"** during setup
3. Add your Replit domain to the allowed domains
4. Update `config/config.php` with the keys

**Debug:** Visit `/admin/debug-recaptcha.php` to test your keys

### Database Auto-Detection Not Working

**Check:** What database type is being used?
```bash
php -r "
require 'config/config.php';
require 'config/database.php';
\$pdo = getDBConnection();
echo 'Using: ' . \$pdo->getAttribute(PDO::ATTR_DRIVER_NAME) . PHP_EOL;
"
```

Should output: `Using: sqlite`

---

## 📁 File Structure

```
CodedArtEmbedded/
├── config/
│   ├── config.php              ← YOU CREATE THIS (copy from config.example.php)
│   ├── config.example.php      ← Template
│   ├── init_db_sqlite.php      ← Run this to initialize SQLite
│   └── database.php            ← Auto-detects SQLite on Replit
├── setup-database.php          ← Interactive setup helper
├── admin/
│   ├── register.php            ← Create first admin user
│   ├── login.php               ← Login to admin panel
│   └── debug-recaptcha.php     ← Debug reCAPTCHA issues
└── codedart.db                 ← Created automatically by SQLite
```

---

## 🎯 Key Points for Replit

1. **No MySQL Needed:** Replit uses SQLite automatically
   - MySQL credentials in config.php are ignored
   - Domain auto-detection chooses SQLite for Replit

2. **Database File:** Located at `/workspace/codedart.db`
   - Created automatically on first connection
   - Tables created by init script

3. **reCAPTCHA Domain:** Changes on each restart!
   - Add `*.replit.dev` to reCAPTCHA domains
   - Or add `localhost` for testing

4. **Email (Optional):** Not required for development
   - Set `SEND_EMAIL_NOTIFICATIONS` to `false` in config.php
   - Registration still works without email

---

## 📝 Minimal config.php for Replit

```php
<?php
define('ENVIRONMENT', 'development');

// Database (auto-detects SQLite on Replit)
define('DB_PATH', __DIR__ . '/../codedart.db');
define('DB_HOST', 'localhost'); // Ignored
define('DB_NAME', 'codedart_db'); // Ignored
define('DB_USER', 'root'); // Ignored
define('DB_PASS', ''); // Ignored
define('DB_CHARSET', 'utf8mb4');

// reCAPTCHA v3 (REQUIRED)
define('RECAPTCHA_SITE_KEY', 'your_v3_site_key_here');
define('RECAPTCHA_SECRET_KEY', 'your_v3_secret_key_here');
define('RECAPTCHA_MIN_SCORE', 0.5);

// Email (optional for dev)
define('SMTP_HOST', 'mail.example.com');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');
define('SMTP_USERNAME', 'your@email.com');
define('SMTP_PASSWORD', 'your_password');
define('SMTP_FROM_EMAIL', 'your@email.com');
define('SMTP_FROM_NAME', 'CodedArt Admin');

// Security
define('SESSION_LIFETIME', 3600);
define('PASSWORD_MIN_LENGTH', 8);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900);

// CORS
define('CORS_PROXY_ENABLED', true);
define('CORS_CACHE_DIR', __DIR__ . '/../cache/cors/');
define('CORS_CACHE_LIFETIME', 86400);
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/webp', 'image/jpg']);

// Site
define('SITE_URL', 'https://your-repl.yourusername.repl.co');
define('ADMIN_URL', SITE_URL . '/admin');
define('TIMEZONE', 'America/New_York');

// Notifications
define('SEND_EMAIL_NOTIFICATIONS', false); // Disabled for dev
define('ADMIN_EMAIL', 'admin@example.com');

// Error handling
date_default_timezone_set(TIMEZONE);
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

---

## 🎓 Complete Setup Flow

```
1. Import from GitHub
   ↓
2. Copy config.example.php → config.php
   ↓
3. Edit config.php with reCAPTCHA keys
   ↓
4. Run: php setup-database.php
   ↓
5. Press 'Y' to initialize
   ↓
6. Visit /admin/register.php
   ↓
7. Create first admin account
   ↓
8. Done! 🎉
```

---

## ❓ Common Questions

**Q: Do I need MySQL on Replit?**
A: No! Auto-detection uses SQLite automatically.

**Q: Why does my Replit domain keep changing?**
A: Replit generates new domains. Use `*.replit.dev` in reCAPTCHA or add `localhost`.

**Q: Can I skip email configuration?**
A: Yes! Set `SEND_EMAIL_NOTIFICATIONS` to `false`.

**Q: How do I know if the database is initialized?**
A: Run `php setup-database.php` - it will tell you the status.

**Q: What if I get "no such table" error?**
A: Run Step 2 (database initialization) - you skipped it!

---

**Created:** 2026-01-20
**For:** Replit Development Environment
