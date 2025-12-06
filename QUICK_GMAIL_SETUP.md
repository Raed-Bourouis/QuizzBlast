# 🚀 Gmail Setup - Quick Reference

## Step 1: Get Gmail App Password (5 minutes)

1. Go to: https://myaccount.google.com/security
2. Enable "2-Step Verification" (if not enabled)
3. Go to "App passwords" (at bottom of 2-Step Verification section)
4. Select "Mail" + "Windows Computer"
5. Click "Generate"
6. Copy the 16-character password (remove spaces)

## Step 2: Configure Your Project (2 minutes)

Create `.env.local` in project root:

```env
MAILER_DSN=gmail+smtp://YOUR_EMAIL@gmail.com:YOUR_16_CHAR_PASSWORD@default
```

**Example:**

```env
MAILER_DSN=gmail+smtp://myapp@gmail.com:abcdefghijklmnop@default
```

## Step 3: Update From Email (1 minute)

Edit these 2 files to use your Gmail:

**File 1:** `src/Service/EmailService.php` (line 12)

```php
private string $fromEmail = 'YOUR_EMAIL@gmail.com',
```

**File 2:** `src/Controller/RegistrationController.php` (line 48)

```php
->from(new Address('YOUR_EMAIL@gmail.com', 'QuizzBlast'))
```

## Step 4: Clear Cache & Test

```bash
php bin/console cache:clear
```

Then login to your app - you should receive a welcome email! 🎉

---

## ⚙️ Quick Settings

**Disable welcome emails on login:**
Edit `src/EventListener/LoginSuccessListener.php` line 14:

```php
private bool $sendWelcomeEmail = false,
```

**Enable login security notifications:**
Edit `src/EventListener/LoginSuccessListener.php` line 15:

```php
private bool $sendLoginNotification = true,
```

---

## 🆘 Quick Troubleshooting

**Problem:** Invalid credentials

- ✅ Use App Password (not regular password)
- ✅ Remove spaces from app password
- ✅ Enable 2-Step Verification first

**Problem:** Emails not sending

- ✅ Check `.env.local` exists and has correct values
- ✅ Clear cache: `php bin/console cache:clear`
- ✅ Check logs: `var/log/dev.log`

**Problem:** Emails in spam

- ✅ Normal for development - check spam folder

---

## 📧 Current Email Features

✅ Registration verification email (already working)
✅ Welcome email on login (NEW - enabled by default)
⚠️ Login security notification (NEW - disabled by default)

---

## 📚 Full Documentation

For detailed information, see:

- `GMAIL_SETUP_GUIDE.md` - Complete Gmail setup
- `EMAIL_FEATURES_GUIDE.md` - All email features and customization

That's it! You're ready to send emails with Gmail! 🚀
