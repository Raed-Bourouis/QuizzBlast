# Registration Email Flow - Complete Guide

## ✅ YES! Emails Are Automatically Sent After Registration

Your application **already** sends emails automatically when a user registers. Here's exactly how it works:

---

## 📧 Registration Email Flow

### Step 1: User Fills Registration Form
- User goes to `/register`
- Fills in: username, email, password
- Clicks "Register" button

### Step 2: User is Created in Database
```php
$user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));
$entityManager->persist($user);
$entityManager->flush();
```
The user account is saved to the database.

### Step 3: ✉️ **Verification Email is AUTOMATICALLY Sent**
```php
$this->emailVerifier->sendEmailConfirmation('app_verify_email', $user,
    (new TemplatedEmail())
        ->from(new Address('bechirzamouri06@gmail.com', 'QuizzBlast Mail Bot'))
        ->to((string) $user->getEmail())
        ->subject('Please Confirm your Email')
        ->htmlTemplate('registration/confirmation_email.html.twig')
);
```

**This happens automatically - no manual action needed!**

### Step 4: User is Automatically Logged In
```php
return $security->login($user, AppCustomAuthenticator::class, 'main');
```
After registration, the user is logged in automatically.

### Step 5: ✉️ **Welcome Email is ALSO Sent** (New Feature!)
When the user is logged in (from step 4), the `LoginSuccessListener` triggers and sends a welcome email automatically!

---

## 📨 Two Emails Are Sent During Registration

| # | Email Type | When Sent | Purpose | Template |
|---|------------|-----------|---------|----------|
| 1️⃣ | **Verification Email** | Immediately after registration | Verify email address with signed link | `registration/confirmation_email.html.twig` |
| 2️⃣ | **Welcome Email** | After auto-login (right after registration) | Welcome the user to the platform | `emails/welcome.html.twig` |

---

## 🔍 How It Works in Detail

### Email 1: Verification Email

**Sent by:** `EmailVerifier` service  
**Triggered by:** Registration controller (line 45-51)  
**Contains:** 
- Link to verify email
- Signed URL for security
- Expires after some time

**User sees:**
```
Subject: Please Confirm your Email
From: QuizzBlast Mail Bot <bechirzamouri06@gmail.com>

[Email content with verification link]
Click here to verify your email: [Secure Link]
```

### Email 2: Welcome Email

**Sent by:** `EmailService` via `LoginSuccessListener`  
**Triggered by:** Auto-login after registration  
**Contains:**
- Welcome message
- Login details (time, username, email)
- Feature highlights
- Encouragement to get started

**User sees:**
```
Subject: Welcome to QuizzBlast!
From: QuizzBlast <bechirzamouri06@gmail.com>

🎉 Welcome to QuizzBlast!
Hello [username]!

We're excited to have you on board...
```

---

## 🧪 Testing the Registration Flow

### Test 1: Register a New User

1. **Go to:** `http://localhost:8000/register`

2. **Fill in the form:**
   - Username: `testuser`
   - Email: Use a **real email you can check** (e.g., your personal email)
   - Password: `Test123!`

3. **Click "Register"**

4. **What happens:**
   - ✅ User is created in database
   - ✅ Verification email is sent to the email address
   - ✅ User is automatically logged in
   - ✅ Welcome email is sent
   - ✅ User is redirected to home page

5. **Check your email inbox:**
   - 📧 Email 1: "Please Confirm your Email" (verification)
   - 📧 Email 2: "Welcome to QuizzBlast!" (welcome)
   - ⚠️ **Check spam folder** if not in inbox!

### Test 2: Verify Email Works

1. Open the verification email
2. Click the verification link
3. Your email should be marked as verified
4. You'll see a success message

---

## 🎯 Current Email Configuration

**Gmail Account:** `bechirzamouri06@gmail.com`  
**App Password:** Configured in `.env.local`  
**SMTP:** Gmail SMTP (working ✅)

**Services:**
- `EmailVerifier` - Handles verification emails
- `EmailService` - Handles welcome & notification emails
- `LoginSuccessListener` - Auto-sends emails on login

---

## 🔧 Customizing Registration Emails

### To Customize Verification Email:

Edit: `templates/registration/confirmation_email.html.twig`

Example changes:
- Subject line (in controller)
- Email design
- Message content
- Company branding

### To Customize Welcome Email:

Edit: `templates/emails/welcome.html.twig`

Example changes:
- Welcome message
- Feature highlights
- Call-to-action buttons
- Design and colors

### To Disable Welcome Email:

Edit: `src/EventListener/LoginSuccessListener.php` (line 17)
```php
private bool $sendWelcomeEmail = false, // Changed to false
```

Then clear cache:
```bash
php bin/console cache:clear
```

---

## 📋 Email Checklist After Registration

When a user registers, check:

- [ ] Verification email received
- [ ] Welcome email received  
- [ ] Both emails from `bechirzamouri06@gmail.com`
- [ ] Verification link works
- [ ] Email format looks good (HTML)
- [ ] No errors in Symfony profiler

---

## 🐛 Troubleshooting

### Problem: No emails received after registration

**Check 1: Spam Folder**
- Gmail might put emails in spam initially
- Check spam/junk folder

**Check 2: Email Address**
- Make sure you used a valid email during registration
- Try with a different email provider

**Check 3: Symfony Profiler**
- After registration, click the toolbar at bottom
- Click email icon
- Check if emails were sent

**Check 4: Logs**
```bash
Get-Content var/log/dev.log -Tail 100 | Select-String -Pattern "email"
```

**Check 5: Test Email Manually**
```bash
php bin/console app:test-email your-email@example.com
```

### Problem: Only one email received

If you only receive verification email but not welcome email:
- Check `LoginSuccessListener` is enabled
- Verify welcome email template has no errors
- Check Symfony profiler for errors

### Problem: Emails go to spam

This is normal for development:
- Gmail marks emails from new senders as spam
- In production, configure SPF/DKIM records
- Mark as "Not Spam" in Gmail

---

## 🎉 Summary

**Question:** After registering, are emails sent automatically?

**Answer:** YES! ✅

**What emails:**
1. ✉️ **Verification Email** - Sent immediately (with verify link)
2. ✉️ **Welcome Email** - Sent after auto-login (greeting)

**Configuration:**
- ✅ Already set up and working
- ✅ Using Gmail SMTP
- ✅ No manual action needed
- ✅ Fully automated

**To test:**
1. Register a new user with a real email
2. Check your inbox (and spam)
3. You'll receive 2 emails automatically!

---

## 📚 Related Documentation

- `EMAIL_FEATURES_GUIDE.md` - Complete email features
- `EMAIL_TROUBLESHOOTING_SOLVED.md` - If emails don't work
- `EMAIL_INTEGRATION_ANALYSIS.md` - How services work together
- `GMAIL_SETUP_GUIDE.md` - Gmail configuration details

Everything is already configured and working! Just register a user and check your email. 🚀
