# User Profile Testing Guide

## Created Files

### 1. Controller Routes

**File:** `src/Controller/UserController.php`

#### Page Routes (HTML):

- **`/profile`** - View user profile
- **`/profile/edit`** - Edit user profile
- **`/user/api-test`** - API testing page

#### API Routes (JSON):

- **`/getuser`** (GET) - Retrieve current user information
- **`/user/update`** (POST/PUT) - Update user information

### 2. Twig Templates

- **`templates/user/profile.html.twig`** - User profile view page
- **`templates/user/edit.html.twig`** - User profile edit page
- **`templates/user/api_test.html.twig`** - API testing interface

## How to Test

### Option 1: Using the Web Interface

1. **View Profile:**

   - Navigate to: `http://localhost/profile`
   - You'll see your current user information displayed in a table
   - Includes: ID, Username, Email, Roles, Creation Date, Verification Status

2. **Edit Profile:**
   - Navigate to: `http://localhost/profile/edit`
   - Modify any of the following fields:
     - Username
     - Email
   - To change password:
     - Enter current password
     - Enter new password
     - Confirm new password
   - Click "Update Profile"

### Option 2: Using the API Test Page

1. Navigate to: `http://localhost/user/api-test`
2. This page provides:
   - A button to test the GET `/getuser` endpoint
   - A form to test the POST `/user/update` endpoint
   - Real-time JSON responses displayed on the page

### Option 3: Using Command Line/Postman

#### Test GET /getuser:

```bash
curl -X GET http://localhost/getuser \
  -H "Content-Type: application/json" \
  --cookie "PHPSESSID=your_session_id"
```

**Expected Response:**

```json
{
  "id": 1,
  "email": "user@example.com",
  "username": "testuser",
  "roles": ["ROLE_USER"],
  "createdAt": "2025-12-05 10:30:00",
  "isVerified": true
}
```

#### Test POST /user/update:

```bash
curl -X POST http://localhost/user/update \
  -H "Content-Type: application/json" \
  --cookie "PHPSESSID=your_session_id" \
  -d '{
    "username": "newusername",
    "email": "newemail@example.com"
  }'
```

**With Password Change:**

```bash
curl -X POST http://localhost/user/update \
  -H "Content-Type: application/json" \
  --cookie "PHPSESSID=your_session_id" \
  -d '{
    "username": "newusername",
    "email": "newemail@example.com",
    "currentPassword": "oldpass123",
    "password": "newpass123"
  }'
```

**Expected Success Response:**

```json
{
  "message": "User information updated successfully",
  "user": {
    "id": 1,
    "email": "newemail@example.com",
    "username": "newusername",
    "roles": ["ROLE_USER"],
    "createdAt": "2025-12-05 10:30:00",
    "isVerified": true
  }
}
```

## Features

### Security Features:

- ✅ Requires authentication for all endpoints
- ✅ Password hashing using Symfony's UserPasswordHasher
- ✅ Optional current password verification when changing passwords
- ✅ Email format validation
- ✅ Never exposes password in responses

### User-Friendly Features:

- ✅ Auto-loads current user data in edit form
- ✅ Real-time validation
- ✅ Success/error messages with auto-dismiss
- ✅ Loading spinners during requests
- ✅ Confirmation required for password changes
- ✅ Automatic redirect after successful update

## Error Responses

### 401 Unauthorized:

```json
{
  "error": "User not authenticated"
}
```

### 400 Bad Request (Invalid Email):

```json
{
  "error": "Invalid email format"
}
```

### 400 Bad Request (Wrong Current Password):

```json
{
  "error": "Current password is incorrect"
}
```

### 400 Bad Request (Invalid JSON):

```json
{
  "error": "Invalid JSON data"
}
```

## Next Steps

1. Make sure you're logged in before testing
2. Test the basic profile view first: `/profile`
3. Try editing your profile: `/profile/edit`
4. Use the API test page for debugging: `/user/api-test`

## Notes

- All pages require user authentication
- Bootstrap CSS is used for styling (assumed from your base.html.twig)
- JavaScript fetches are used for AJAX requests
- CSRF protection may need to be configured if enabled in your Symfony app
