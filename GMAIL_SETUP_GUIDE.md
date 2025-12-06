# Gmail Configuration for Symfony Mailer

## 🚀 Quick Setup Guide

### Step 1: Generate Gmail App Password

Since Gmail requires 2-factor authentication for app passwords, follow these steps:

1. **Go to your Google Account**: https://myaccount.google.com/
2. **Navigate to Security**: Click on "Security" in the left sidebar
3. **Enable 2-Step Verification** (if not already enabled):

   - Scroll down to "2-Step Verification"
   - Click "Get started" and follow the prompts
   - This is REQUIRED for app passwords

4. **Generate App Password**:
   - After enabling 2-Step Verification, go back to Security
   - Scroll down to "2-Step Verification"
   - At the bottom, click on "App passwords"
   - Select "Mail" for app type
   - Select "Windows Computer" for device type
   - Click "Generate"
   - **COPY THE 16-CHARACTER PASSWORD** (it will look like: `abcd efgh ijkl mnop`)
   - Remove the spaces, so it becomes: `abcdefghijklmnop`

### Step 2: Create .env.local File

In your project root directory (where .env is located), create a new file named `.env.local`:

**File: `.env.local`**

```env
###> symfony/mailer ###
# Replace with your actual Gmail address and the 16-character app password
MAILER_DSN=gmail+smtp://your-email@gmail.com:your-app-password@default
###< symfony/mailer ###
```

**Example (with fake credentials):**

```env
###> symfony/mailer ###
MAILER_DSN=gmail+smtp://myemail@gmail.com:abcdefghijklmnop@default
###< symfony/mailer ###
```

### Step 3: Configure From Email Address

Update your registration controller to use your Gmail address.

**File: `src/Controller/RegistrationController.php`**

Change line 48 from:

```php
->from(new Address('clubistianocnt@gmail.com', 'Mail Bot'))
```

To use your actual Gmail:

```php
->from(new Address('your-email@gmail.com', 'QuizzBlast'))
```

### Step 4: Test the Configuration

1. Clear the cache:

```bash
php bin/console cache:clear
```

2. Register a new test user to see if the email is sent

## ⚠️ Important Notes

### Security:

- ✅ `.env.local` is already in `.gitignore` - it will NOT be committed to Git
- ✅ Never commit real credentials to Git
- ✅ Keep your app password secure
- ✅ The app password is specific to this application

### Gmail Limitations:

- 📧 Free Gmail accounts have sending limits (~500 emails per day)
- 📧 New Gmail accounts might have stricter limits initially
- 📧 Gmail might block suspicious activity - if this happens, check your Gmail security alerts

### Alternative: Using Environment Variables

For production, you can set environment variables directly on your server:

```bash
MAILER_DSN=gmail+smtp://your-email@gmail.com:your-app-password@default
```

## 🧪 Testing

### Test 1: Registration Email

1. Go to `/register`
2. Register a new user with a real email address you can check
3. Check the email inbox for the verification email

### Test 2: Check Symfony Profiler

1. After sending an email, click on the Symfony profiler toolbar at the bottom
2. Click on the "Email" tab (envelope icon)
3. You should see the email that was sent

### Test 3: Check Logs

If emails aren't sending, check the logs:

```bash
tail -f var/log/dev.log
```

## 🔧 Troubleshooting

### Problem: "Invalid credentials" or "Authentication failed"

**Solution:**

- Make sure you're using an App Password, not your regular Gmail password
- Remove any spaces from the app password
- Make sure 2-Step Verification is enabled

### Problem: "Connection refused" or "Could not connect"

**Solution:**

- Check your internet connection
- Gmail might be blocking the connection - check your Gmail security page
- Try using port 587 explicitly: `gmail+smtp://email:password@smtp.gmail.com:587`

### Problem: Emails are sent but not received

**Solution:**

- Check the spam/junk folder
- Check the recipient email address is correct
- Verify your Gmail account is in good standing (not suspended)

### Problem: "535-5.7.8 Username and Password not accepted"

**Solution:**

- You need to use an App Password, not your regular password
- Make sure 2-Step Verification is enabled on your Google account
- Generate a new App Password if the current one isn't working

## 📝 Full Configuration Example

**`.env.local` (create this file):**

```env
###> symfony/mailer ###
MAILER_DSN=gmail+smtp://myquizapp@gmail.com:abcdefghijklmnop@default
###< symfony/mailer ###
```

**What NOT to do:**
❌ Don't use your regular Gmail password
❌ Don't commit `.env.local` to Git
❌ Don't share your app password publicly
❌ Don't include spaces in the app password

**What to do:**
✅ Use Gmail App Password
✅ Keep `.env.local` private
✅ Clear cache after configuration changes
✅ Test with a real email address

## 🎉 After Setup

Once configured, your app will:

- ✅ Send verification emails when users register
- ✅ Use Gmail's SMTP server for reliable delivery
- ✅ Support HTML email templates
- ✅ Work in development and production (with proper config)

## Next Steps

Would you like me to also create:

1. A welcome email service for successful logins?
2. A password reset email feature?
3. Custom email templates with your branding?

Let me know and I can implement any of these features!
