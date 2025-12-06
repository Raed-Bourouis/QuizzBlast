# QuizzBlast - Team Setup Guide

## 🚀 Quick Setup (3 Steps)

### 1️⃣ Install Dependencies
```bash
composer install
```

### 2️⃣ Configure Database
Edit `.env.local` (create if it doesn't exist):
```env
DATABASE_URL="mysql://username:password@127.0.0.1:3306/quizz_blast?serverVersion=8.0"
MAILER_DSN=gmail+smtp://your-email@gmail.com:your-app-password@default
```

### 3️⃣ Setup Database
```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

**✅ Done! Start the server:**
```bash
symfony server:start
# OR
php -S localhost:8000 -t public
```

---

## 📦 Required Dependencies

### Already Installed (via composer.json):
- ✅ `symfony/framework-bundle` - Core framework
- ✅ `symfony/security-bundle` - Authentication system
- ✅ `symfony/mailer` - Email system
- ✅ `symfony/google-mailer` - Gmail SMTP integration
- ✅ `symfony/messenger` - Async email delivery
- ✅ `doctrine/orm` - Database ORM
- ✅ `doctrine/doctrine-bundle` - Doctrine integration
- ✅ `doctrine/doctrine-migrations-bundle` - Database migrations
- ✅ `twig/twig` - Template engine
- ✅ `symfonycasts/verify-email-bundle` - Email verification

**No additional installation needed if you run `composer install`!**

---

## 🗄️ Database Commands

### Create Database
```bash
php bin/console doctrine:database:create
```

### Run Migrations
```bash
php bin/console doctrine:migrations:migrate
```
⚠️ **Important:** Running migrations is **required** - the code alone won't create tables!

### Check Migration Status
```bash
php bin/console doctrine:migrations:status
```

### Generate New Migration (after entity changes)
```bash
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

### Rollback Last Migration
```bash
php bin/console doctrine:migrations:migrate prev
```

---

## ⚙️ Configuration Files

### `.env.local` (Create this file - not in Git)
```env
# Database
DATABASE_URL="mysql://root:@127.0.0.1:3306/quizz_blast?serverVersion=8.0"

# Gmail SMTP (get app password from Google Account)
MAILER_DSN=gmail+smtp://your-email@gmail.com:your-app-password@default

# Messenger (for async emails)
MESSENGER_TRANSPORT_DSN=doctrine://default?auto_setup=0
```

### Gmail App Password Setup:
1. Go to Google Account → Security
2. Enable 2-Step Verification
3. Create App Password
4. Use that password in MAILER_DSN

---

## 🧪 Useful Commands

### Clear Cache
```bash
php bin/console cache:clear
```

### View All Routes
```bash
php bin/console debug:router
```

### Check Environment Variables
```bash
php bin/console debug:container --env-vars
```

### Send Test Email
```bash
php bin/console app:test-email your-email@example.com
```

### Process Queued Emails (if using async)
```bash
php bin/console messenger:consume async
```

### Create New Controller
```bash
php bin/console make:controller ControllerName
```

### Create New Entity
```bash
php bin/console make:entity EntityName
```

---

## 📁 Project Structure

```
QuizzBlast/
├── config/              # Configuration files
│   ├── packages/       # Bundle configurations
│   │   ├── security.yaml
│   │   ├── messenger.yaml
│   │   └── doctrine.yaml
│   └── routes.yaml
├── migrations/          # Database migrations
├── public/             # Web root
│   └── index.php
├── src/
│   ├── Controller/     # Route controllers
│   ├── Entity/         # Database entities
│   ├── Form/          # Form types
│   ├── Repository/    # Database repositories
│   ├── Security/      # Authentication
│   └── Service/       # Business logic
├── templates/         # Twig templates
├── var/
│   ├── cache/        # Cache files
│   └── log/          # Log files
├── .env              # Environment variables (in Git)
├── .env.local        # Local overrides (NOT in Git)
├── composer.json     # PHP dependencies
└── composer.lock     # Locked versions
```

---

## 🔧 Common Issues & Solutions

### Issue: "Table doesn't exist"
**Solution:** Run migrations
```bash
php bin/console doctrine:migrations:migrate
```

### Issue: "Emails not being sent"
**Solution:** Process the queue
```bash
php bin/console messenger:consume async --limit=10
```

### Issue: "Route not found"
**Solution:** Clear cache
```bash
php bin/console cache:clear
```

### Issue: "Access denied for database"
**Solution:** Check DATABASE_URL in `.env.local`

### Issue: "Cannot send email"
**Solution:** 
1. Check MAILER_DSN in `.env.local`
2. Verify Gmail app password
3. Check if 2-Step Verification is enabled

---

## 🎯 Development Workflow

### 1. Pull Latest Code
```bash
git pull origin bechir
```

### 2. Install/Update Dependencies
```bash
composer install
```

### 3. Update Database
```bash
php bin/console doctrine:migrations:migrate
```

### 4. Clear Cache
```bash
php bin/console cache:clear
```

### 5. Start Development Server
```bash
symfony server:start
```

---

## 🧑‍💻 Making Changes

### Adding a New Feature:

**1. Create Entity (if needed):**
```bash
php bin/console make:entity EntityName
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

**2. Create Controller:**
```bash
php bin/console make:controller FeatureController
```

**3. Create Form (if needed):**
```bash
php bin/console make:form FeatureType
```

**4. Create Template:**
Create file in `templates/feature/`

**5. Test:**
- Visit route in browser
- Check logs: `var/log/dev.log`

---

## 📧 Email Configuration

### Sync Email (Immediate Sending)
In `config/packages/messenger.yaml`:
```yaml
routing:
    Symfony\Component\Mailer\Messenger\SendEmailMessage: sync
```

### Async Email (Queue for Background Processing)
```yaml
routing:
    Symfony\Component\Mailer\Messenger\SendEmailMessage: async
```
**Then run worker:**
```bash
php bin/console messenger:consume async
```

---

## 🐛 Debugging

### View Logs
```bash
# All logs
Get-Content var/log/dev.log

# Last 50 lines
Get-Content var/log/dev.log -Tail 50

# Search for errors
Get-Content var/log/dev.log | Select-String -Pattern "error"
```

### Check Database Connection
```bash
php bin/console doctrine:query:sql "SELECT 1"
```

### Validate Doctrine Entities
```bash
php bin/console doctrine:schema:validate
```

---

## 🚀 Deployment Checklist

- [ ] Set `APP_ENV=prod` in `.env.local`
- [ ] Update `DATABASE_URL` with production database
- [ ] Configure production `MAILER_DSN`
- [ ] Run `composer install --no-dev --optimize-autoloader`
- [ ] Run `php bin/console cache:clear --env=prod`
- [ ] Run `php bin/console doctrine:migrations:migrate --env=prod`
- [ ] Set up background worker for async emails
- [ ] Configure web server (Apache/Nginx)
- [ ] Enable HTTPS
- [ ] Set up regular database backups

---

## 📚 Documentation Files

- `ROUTES_AND_FEATURES.md` - Complete routes and features guide
- `UI_REDESIGN_COMPLETE.md` - UI design documentation
- `EMAIL_ASYNC_SOLUTION.md` - Email configuration guide
- `ROUTE_NAMES_FIXED.md` - Route naming conventions

---

## 🆘 Need Help?

### Symfony Documentation:
- https://symfony.com/doc/current/index.html

### Check Route Details:
```bash
php bin/console debug:router app_quiz_index
```

### Check Service Container:
```bash
php bin/console debug:container
```

### Check Event Listeners:
```bash
php bin/console debug:event-dispatcher
```

---

## ⚡ Quick Reference

| Task | Command |
|------|---------|
| Install dependencies | `composer install` |
| Create database | `php bin/console doctrine:database:create` |
| Run migrations | `php bin/console doctrine:migrations:migrate` |
| Clear cache | `php bin/console cache:clear` |
| Start server | `symfony server:start` |
| View routes | `php bin/console debug:router` |
| Process emails | `php bin/console messenger:consume async` |
| Generate migration | `php bin/console make:migration` |

---

**Remember:** After pulling code, always run:
```bash
composer install
php bin/console doctrine:migrations:migrate
php bin/console cache:clear
```

---

*QuizzBlast Team Guide - Last Updated: December 6, 2025*
