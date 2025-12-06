# QuizzBlast - Routes & Functionalities

## 📍 Application Routes

### 🏠 Public Routes (No Authentication Required)

| Route | URL | Description |
|-------|-----|-------------|
| `app_home` | `/app_home` | Homepage with hero section, features, and statistics |
| `app_login` | `/login` | User login page |
| `app_register` | `/register` | User registration page |
| `app_verify_email` | `/verify/email` | Email verification after registration |

---

### 👤 User Management Routes (Authentication Required)

| Route | URL | Method | Description |
|-------|-----|--------|-------------|
| `user_profile` | `/profile` | GET | View user profile with statistics and account info |
| `user_edit` | `/profile/edit` | GET | Edit profile page (update username, email, password) |
| `getuser` | `/getuser` | GET | API endpoint - Get current user info as JSON |
| `user_update` | `/user/update` | POST/PUT | API endpoint - Update user information |
| `app_logout` | `/logout` | ANY | Logout user and redirect to homepage |

**Features:**
- ✅ View profile with account statistics
- ✅ Edit username and email
- ✅ Change password with strength indicator
- ✅ Email verification status display
- ✅ Account creation date

---

### 📝 Quiz Management Routes (Authentication Required)

**Base Path:** `/quiz`

| Route | URL | Method | Description |
|-------|-----|--------|-------------|
| `app_quiz_index` | `/quiz/` | GET | Browse all public quizzes |
| `app_quiz_my_quizzes` | `/quiz/my-quizzes` | GET | View your created quizzes |
| `app_quiz_new` | `/quiz/new` | GET/POST | Create a new quiz |
| `app_quiz_show` | `/quiz/{id}` | GET | View quiz details |
| `app_quiz_edit` | `/quiz/{id}/edit` | GET/POST | Edit an existing quiz |
| `app_quiz_delete` | `/quiz/{id}` | POST | Delete a quiz |

**Features:**
- ✅ Create quizzes with multiple questions
- ✅ Add questions with multiple answers
- ✅ Mark correct answers
- ✅ Set quiz difficulty levels
- ✅ Make quizzes public or private
- ✅ Edit and delete your quizzes

---

### 🎮 Game Session Routes (Authentication Required)

| Route | URL | Method | Description |
|-------|-----|--------|-------------|
| `game_start` | `/game/start/{id}` | GET | Start a new game session for a quiz |
| `game_end` | `/game/{code}/end` | GET | End game session |
| `game_leaderboard` | `/game/{code}/leaderboard` | GET | View game leaderboard |

**Features:**
- ✅ Host live quiz games
- ✅ Generate unique game codes
- ✅ Real-time leaderboards
- ✅ Track player scores
- ✅ Multiplayer support

---

## 🎯 Main Features

### 1. **User Authentication System**
- Registration with email verification
- Login with username OR email
- Password hashing with Symfony security
- Remember me functionality
- Secure logout with session cleanup
- Email notifications on login

### 2. **User Profile Management**
- View profile with statistics
- Update personal information
- Change password with strength validation
- Email verification status
- Account creation tracking

### 3. **Quiz Creation & Management**
- Create custom quizzes
- Add multiple questions per quiz
- Multiple choice answers (2-6 options)
- Mark correct answers
- Set difficulty levels (Easy, Medium, Hard)
- Public/Private visibility
- Edit and delete capabilities

### 4. **Game Hosting**
- Start live game sessions
- Generate unique join codes
- Real-time participant tracking
- Automated scoring system
- Live leaderboards
- Host controls for game flow

### 5. **Email System**
- Welcome emails on registration
- Email verification links
- Login notification emails
- Async/Sync email delivery options
- Gmail SMTP integration

### 6. **Modern UI/UX**
- Professional gradient design (Purple/Indigo theme)
- Fully responsive (Mobile, Tablet, Desktop)
- Bootstrap 5 with custom styling
- Icon-enhanced navigation
- Smooth animations and transitions
- Loading states and feedback
- Form validation with visual feedback

---

## 🔐 Security Features

- ✅ Password hashing with auto algorithm
- ✅ CSRF protection on all forms
- ✅ Email verification for new accounts
- ✅ Remember me with secure tokens
- ✅ Session management
- ✅ User authentication required for protected routes
- ✅ Role-free system (all users have ROLE_USER)

---

## 📊 Database Entities

### **User**
- id, username, email, password (hashed)
- createdAt, isVerified
- Relations: quizzes (one-to-many), hostedSessions (one-to-many)

### **Quiz**
- id, title, description, difficulty
- isPublic, isActive, createdAt
- Relations: createdBy (user), questions (one-to-many), gameSessions (one-to-many)

### **Question**
- id, text, points, timeLimit, mediaUrl
- Relations: quiz (many-to-one), answers (one-to-many)

### **Answer**
- id, text, isCorrect, orderIndex
- Relations: question (many-to-one)

### **GameSession**
- id, code (unique), status, startedAt
- currentQuestionIndex
- Relations: quiz, host (user), participants (one-to-many)

### **GameParticipant**
- id, nickname, totalScore, joinedAt
- Relations: gameSession, user (optional)

### **PlayerAnswer**
- id, points, timeToAnswer, answeredAt
- Relations: gameParticipant, question, selectedAnswer

---

## 🎨 Design System

**Colors:**
- Primary: Indigo (#6366f1)
- Gradient: #667eea → #764ba2
- Success: Green (#10b981)
- Danger: Red (#ef4444)
- Warning: Amber (#f59e0b)

**Typography:**
- Font: Poppins (Google Fonts)

**Components:**
- Cards with rounded corners (1.5rem)
- Gradient buttons
- Icon-enhanced inputs
- Animated loading states
- Toast notifications
- Modal dialogs

---

## 📱 Responsive Breakpoints

- **Mobile:** < 768px
- **Tablet:** 768px - 1024px
- **Desktop:** > 1024px

---

## 🚀 Quick Navigation

### For Guests:
1. Visit `/app_home` - View homepage
2. Click "Get Started Free" → `/register`
3. Verify email via link sent
4. Login at `/login`

### For Authenticated Users:
1. Dashboard: `/profile`
2. Create Quiz: `/quiz/new`
3. My Quizzes: `/quiz/my-quizzes`
4. Browse Quizzes: `/quiz/`
5. Start Game: `/game/start/{quiz_id}`

---

## 📈 Future Enhancements (Planned)

- Player dashboard for joining games
- Real-time WebSocket for live games
- Quiz categories and tags
- Search and filtering
- Quiz ratings and reviews
- Achievement badges
- Social sharing
- Dark mode toggle
- Profile pictures
- Advanced analytics

---

**Built with Symfony 6.4 | PHP 8.2 | MySQL | Bootstrap 5**

*Last Updated: December 6, 2025*
