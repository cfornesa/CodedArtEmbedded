# CodedArtEmbedded Deployment Guide

**Version:** 1.0
**Date:** 2026-01-20
**Status:** Production Ready

---

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [Pre-Deployment Checklist](#pre-deployment-checklist)
3. [Deployment Steps](#deployment-steps)
4. [Configuration](#configuration)
5. [Database Setup](#database-setup)
6. [Testing](#testing)
7. [Troubleshooting](#troubleshooting)
8. [Backup & Recovery](#backup--recovery)
9. [Post-Deployment](#post-deployment)
10. [Maintenance](#maintenance)

---

## Prerequisites

### Server Requirements

**Minimum:**
- PHP 7.4 or higher (PHP 8.0+ recommended)
- MySQL 5.7 or higher (MySQL 8.0+ recommended)
- Apache 2.4+ or Nginx 1.18+
- 512MB RAM minimum
- 500MB disk space

**Recommended:**
- PHP 8.2
- MySQL 8.0
- 1GB+ RAM
- 1GB+ disk space
- SSL certificate (Let's Encrypt or commercial)

### PHP Extensions Required

```bash
php -m | grep -E "pdo|pdo_mysql|mbstring|openssl|curl|gd|json"
```

**Required extensions:**
- pdo
- pdo_mysql (or pdo_sqlite for development)
- mbstring
- openssl
- json

**Optional but recommended:**
- curl (for CORS proxy)
- gd or imagick (for image manipulation)

### External Services

1. **SMTP Server** (for email notifications)
   - Host, port, username, password
   - Hostinger provides this built-in
   - Alternative: SendGrid, Mailgun, AWS SES

2. **Google reCAPTCHA** (for registration protection)
   - Site key
   - Secret key
   - Get from: https://www.google.com/recaptcha/admin

---

## Pre-Deployment Checklist

### Development Environment

- [ ] All phases complete (Phases 1-5)
- [ ] All tests passing (86.5%+ pass rate)
- [ ] Database seeded with initial content
- [ ] Admin account created and tested
- [ ] All PHP files have valid syntax
- [ ] Git repository up to date

### Production Environment

- [ ] Hosting account active (Hostinger or similar)
- [ ] Domain configured and DNS propagated
- [ ] cPanel or equivalent access available
- [ ] MySQL database created
- [ ] Database user created with privileges
- [ ] SMTP credentials obtained
- [ ] reCAPTCHA keys obtained
- [ ] SSL certificate installed

### Security Checklist

- [ ] `config.php` excluded from git (.gitignore)
- [ ] Strong database password set
- [ ] Strong admin passwords chosen
- [ ] File permissions configured correctly
- [ ] Directory indexes disabled
- [ ] Error display turned off in production
- [ ] HTTPS enforced (SSL/TLS)

---

## Deployment Steps

### Step 1: Upload Files

**Via FTP/SFTP:**
```bash
# Upload all files except:
# - .git directory
# - config/config.php (will create separately)
# - cache directory (will be created)
# - logs directory (will be created)

sftp user@yourdomain.com
put -r /path/to/CodedArtEmbedded/* /home/user/public_html/
```

**Via cPanel File Manager:**
1. Compress project folder locally (exclude .git)
2. Upload zip file via cPanel File Manager
3. Extract in public_html directory

**Via Git (Recommended):**
```bash
# On server
cd /home/user/public_html
git clone https://github.com/yourusername/CodedArtEmbedded.git .
git checkout main
```

### Step 2: Create config.php

**On server, create `/config/config.php`:**

```php
<?php
/**
 * Production Configuration
 * DO NOT commit this file to git
 */

// Environment
define('ENVIRONMENT', 'production');

// Database Configuration
define('DB_HOST', 'localhost'); // Or your MySQL host
define('DB_NAME', 'your_database_name'); // From cPanel MySQL Databases
define('DB_USER', 'your_database_user'); // From cPanel MySQL Databases
define('DB_PASS', 'your_strong_password'); // Database password
define('DB_CHARSET', 'utf8mb4');

// SMTP Configuration
define('SMTP_HOST', 'mail.yourdomain.com'); // Or smtp.gmail.com for Gmail
define('SMTP_PORT', 587); // 587 for TLS, 465 for SSL
define('SMTP_SECURE', 'tls'); // 'tls' or 'ssl'
define('SMTP_USERNAME', 'admin@yourdomain.com');
define('SMTP_PASSWORD', 'your_email_password');
define('SMTP_FROM_EMAIL', 'admin@yourdomain.com');
define('SMTP_FROM_NAME', 'CodedArt Admin');

// reCAPTCHA Configuration
define('RECAPTCHA_SITE_KEY', 'your_site_key_here');
define('RECAPTCHA_SECRET_KEY', 'your_secret_key_here');

// Security Settings
define('SESSION_LIFETIME', 3600); // 1 hour
define('PASSWORD_MIN_LENGTH', 8);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes

// CORS Proxy Settings
define('CORS_PROXY_ENABLED', true);
define('CORS_CACHE_DIR', __DIR__ . '/../cache/cors/');
define('CORS_CACHE_LIFETIME', 86400); // 24 hours

// Application Settings
define('SITE_URL', 'https://yourdomain.com');
define('ADMIN_URL', SITE_URL . '/admin');
define('TIMEZONE', 'America/New_York');

// Notification Settings
define('SEND_EMAIL_NOTIFICATIONS', true);
define('ADMIN_EMAIL', 'admin@yourdomain.com');

// Set timezone
date_default_timezone_set(TIMEZONE);

// Error reporting (production)
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php_errors.log');
?>
```

### Step 3: Set File Permissions

```bash
# On server
cd /home/user/public_html

# Make config read-only
chmod 600 config/config.php

# Make cache and logs writable
chmod 777 cache
chmod 777 cache/cors
chmod 777 logs

# Make admin includes executable
chmod 755 admin/includes/*.php

# Make sure PHP files are readable
find . -name "*.php" -exec chmod 644 {} \;
```

### Step 4: Create Required Directories

```bash
# On server
mkdir -p cache/cors
mkdir -p logs
chmod 777 cache/cors
chmod 777 logs
```

### Step 5: Database Setup

**Option A: Via cPanel phpMyAdmin:**
1. Log into cPanel → phpMyAdmin
2. Select your database
3. Import → Choose file → Select `config/init_db.sql` (if exists)
4. Or run the initialization script

**Option B: Via PHP Script:**
```bash
# On server
cd /home/user/public_html
php config/init_db.php
php config/seed_data.php
```

**Option C: Manual SQL:**
Run the SQL from `config/init_db.php` manually in phpMyAdmin.

### Step 6: Create First Admin User

**Via browser:**
1. Navigate to `https://yourdomain.com/admin/register.php`
2. Fill in registration form:
   - Email: admin@yourdomain.com
   - Password: [strong password]
   - First Name: Admin
   - Last Name: User
3. Click "Register"
4. First user is auto-activated (no email verification needed)
5. Login at `/admin/login.php`

**Via PHP CLI:**
```bash
# On server
cd /home/user/public_html
php -r "
require 'admin/includes/auth.php';
\$result = registerUser([
    'email' => 'admin@yourdomain.com',
    'password' => 'YourStrongPassword123!',
    'first_name' => 'Admin',
    'last_name' => 'User'
]);
echo json_encode(\$result, JSON_PRETTY_PRINT);
"
```

### Step 7: Configure .htaccess (Optional but Recommended)

Create `/public_html/.htaccess`:

```apache
# Force HTTPS
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Prevent directory listing
Options -Indexes

# Protect config directory
<Directory "/home/user/public_html/config">
    Order allow,deny
    Deny from all
</Directory>

# Protect cache directory
<Directory "/home/user/public_html/cache">
    Order allow,deny
    Deny from all
</Directory>

# Protect logs directory
<Directory "/home/user/public_html/logs">
    Order allow,deny
    Deny from all
</Directory>

# Hide sensitive files
<FilesMatch "^(config\.php|\.gitignore|\.git|\.env)$">
    Order allow,deny
    Deny from all
</FilesMatch>

# Set default charset
AddDefaultCharset UTF-8

# Enable compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript
</IfModule>

# Set cache headers for static files
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/jpg "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType image/webp "access plus 1 year"
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
</IfModule>
```

---

## Configuration

### Hostinger-Specific Configuration

**Database:**
1. cPanel → MySQL® Databases
2. Create Database: `username_codedart`
3. Create User: `username_codeart_user`
4. Set strong password
5. Add User to Database (ALL PRIVILEGES)
6. Note: Host is usually `localhost`

**Email:**
1. cPanel → Email Accounts
2. Create: `admin@yourdomain.com`
3. SMTP Settings:
   - Host: `mail.yourdomain.com`
   - Port: 587 (TLS) or 465 (SSL)
   - Username: `admin@yourdomain.com`
   - Password: [email password]

**SSL:**
1. cPanel → SSL/TLS Status
2. Run AutoSSL (Let's Encrypt)
3. Or install custom certificate

### Domain Configuration

**Multi-Domain Support:**
The project supports multiple domains via `resources/templates/name.php`.

**Current domains:**
- codedart.cfornesa.com
- codedart.fornesus.com
- codedart.org

**To add a new domain:**
1. Point DNS to server
2. Update `resources/templates/name.php`:
```php
$domains = [
    'codedart.org' => ['name' => 'C. Fornesa', 'img' => '/img/name.png'],
    'newdomain.com' => ['name' => 'Your Name', 'img' => '/img/newname.png']
];
```

---

## Database Setup

### Initial Schema

**Tables created:**
1. `users` - Admin user accounts
2. `aframe_art` - A-Frame art pieces
3. `c2_art` - C2.js art pieces
4. `p5_art` - P5.js art pieces
5. `threejs_art` - Three.js art pieces
6. `activity_log` - CRUD operation logs
7. `site_config` - Global settings

### Seeding Data

**Run seed script:**
```bash
php config/seed_data.php
```

**Expected output:**
```
Seeding database with initial art pieces...
✓ Seeded 2 A-Frame pieces
✓ Seeded 2 C2 pieces
✓ Seeded 4 P5 pieces
✓ Seeded 3 Three.js pieces
Total: 11 pieces seeded
```

### Database Verification

```bash
# Check all tables exist
php -r "
require 'config/database.php';
\$db = getDbConnection();
\$tables = ['users', 'aframe_art', 'c2_art', 'p5_art', 'threejs_art', 'activity_log', 'site_config'];
foreach (\$tables as \$table) {
    \$count = \$db->query('SELECT COUNT(*) FROM ' . \$table)->fetchColumn();
    echo \$table . ': ' . \$count . ' rows\n';
}
"
```

---

## Testing

### Post-Deployment Tests

**1. Homepage Test:**
```bash
curl -I https://yourdomain.com
# Should return: HTTP/2 200
```

**2. Gallery Pages Test:**
```bash
curl https://yourdomain.com/a-frame/ | grep -i "A-Frame"
curl https://yourdomain.com/c2/ | grep -i "C2"
curl https://yourdomain.com/p5/ | grep -i "p5"
curl https://yourdomain.com/three-js/ | grep -i "Three"
```

**3. Admin Login Test:**
Navigate to `https://yourdomain.com/admin/login.php` and verify:
- Page loads without errors
- Can log in with admin credentials
- Redirected to dashboard

**4. Database Connection Test:**
```bash
php -r "
require 'config/database.php';
\$db = getDbConnection();
echo \$db ? 'Database connected successfully' : 'Database connection failed';
"
```

**5. Run Comprehensive Test Suite:**
```bash
php test_complete_system.php
# Expected: 86.5%+ pass rate
```

### Manual Testing Checklist

- [ ] Homepage loads correctly
- [ ] All gallery pages load with art pieces
- [ ] Gallery thumbnails display properly
- [ ] Admin login works
- [ ] Admin dashboard shows statistics
- [ ] Can create new art piece
- [ ] Can edit existing art piece
- [ ] Can delete art piece (with confirmation)
- [ ] Email notifications sent (check inbox)
- [ ] Navigation works across all pages
- [ ] Responsive design works on mobile
- [ ] SSL certificate active (HTTPS)
- [ ] No PHP errors in logs

---

## Troubleshooting

### Common Issues

#### Issue 1: "Database connection failed"

**Symptoms:** White screen or error message

**Solutions:**
1. Check database credentials in `config/config.php`
2. Verify database exists in cPanel
3. Verify user has privileges: `GRANT ALL ON dbname.* TO 'username'@'localhost';`
4. Check if MySQL service is running

#### Issue 2: "Permission denied" errors

**Symptoms:** Cannot write to cache or logs

**Solutions:**
```bash
chmod 777 cache
chmod 777 cache/cors
chmod 777 logs
```

#### Issue 3: "Headers already sent" errors

**Symptoms:** Session warnings

**Solutions:**
1. Ensure no output before session_start()
2. Check for BOM (Byte Order Mark) in PHP files
3. Remove any whitespace before `<?php`

#### Issue 4: Admin pages not loading (404)

**Symptoms:** /admin/ returns 404

**Solutions:**
1. Verify admin directory exists and is readable
2. Check .htaccess isn't blocking /admin/
3. Verify file permissions: `chmod 755 admin`

#### Issue 5: Images not loading (CORS errors)

**Symptoms:** Console shows CORS policy errors

**Solutions:**
1. Verify CORS proxy enabled in config
2. Check cache directory writable: `chmod 777 cache/cors`
3. Test proxy: `/admin/includes/cors-proxy.php?url=IMAGE_URL`

#### Issue 6: Emails not sending

**Symptoms:** No notification emails received

**Solutions:**
1. Verify SMTP credentials in config.php
2. Check SMTP settings: host, port, secure
3. Test with: `mail('you@example.com', 'Test', 'Test email');`
4. Check spam folder
5. Enable debug mode in PHPMailer (if using)

#### Issue 7: reCAPTCHA not working

**Symptoms:** Registration always fails

**Solutions:**
1. Verify RECAPTCHA keys in config.php
2. Ensure site key matches domain
3. Check browser console for errors
4. Test on https:// (reCAPTCHA requires HTTPS for production)

---

## Backup & Recovery

### Database Backup

**Via cPanel:**
1. cPanel → phpMyAdmin
2. Select database
3. Export → Custom → Go
4. Save `.sql` file

**Via Command Line:**
```bash
mysqldump -u username -p database_name > backup_$(date +%Y%m%d).sql
```

**Automated Backup (Cron Job):**
```bash
# Add to crontab (cPanel → Cron Jobs)
0 2 * * * mysqldump -u username -p'password' database_name > /home/user/backups/db_$(date +\%Y\%m\%d).sql
```

### File Backup

**Via cPanel:**
1. cPanel → Backup
2. Download Home Directory Backup

**Via Command Line:**
```bash
tar -czf codedart_backup_$(date +%Y%m%d).tar.gz /home/user/public_html
```

### Recovery Procedure

**Database Restore:**
```bash
mysql -u username -p database_name < backup_20260120.sql
```

**File Restore:**
```bash
tar -xzf codedart_backup_20260120.tar.gz -C /home/user/
```

### Backup Schedule Recommendation

- **Daily:** Database backup (retain 7 days)
- **Weekly:** Full file backup (retain 4 weeks)
- **Monthly:** Archive backup (retain 12 months)
- **Before updates:** Always backup before deploying changes

---

## Post-Deployment

### Monitoring Setup

**Error Monitoring:**
1. Check logs regularly: `tail -f /home/user/public_html/logs/php_errors.log`
2. Set up log rotation
3. Monitor disk space usage

**Uptime Monitoring:**
- UptimeRobot (free)
- Pingdom
- StatusCake

**Performance Monitoring:**
- Google Analytics
- Google Search Console
- Server logs analysis

### Security Hardening

**Additional Steps:**
1. Change default MySQL port (optional)
2. Install ModSecurity (if available)
3. Enable firewall rules
4. Disable unused PHP functions
5. Regularly update PHP and MySQL
6. Review access logs weekly

### SEO Optimization

**Already included:**
- Semantic HTML5
- Responsive design
- Fast page loads
- Clean URLs

**Additional steps:**
1. Add robots.txt
2. Create sitemap.xml
3. Register with Google Search Console
4. Add meta descriptions to pages
5. Optimize images (already using WebP)

---

## Maintenance

### Regular Tasks

**Daily:**
- [ ] Check error logs
- [ ] Monitor server resources
- [ ] Verify backups completed

**Weekly:**
- [ ] Review activity log in admin
- [ ] Test admin functionality
- [ ] Check for PHP/MySQL updates
- [ ] Review user accounts

**Monthly:**
- [ ] Full system backup
- [ ] Security audit
- [ ] Performance review
- [ ] Update documentation
- [ ] Review and optimize database

**Quarterly:**
- [ ] Comprehensive security audit
- [ ] Load testing
- [ ] Review and update dependencies
- [ ] Disaster recovery drill

### Update Procedure

**For code updates:**
```bash
# 1. Backup current installation
tar -czf backup_before_update.tar.gz /home/user/public_html

# 2. Pull updates
git pull origin main

# 3. Test on staging (if available)
# 4. Deploy to production
# 5. Run migration scripts (if any)
# 6. Test thoroughly
```

**For database schema changes:**
```bash
# 1. Backup database
mysqldump -u username -p database_name > backup_before_migration.sql

# 2. Run migration
php config/migrate.php

# 3. Verify data integrity
# 4. Test functionality
```

### Performance Optimization

**Already implemented:**
- CSS/JS minification
- Image optimization (WebP)
- Database indexing
- Prepared statements

**Additional optimizations:**
1. Enable OPcache: `opcache.enable=1`
2. Enable query caching in MySQL
3. Use CDN for static assets
4. Implement Redis/Memcached
5. Enable Gzip compression (in .htaccess)

---

## Support & Documentation

### Documentation Files

- `CLAUDE.md` - Overall project plan
- `PHASE3-COMPLETE.md` - Admin interface documentation
- `PHASE4-COMPLETE.md` - Gallery pages documentation
- `PHASE5-COMPLETE.md` - Template consolidation documentation
- `DEPLOYMENT-GUIDE.md` - This file

### Getting Help

**Common resources:**
- Hostinger Knowledge Base
- PHP Documentation: https://www.php.net/docs.php
- MySQL Documentation: https://dev.mysql.com/doc/

**Contact:**
- System Administrator: admin@yourdomain.com
- Developer: [your email]

---

## Appendix: Quick Reference

### Important URLs

- **Production Site:** https://yourdomain.com
- **Admin Login:** https://yourdomain.com/admin/login.php
- **Admin Dashboard:** https://yourdomain.com/admin/dashboard.php
- **cPanel:** https://yourdomain.com:2083
- **phpMyAdmin:** https://yourdomain.com:2083/cpsess.../phpMyAdmin

### Important Files

- **Configuration:** `/config/config.php`
- **Database:** `/config/database.php`
- **Admin Auth:** `/admin/includes/auth.php`
- **Admin Functions:** `/admin/includes/functions.php`
- **Error Log:** `/logs/php_errors.log`

### Important Commands

```bash
# Check PHP version
php -v

# Check MySQL version
mysql --version

# Test database connection
php -r "require 'config/database.php'; echo getDbConnection() ? 'OK' : 'FAIL';"

# View error log
tail -n 50 logs/php_errors.log

# Clear cache
rm -rf cache/cors/*

# Run tests
php test_complete_system.php
```

---

## Conclusion

This deployment guide covers the complete process of deploying CodedArtEmbedded to a production server. Follow each step carefully, test thoroughly, and maintain regular backups.

**Status:** ✅ Production Ready

**Last Updated:** 2026-01-20

**Version:** 1.0

---

**End of Deployment Guide**
