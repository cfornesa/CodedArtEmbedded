# Configuration Setup Guide

This guide explains how to properly configure the CodedArt application for both development (Replit) and production (Hostinger) environments.

## 🔒 Security Notice

**CRITICAL:** The `config.php` file contains sensitive credentials and **MUST NEVER** be committed to Git. It is already excluded in `.gitignore`.

---

## 📋 Quick Setup

### Step 1: Create Your Config File

```bash
# Copy the example config to create your actual config file
cp config/config.example.php config/config.php
```

### Step 2: Edit config.php

Open `config/config.php` and replace **ALL** placeholder values with your actual credentials.

---

## 🔧 Configuration Sections

### 1. Database Configuration

#### For Hostinger (Production):
1. Log into your Hostinger cPanel
2. Go to **MySQL Databases**
3. Create a new database or note your existing one
4. Create a database user with a strong password
5. Grant all privileges to the user for that database
6. Fill in `config.php`:

```php
define('DB_HOST', 'localhost'); // Usually localhost
define('DB_NAME', 'u123456789_codedart'); // Your database name
define('DB_USER', 'u123456789_codedart'); // Your database user
define('DB_PASS', 'YourStrongPassword123!'); // Your database password
```

#### For Replit (Development):
Replit typically uses MySQL with root access:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'codedart_db');
define('DB_USER', 'root');
define('DB_PASS', ''); // Usually empty on Replit
```

### 2. SMTP Email Configuration

You need an SMTP server to send email notifications. Options include:

#### Option A: Hostinger Email
1. Log into Hostinger cPanel
2. Go to **Email Accounts**
3. Create email: `admin@codedart.org`
4. Note the SMTP settings (usually shown in email client setup)
5. Fill in `config.php`:

```php
define('SMTP_HOST', 'mail.codedart.org'); // From Hostinger
define('SMTP_PORT', 587); // Usually 587 for TLS
define('SMTP_SECURE', 'tls'); // TLS encryption
define('SMTP_USERNAME', 'admin@codedart.org');
define('SMTP_PASSWORD', 'YourEmailPassword');
```

#### Option B: Gmail (Development)
1. Enable 2-factor authentication on your Google account
2. Generate an App Password: https://myaccount.google.com/apppasswords
3. Use these settings:

```php
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');
define('SMTP_USERNAME', 'youremail@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');
```

#### Option C: SendGrid (Recommended for Production)
1. Sign up at https://sendgrid.com (free tier available)
2. Create an API key
3. Use these settings:

```php
define('SMTP_HOST', 'smtp.sendgrid.net');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');
define('SMTP_USERNAME', 'apikey'); // Literally "apikey"
define('SMTP_PASSWORD', 'SG.xxxxxxxxxxxxx'); // Your API key
```

### 3. Google reCAPTCHA Configuration

1. Go to https://www.google.com/recaptcha/admin
2. Click **+** to register a new site
3. Choose **reCAPTCHA v3** (recommended - invisible)
4. Add your domains:
   - `codedart.org`
   - `codedart.cfornesa.com`
   - `codedart.fornesus.com`
   - `localhost` (for development)
5. Accept terms and submit
6. Copy your **Site Key** and **Secret Key**
7. Fill in `config.php`:

```php
define('RECAPTCHA_SITE_KEY', '6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI');
define('RECAPTCHA_SECRET_KEY', '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe');
```

**Note:** The keys above are Google's test keys. Use them for development, but create real keys for production.

### 4. Site URL Configuration

The application auto-detects the environment, but you can customize:

#### Production (Hostinger):
```php
define('SITE_URL', 'https://codedart.org');
define('SITE_DOMAIN', 'codedart.org');
```

#### Development (Replit):
```php
define('SITE_URL', 'http://localhost:8000');
define('SITE_DOMAIN', 'localhost');
```

---

## 🚀 Environment-Specific Setup

### Replit Setup

1. **Copy config file:**
   ```bash
   cp config/config.example.php config/config.php
   ```

2. **Edit config.php** with Replit-specific settings:
   - Database: Usually `root` user with empty password
   - SMTP: Use Gmail with app password for testing
   - reCAPTCHA: Use Google's test keys or create localhost keys
   - Site URL: Use your Replit app URL

3. **Install dependencies** (if needed):
   ```bash
   # The replit.nix file should handle this automatically
   ```

4. **Initialize database:**
   ```bash
   php config/init_db.php
   ```

5. **Run the application:**
   ```bash
   # Replit will automatically run based on .replit config
   ```

### Hostinger Setup

1. **Upload files via cPanel File Manager or FTP**
   - Upload all files **except** `config/config.php`

2. **Create config.php on the server:**
   - In cPanel File Manager, navigate to `/config/`
   - Click **+ File** and create `config.php`
   - Edit the file and paste your configuration
   - Or upload your local `config.php` (ensure it has production values)

3. **Set file permissions:**
   ```bash
   # Via SSH or cPanel Terminal
   chmod 600 config/config.php  # Only owner can read/write
   chmod 755 admin/             # Directory executable
   chmod 644 *.php              # PHP files readable
   ```

4. **Create database:**
   - Use cPanel MySQL Databases tool
   - Run `init_db.php` once by visiting: `https://codedart.org/config/init_db.php`
   - **IMPORTANT:** Delete or rename `init_db.php` after first run!

5. **Set up CRON job** (optional) for maintenance tasks:
   ```
   0 3 * * * php /path/to/CodedArtEmbedded/maintenance/cleanup.php
   ```

---

## 🧪 Testing Your Configuration

### Test Database Connection

Create a test file: `config/test-db.php`

```php
<?php
require_once 'config.php';
require_once 'database.php';

try {
    $db = getDBConnection();
    echo "✅ Database connection successful!\n";
    echo "Connected to: " . DB_NAME . "\n";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
}
```

Run: `php config/test-db.php`

### Test SMTP Configuration

Create a test file: `config/test-email.php`

```php
<?php
require_once 'config.php';
require_once '../admin/includes/email-notifications.php';

$to = 'your-email@example.com';
$subject = 'Test Email from CodedArt';
$body = 'If you receive this, SMTP is configured correctly!';

if (sendEmail($to, $subject, $body)) {
    echo "✅ Email sent successfully!\n";
} else {
    echo "❌ Email failed to send.\n";
}
```

Run: `php config/test-email.php`

### Test reCAPTCHA

Visit your registration page and check the browser console for reCAPTCHA errors.

---

## 🔐 Security Checklist

- [ ] `config.php` is **NOT** in Git repository
- [ ] `config.php` has restrictive permissions (600) on production
- [ ] Database password is strong (16+ characters, mixed case, numbers, symbols)
- [ ] SMTP password is unique and not reused
- [ ] reCAPTCHA keys are for production domain (not test keys)
- [ ] Error display is **OFF** in production (`display_errors = 0`)
- [ ] HTTPS is enabled on production (SSL certificate installed)
- [ ] Session cookies are HTTPS-only in production
- [ ] Database user has minimal required privileges
- [ ] Admin panel is not publicly linked (access via direct URL only)

---

## 📁 Directory Structure

After configuration, ensure these directories exist with proper permissions:

```
config/
├── config.php          ← YOUR FILE (NOT IN GIT) - permissions: 600
├── config.example.php  ← IN GIT - safe to commit
├── database.php        ← IN GIT
├── environment.php     ← IN GIT
├── helpers.php         ← IN GIT
├── init_db.php         ← IN GIT (delete after first run on production)
└── seed_data.php       ← IN GIT

cache/                  ← Created automatically - permissions: 755
├── cors/               ← For cached images

logs/                   ← Created automatically - permissions: 755
└── php_errors.log      ← Auto-generated
```

---

## 🐛 Troubleshooting

### Database Connection Fails

**Problem:** "Access denied for user..."

**Solution:**
1. Verify DB_USER and DB_PASS in config.php
2. Check database user has privileges: `GRANT ALL ON database.* TO 'user'@'localhost';`
3. Verify DB_HOST is correct (usually `localhost`)

### Email Not Sending

**Problem:** Emails not arriving

**Solution:**
1. Check spam folder
2. Verify SMTP credentials are correct
3. Try sending a test email via command line
4. Check PHP error logs: `tail -f logs/php_errors.log`
5. Verify port 587 or 465 is not blocked by firewall

### reCAPTCHA Errors

**Problem:** "Invalid site key" or "reCAPTCHA validation failed"

**Solution:**
1. Ensure domain is registered with your reCAPTCHA account
2. Use correct site key (v3, not v2)
3. Check browser console for JavaScript errors
4. Verify reCAPTCHA library is loaded: `https://www.google.com/recaptcha/api.js`

### Permission Denied Errors

**Problem:** "Permission denied" when creating files

**Solution:**
```bash
# Set ownership (on server via SSH)
chown -R www-data:www-data /path/to/CodedArtEmbedded

# Set permissions
chmod -R 755 /path/to/CodedArtEmbedded
chmod 600 config/config.php
chmod 755 cache logs
```

---

## 📞 Support

If you encounter issues:

1. Check error logs: `logs/php_errors.log`
2. Enable debug mode temporarily in config.php: `define('DEBUG_MODE', true);`
3. Review this setup guide thoroughly
4. Check PHP version compatibility (requires PHP 8.0+)

---

## 🔄 Updating Configuration

When you need to update configuration:

1. **Development:** Edit `config/config.php` locally
2. **Production:**
   - Edit via cPanel File Manager, or
   - Edit locally and upload via FTP/SFTP

**Never commit config.php to Git!**

---

## ✅ Configuration Complete

Once all sections are configured:

1. Test database connection
2. Test email sending
3. Test user registration with reCAPTCHA
4. Test creating/editing/deleting art pieces
5. Verify email notifications arrive
6. Check all pages load without errors

**You're ready to go!** 🎉
