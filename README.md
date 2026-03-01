# Busuu Dashboard 🎓

A full-stack **Bento-style Learning Dashboard** built with **Laravel 11 + Inertia.js + Vue 3**. Track language learning statistics, manage courses, import Excel data, and visualize progress with beautiful charts.

---

## 🛠️ Technology Stack

| Layer | Technology |
|-------|-----------|
| Backend | Laravel 12 (PHP 8.3) |
| Frontend | Vue 3 (Composition API) + Inertia.js |
| Styling | Tailwind CSS |
| Database | MySQL / PostgreSQL / SQLite |
| Charts | Chart.js |
| State | Pinia |
| Excel Import | Maatwebsite/Laravel-Excel |
| Auth | Laravel Breeze |

---

## 🚀 Installation

### Prerequisites
- PHP 8.2+
- Composer 2+
- Node.js 18+
- SQLite / MySQL / PostgreSQL

### 1. Clone & Install PHP dependencies
```bash
git clone https://github.com/Sodap239519/Busuu-Dashboard.git
cd Busuu-Dashboard
composer install
```

### 2. Install Node dependencies & Build assets
```bash
npm install
npm run build
```

### 3. Environment setup
```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` for your database. For SQLite (quickest):
```env
DB_CONNECTION=sqlite
# leave DB_DATABASE blank to use database/database.sqlite
```

For MySQL:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=busuu_dashboard
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### 4. Database setup & Seed
```bash
touch database/database.sqlite   # SQLite only
php artisan migrate --seed
```

### 5. Start development server
```bash
php artisan serve
npm run dev   # In a separate terminal (for hot reload)
```

Visit: **http://localhost:8000**

---

## 👤 Default Accounts

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@busuu.test | password |
| User | alice@busuu.test | password |
| User | bob@busuu.test | password |
| User | carol@busuu.test | password |
| User | david@busuu.test | password |

---

## 📱 Features

### User Dashboard (Bento-style)
- 📊 **Stats Cards** – Total hours, XP, courses count, completed courses
- 📈 **Activity Chart** – Chart.js line graph showing last 14 days
- 🔥 **Streak Card** – Current streak with 7-day visual calendar
- 🏆 **Achievements** – Badge grid (locked/unlocked states)
- 📝 **Activity Timeline** – Recent learning sessions
- 📚 **Course Cards** – Progress bars per course

### Admin Panel
- ⚙️ **Admin Dashboard** – Platform-wide stats and recent sessions table
- 📤 **Excel Import** – Drag & drop file upload with import history

---

## 📤 Excel Import Format

### Learning Sessions (`/admin/import`)
```csv
user_email,course_name,duration_min,session_date,xp_earned,completed
alice@busuu.test,English A1,30,2024-01-15,50,true
bob@busuu.test,Spanish A2,45,2024-01-16,75,false
```

### Courses
```csv
name,language,level,description,total_lessons,estimated_hours,icon,color
English C1,English,C1,Advanced English,35,25,🇬🇧,#1D4ED8
```

---

## 🗂️ Project Structure

```
├── app/
│   ├── Http/Controllers/
│   │   ├── DashboardController.php
│   │   └── Admin/
│   │       ├── AdminDashboardController.php
│   │       └── ImportController.php
│   ├── Http/Middleware/AdminMiddleware.php
│   ├── Imports/
│   │   ├── LearningSessionsImport.php
│   │   └── CoursesImport.php
│   ├── Models/ (User, Course, Lesson, UserProgress, LearningSession, Achievement)
│   └── Services/StatisticsService.php
├── database/
│   ├── migrations/
│   └── seeders/ (DatabaseSeeder, CourseSeeder, UserSeeder)
└── resources/js/
    ├── Pages/
    │   ├── Dashboard/Index.vue
    │   └── Admin/ (Dashboard.vue, Import.vue)
    ├── Components/
    │   ├── Dashboard/ (StatsCard, ProgressChart, CourseCard, StreakCard, ActivityTimeline, AchievementBadges)
    │   └── Admin/ (FileUploader, ImportHistory)
    └── Stores/dashboard.js
```

---

## 🔒 Routes

| Method | URL | Description |
|--------|-----|-------------|
| GET | `/` | Welcome page |
| GET | `/dashboard` | User dashboard (auth) |
| GET | `/admin/dashboard` | Admin dashboard (admin only) |
| GET | `/admin/import` | Import page (admin only) |
| POST | `/admin/import` | Upload Excel file (admin only) |

---

## 📊 Database Schema

- **users** – name, email, password, role (user/admin), avatar
- **courses** – name, language, level, description, total_lessons, estimated_hours, icon, color
- **lessons** – course_id, title, order, type, duration_minutes
- **user_progress** – user_id, course_id, lessons_completed, progress_percentage
- **learning_sessions** – user_id, course_id, duration_minutes, xp_earned, session_date
- **achievements** – user_id, type, name, description, icon, earned_at
