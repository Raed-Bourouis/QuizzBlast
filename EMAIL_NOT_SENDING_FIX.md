# Email Not Sending - Troubleshooting & Fix

## 🔍 Issues Found

### 1. **.env vs .env.local**

You put credentials in `.env` instead of `.env.local`. While `.env` can work, it's not recommended because:

- ❌ `.env` is tracked by Git (credentials will be exposed)
- ❌ `.env` is meant for default/example values
- ✅ `.env.local` is in `.gitignore` (safe for credentials)
- ✅ `.env.local` overrides `.env`

### 2. **Email Address Mismatch**

- In `.env`: `bechirzamouri06@gmail.com`
- In `RegistrationController.php`: `bechirzamoui06@gmail.com`
- In `EmailService.php`: `bechirzamoui06@gmail.com`

Different spellings! This could cause issues.

### 3. **Wrong DSN Format**

Your current `.env` has:

```env
MAILER_DSN=gmail+smtp://bechirzamouri06@gmail.com:oktxjyitngqeompg@default
```

The correct format for Gmail with the bridge should be:

```env
MAILER_DSN=gmail+smtp://bechirzamoui06@gmail.com:oktxjyitngqeompg@default
```

---

## ✅ Complete Fix (Step by Step)

### Step 1: Create `.env.local` File

In your project root (where `.env` is located), create a new file named `.env.local`:

**File: `.env.local`**

```env
###> symfony/mailer ###
# Use the correct email address that matches your Gmail account
MAILER_DSN=gmail+smtp://bechirzamoui06@gmail.com:oktxjyitngqeompg@default
###< symfony/mailer ###
```

**IMPORTANT:** Replace with your actual correct Gmail address!

### Step 2: Remove Gmail Credentials from `.env`

Edit `.env` file and change the MAILER_DSN back to null:

```env
###> symfony/mailer ###
MAILER_DSN=null://null
###< symfony/mailer ###
```

This is the safe default that should be in `.env`.

### Step 3: Verify Email Address Consistency

Make sure the same email is used everywhere:

**Check `src/Controller/RegistrationController.php` (line 48):**

```php
->from(new Address('bechirzamoui06@gmail.com', 'QuizzBlast Mail Bot'))
```

**Check `src/Service/EmailService.php` (line 13):**

```php
private string $fromEmail = 'bechirzamoui06@gmail.com',
```

**All three places should have the SAME email address!**

### Step 4: Clear Cache

```bash
php bin/console cache:clear
```

### Step 5: Test

Try registering a new user or logging in to trigger an email.

---

## 🔐 Security Note

**Why your credentials are currently exposed:**

Your `.env` file is typically tracked by Git, which means your Gmail password (`oktxjyitngqeompg`) is now in your Git history and could be visible to anyone with access to your repository.

**What to do:**

1. ✅ Move credentials to `.env.local` (already in `.gitignore`)
2. ✅ Generate a NEW App Password from Google
3. ✅ Update `.env.local` with the new password
4. ✅ Never commit `.env.local` to Git

---

## 🧪 Testing Checklist

After making the changes:

- [ ] `.env.local` created with correct Gmail credentials
- [ ] `.env` has `MAILER_DSN=null://null`
- [ ] Same email address in all files (RegistrationController, EmailService)
- [ ] Cache cleared
- [ ] Test registration - should send verification email
- [ ] Test login - should send welcome email
- [ ] Check Symfony profiler (bottom toolbar) - email icon should show sent emails

---

## 🔧 Additional Troubleshooting

### If emails still don't send:

**1. Check logs:**

```bash
tail -f var/log/dev.log
```

**2. Verify mailer is configured:**

```bash
php bin/console debug:config framework mailer
```

**3. Test if Gmail credentials are valid:**

- Try logging into Gmail with the email and app password
- Make sure 2-Step Verification is enabled
- Make sure the app password hasn't expired

**4. Check if emails are being queued:**

```bash
php bin/console messenger:stats
```

**5. Check Symfony profiler:**

- After triggering an email action
- Click the bottom toolbar
- Click the email icon (envelope)
- You should see the email that was sent/attempted

---

## 🎯 Correct Configuration Summary

**`.env` (committed to Git):**

```env
###> symfony/mailer ###
MAILER_DSN=null://null
###< symfony/mailer ###
```

**`.env.local` (NOT committed to Git):**

```env
###> symfony/mailer ###
MAILER_DSN=gmail+smtp://bechirzamoui06@gmail.com:your-app-password@default
###< symfony/mailer ###
```

**All code files should use:**

- Email: `bechirzamoui06@gmail.com` (or whatever your correct email is)

---

## 📝 Next Steps

1. Create `.env.local` with the correct configuration
2. Reset `.env` to safe defaults
3. Ensure email consistency across all files
4. Clear cache
5. Test!

Let me know if you need help with any of these steps!
