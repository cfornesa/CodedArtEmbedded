# reCAPTCHA v3 Troubleshooting Guide

**Error:** "ERROR for site owner: Invalid key type"

---

## Quick Diagnosis

### Step 1: Use the Debug Tool

Visit: **`/admin/debug-recaptcha.php`**

This tool will:
- ✅ Check if keys are defined
- ✅ Detect whitespace issues
- ✅ Verify key format
- ✅ Test live reCAPTCHA v3 execution
- ✅ Show actual Google API response

---

## Common Causes & Fixes

### 🔴 Issue #1: Using v2 Keys Instead of v3

**Symptoms:**
- Error: "Invalid key type"
- Response from Google: `error-codes: ["invalid-keys"]`

**How to Verify:**
1. Go to https://www.google.com/recaptcha/admin
2. Find your keys in the list
3. Check the "Type" column - must say **"Score based (v3)"**

**If it says "Checkbox v2" or "Invisible v2":**

✅ **CREATE NEW KEYS:**
1. Click **"+"** (plus icon) to create new site
2. **Label:** CodedArt v3
3. **reCAPTCHA type:** Select **"Score based (v3)"** ← CRITICAL!
4. **Domains:**
   - `localhost`
   - `codedart.org`
   - `augmenthumankind.com`
   - Your Replit domain (e.g., `0db4b8c9-73c2-4334-8847-84312811b055-00-2dh85if6g73bi.riker.replit.dev`)
5. Click **Submit**
6. Copy **BOTH** keys (Site Key and Secret Key)
7. Update `config/config.php` with the NEW keys

---

### 🔴 Issue #2: Keys Have Whitespace

**Symptoms:**
- Error: "Invalid key type" or "Invalid input secret"
- Keys work in Google admin but not in your app

**Check:**
```php
// BAD - has extra spaces
define('RECAPTCHA_SITE_KEY', ' 6Lc1234567890abcdefABCDEFGHIJKLMNOPQRSTU ');

// GOOD - no spaces
define('RECAPTCHA_SITE_KEY', '6Lc1234567890abcdefABCDEFGHIJKLMNOPQRSTU');
```

**Fix:**
1. Edit `config/config.php`
2. Remove ALL spaces around the keys
3. Keys should be on one line with no extra characters

---

### 🔴 Issue #3: Site Key and Secret Key Are Swapped

**Symptoms:**
- Error: "Invalid input secret"

**Verify:**
- **Site Key** (public) - Used in JavaScript/HTML - Starts with `6L`
- **Secret Key** (private) - Used in PHP backend - Also starts with `6L`

Both start with `6L`, so it's easy to swap them!

**In config.php:**
```php
// CORRECT
define('RECAPTCHA_SITE_KEY', '6Lc...[40 chars]...xyz');    // PUBLIC key
define('RECAPTCHA_SECRET_KEY', '6Lc...[40 chars]...abc');  // SECRET key

// WRONG - swapped
define('RECAPTCHA_SITE_KEY', '6Lc...[40 chars]...abc');    // This is the secret!
define('RECAPTCHA_SECRET_KEY', '6Lc...[40 chars]...xyz');  // This is the site!
```

**How to Tell Them Apart:**
- In Google reCAPTCHA Admin, the **Site Key** is listed FIRST
- The **Secret Key** is listed SECOND (usually hidden/blurred)

---

### 🔴 Issue #4: Domain Not Registered

**Symptoms:**
- Error: "Invalid domain"
- Response from Google: `hostname: "your-domain.com"` with error

**Current Domain:**
Check `$_SERVER['HTTP_HOST']` - this is what reCAPTCHA sees

**For Replit:**
Your domain changes each time! Format: `[uuid]-00-[random].riker.replit.dev`

Example: `0db4b8c9-73c2-4334-8847-84312811b055-00-2dh85if6g73bi.riker.replit.dev`

**Fix:**
1. Visit your Replit app and copy the FULL domain from the URL bar
2. Go to https://www.google.com/recaptcha/admin
3. Click settings (gear icon) for your v3 key
4. Scroll to **Domains**
5. Click **Add** and paste the EXACT Replit domain
6. Save

**Tip for Replit:**
You can use a wildcard: `*.replit.dev` (if Google supports it)
OR add `localhost` for testing

---

### 🔴 Issue #5: Config File Not Updated

**Symptoms:**
- Debug tool shows old/placeholder keys
- Changes to config.php don't take effect

**Check:**
1. Are you editing the RIGHT file? Should be: `/config/config.php` (NOT `config.example.php`)
2. Did you save the file?
3. Clear browser cache: Ctrl+F5 (Windows) or Cmd+Shift+R (Mac)
4. Restart Replit if keys still don't update

**Verify:**
```bash
# In Replit Shell, check what's loaded:
php -r "require 'config/config.php'; echo RECAPTCHA_SITE_KEY;"
```

Should output your actual site key (not placeholder).

---

### 🔴 Issue #6: Old Cached Keys

**Symptoms:**
- Updated keys but still seeing errors
- Debug tool shows correct keys but registration fails

**Fix:**
1. **Clear PHP opcache:**
   ```bash
   # Restart PHP (Replit does this automatically on file change)
   # Or manually restart the Repl
   ```

2. **Clear browser cache:**
   - Chrome/Edge: Ctrl+Shift+Delete → Clear cached images and files
   - Firefox: Ctrl+Shift+Delete → Cache
   - Or use Incognito/Private mode

3. **Hard refresh:**
   - Windows: Ctrl+F5
   - Mac: Cmd+Shift+R

---

## Step-by-Step Verification Checklist

### ✅ Step 1: Verify You Have v3 Keys

- [ ] Go to https://www.google.com/recaptcha/admin
- [ ] Find your keys
- [ ] Confirm type is **"Score based (v3)"** not v2
- [ ] If v2, create NEW v3 keys

### ✅ Step 2: Check config.php

- [ ] Open `/config/config.php`
- [ ] Verify `RECAPTCHA_SITE_KEY` is defined
- [ ] Verify `RECAPTCHA_SECRET_KEY` is defined
- [ ] Verify `RECAPTCHA_MIN_SCORE` is defined (0.5 recommended)
- [ ] Check for extra spaces/whitespace around keys
- [ ] Verify keys are exactly 40 characters each
- [ ] Verify keys start with `6L`

### ✅ Step 3: Check Domain Registration

- [ ] Note your current domain from browser URL bar
- [ ] Go to reCAPTCHA admin → Settings
- [ ] Verify current domain is in the list
- [ ] Add domain if missing
- [ ] Save changes

### ✅ Step 4: Use Debug Tool

- [ ] Visit `/admin/debug-recaptcha.php`
- [ ] Check all status indicators are green
- [ ] Click "Test reCAPTCHA v3" button
- [ ] Review results

### ✅ Step 5: Check Browser Console

- [ ] Open browser DevTools (F12)
- [ ] Go to Console tab
- [ ] Try to register
- [ ] Look for reCAPTCHA-related errors
- [ ] Look for JavaScript errors

### ✅ Step 6: Check Actual Error Message

With the updated code, you should now see the ACTUAL error from Google:

**In Development Mode:**
- Error message shows actual Google error codes
- Example: "RECAPTCHA verification failed: invalid-keys"

**Check the Error:**
- `invalid-keys` → Using v2 keys with v3 code
- `invalid-input-secret` → Secret key wrong or swapped
- `invalid-domain` → Domain not registered
- `timeout-or-duplicate` → Token expired (normal after 2 minutes)

---

## Testing After Changes

1. **Clear everything:**
   ```bash
   # Restart Repl (if on Replit)
   # Hard refresh browser (Ctrl+F5)
   ```

2. **Run debug tool:**
   ```
   Visit: /admin/debug-recaptcha.php
   Click: "Test reCAPTCHA v3"
   ```

3. **Check actual registration:**
   ```
   Visit: /admin/register.php
   Fill form and submit
   Check error message (should show actual error codes in dev mode)
   ```

4. **Check browser console:**
   - Should see: `grecaptcha.execute()` call
   - Should NOT see: reCAPTCHA errors
   - Should see: Token being generated

---

## Expected Results

### ✅ Working v3 Configuration:

**Debug Tool:**
- All keys defined
- No whitespace detected
- Keys are 40 characters
- Test shows: `success: true, score: 0.7-0.9`

**Registration Page:**
- No visible CAPTCHA (v3 is invisible)
- Form submits successfully
- OR shows score-based rejection (if score < 0.5)

**Browser Console:**
- No errors
- reCAPTCHA loads silently

**Error Log:**
- No "invalid-keys" errors
- May see score warnings if legitimate score is low

---

## If Still Broken After All Checks

### Last Resort: Complete Key Recreation

1. **Delete existing keys:**
   - Go to Google reCAPTCHA Admin
   - Delete the old keys entirely

2. **Create fresh v3 keys:**
   - Create NEW site
   - **TRIPLE-CHECK:** Select "Score based (v3)"
   - Add ALL your domains (including Replit)
   - Save and copy BOTH keys

3. **Update config.php:**
   ```php
   define('RECAPTCHA_SITE_KEY', 'PASTE_NEW_SITE_KEY_HERE');
   define('RECAPTCHA_SECRET_KEY', 'PASTE_NEW_SECRET_KEY_HERE');
   define('RECAPTCHA_MIN_SCORE', 0.5);
   ```

4. **Test with debug tool immediately**

---

## Quick Reference: Google Error Codes

| Error Code | Meaning | Fix |
|------------|---------|-----|
| `invalid-keys` | Using v2 keys with v3 | Create NEW v3 keys |
| `invalid-input-secret` | Secret key wrong | Check for typos, whitespace, or swap |
| `invalid-input-response` | Token invalid/expired | Normal - tokens expire in 2 min |
| `timeout-or-duplicate` | Token already used | Normal - tokens are single-use |
| `missing-input-secret` | No secret in config | Add RECAPTCHA_SECRET_KEY |
| `missing-input-response` | No token sent | Check JavaScript execution |
| `bad-request` | Malformed API request | Check code formatting |

---

## Contact Info

If none of this works, provide:
1. Screenshot of debug tool results
2. Exact error message from registration page
3. Error log output (from PHP error log)
4. Screenshot of Google reCAPTCHA admin showing key type

**Created:** 2026-01-20
**Last Updated:** 2026-01-20
