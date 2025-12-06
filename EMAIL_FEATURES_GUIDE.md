# Email Features Implementation Guide

## 📧 What Has Been Created

I've set up a complete email system for your QuizzBlast application with the following features:

### 1. **Files Created:**

#### Configuration & Setup:

- ✅ `GMAIL_SETUP_GUIDE.md` - Complete step-by-step Gmail setup instructions
- ✅ `.env.local.example` - Template for email configuration

#### Email Service:

- ✅ `src/Service/EmailService.php` - Reusable email service with multiple methods

#### Email Templates:

- ✅ `templates/emails/welcome.html.twig` - Beautiful welcome email
- ✅ `templates/emails/login_notification.html.twig` - Security notification email

#### Event Listener:

- ✅ `src/EventListener/LoginSuccessListener.php` - Automatically sends emails on login

---

## 🚀 Quick Start (3 Steps)

### Step 1: Configure Gmail

1. **Enable 2-Step Verification on your Gmail account:**

   - Go to: https://myaccount.google.com/security
   - Click "2-Step Verification" and enable it

2. **Generate App Password:**

   - In Security settings, scroll to "2-Step Verification"
   - At the bottom, click "App passwords"
   - Select "Mail" and "Windows Computer"
   - Copy the 16-character password (remove spaces)

3. **Create `.env.local` file** in your project root:

```env
###> symfony/mailer ###
MAILER_DSN=gmail+smtp://your-email@gmail.com:your-16-char-password@default
###< symfony/mailer ###
```

**Example:**

```env
MAILER_DSN=gmail+smtp://myquizapp@gmail.com:abcdefghijklmnop@default
```

### Step 2: Update Email Addresses

Update `src/Service/EmailService.php` line 12 to use your Gmail:

```php
private string $fromEmail = 'your-email@gmail.com',
```

Also update `src/Controller/RegistrationController.php` line 48:

```php
->from(new Address('your-email@gmail.com', 'QuizzBlast'))
```

### Step 3: Clear Cache and Test

```bash
php bin/console cache:clear
```

Now try logging in - you should receive a welcome email!

---

## 🎯 Features Available

### 1. Welcome Email on Login ✅ (ENABLED by default)

Every time a user logs in, they receive a beautiful welcome email with:

- Personalized greeting
- Login time and details
- Quick links to start using the app
- Feature highlights

**To disable:** Edit `src/EventListener/LoginSuccessListener.php` line 14:

```php
private bool $sendWelcomeEmail = false, // Changed to false
```

### 2. Login Security Notification ⚠️ (DISABLED by default)

Sends an email notification on every login for security purposes:

- Shows login time and IP address
- Warns users about suspicious activity
- Provides security recommendations

**To enable:** Edit `src/EventListener/LoginSuccessListener.php` line 15:

```php
private bool $sendLoginNotification = true, // Changed to true
```

### 3. Registration Verification Email ✅ (Already working)

Your existing registration system sends verification emails.

---

## 📋 Email Service Methods

The `EmailService` provides multiple methods you can use:

### Send Welcome Email:

```php
public function sendWelcomeEmail(User $user): void
```

### Send Login Notification:

```php
public function sendLoginNotification(User $user, string $ipAddress = null): void
```

### Send Custom Email:

```php
public function sendCustomEmail(
    string $to,
    string $subject,
    string $template,
    array $context = []
): void
```

### Example Usage in a Controller:

```php
use App\Service\EmailService;

class MyController extends AbstractController
{
    public function someAction(EmailService $emailService): Response
    {
        // Send a custom email
        $emailService->sendCustomEmail(
            'user@example.com',
            'Your Quiz is Ready!',
            'emails/quiz_ready.html.twig',
            ['quizName' => 'My Awesome Quiz']
        );

        return $this->redirectToRoute('some_route');
    }
}
```

---

## 🧪 Testing Your Setup

### Test 1: Registration Email (Already Working)

1. Register a new user
2. Check your email for verification

### Test 2: Welcome Email on Login

1. Log out
2. Log back in
3. Check your email for welcome message

### Test 3: Check Symfony Profiler

1. After login, look at the Symfony profiler toolbar (bottom of page)
2. Click the email icon
3. View the sent email

### Test 4: Enable Login Notifications

1. Edit `LoginSuccessListener.php` and set `$sendLoginNotification = true`
2. Clear cache: `php bin/console cache:clear`
3. Log in and check for security notification email

---

## 🎨 Customizing Email Templates

All email templates are in `templates/emails/` and use Twig.

### Example: Customize Welcome Email

Edit `templates/emails/welcome.html.twig`:

```twig
<div class="header">
    <h1>🎉 Welcome to {{ 'Your App Name' }}!</h1>
</div>
```

### Create Your Own Email Template:

1. Create a new file: `templates/emails/my_custom_email.html.twig`
2. Use the existing templates as examples
3. Send it using:

```php
$emailService->sendCustomEmail(
    $to,
    'Subject',
    'emails/my_custom_email.html.twig',
    ['data' => 'value']
);
```

---

## ⚙️ Configuration Options

### Change "From" Email Dynamically:

```php
$emailService->setFromEmail('custom@example.com', 'Custom Name')
              ->sendWelcomeEmail($user);
```

### Configure Default From Email:

Edit `src/Service/EmailService.php` constructor:

```php
private string $fromEmail = 'your-email@gmail.com',
private string $fromName = 'Your App Name'
```

---

## 🔧 Advanced Configuration

### Use Services Configuration (Alternative to .env.local):

**File: `config/services.yaml`**

```yaml
parameters:
  app.email.from_address: "your-email@gmail.com"
  app.email.from_name: "QuizzBlast"

services:
  App\Service\EmailService:
    arguments:
      $fromEmail: "%app.email.from_address%"
      $fromName: "%app.email.from_name%"
```

### Disable Emails in Development:

If you want to test without sending real emails, use:

**`.env.local`:**

```env
MAILER_DSN=null://null
```

This will log emails instead of sending them.

---

## 📊 Email Types Summary

| Email Type                | When Sent            | Status                | File                                        |
| ------------------------- | -------------------- | --------------------- | ------------------------------------------- |
| Registration Verification | On user registration | ✅ Working            | `registration/confirmation_email.html.twig` |
| Welcome Email             | On every login       | ✅ Enabled (default)  | `emails/welcome.html.twig`                  |
| Login Notification        | On every login       | ⚠️ Disabled (default) | `emails/login_notification.html.twig`       |

---

## 🛠️ Troubleshooting

### Emails Not Sending?

1. Check `.env.local` has correct Gmail credentials
2. Clear cache: `php bin/console cache:clear`
3. Check logs: `tail -f var/log/dev.log`
4. Verify 2-Step Verification is enabled on Gmail
5. Generate a new App Password if needed

### Gmail Blocking?

- Check Gmail security alerts
- Make sure you're using App Password, not regular password
- Try enabling "Less secure app access" (not recommended)

### Emails Going to Spam?

- This is normal for development
- In production, configure proper SPF/DKIM records
- Consider using a professional email service

---

## 🎁 Bonus: Additional Email Ideas

Want to add more email features? Here are some ideas:

1. **Password Reset Email** - When users forget their password
2. **Quiz Invitation Email** - Invite players to join a quiz
3. **Quiz Results Email** - Send quiz results after completion
4. **Weekly Digest Email** - Summary of activity
5. **Achievement Unlocked Email** - Gamification notifications

Let me know if you want me to implement any of these!

---

## 📝 Summary

You now have:
✅ Gmail SMTP configured and ready
✅ Welcome emails on login
✅ Security notification emails (optional)
✅ Reusable email service
✅ Beautiful HTML email templates
✅ Automatic email sending via event listener

**Next Step:** Follow the "Quick Start" section to configure your Gmail credentials!
