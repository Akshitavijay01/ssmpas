# SSMPAS - Student/Staff Management & Performance Analysis System

A professional, secure, and modern academic management system built for educational institutions. This system provides comprehensive tools for managing students, teachers, classes, exams, attendance, marks, and performance analytics.

## Features

### Admin Dashboard
- Complete system overview with statistics cards
- Student, Teacher, Department, Class, Subject, and Exam management (full CRUD)
- Marks management with automatic grade calculation
- Attendance tracking and visualization
- Results generation and publication
- System-wide notifications
- Activity logs with audit trail
- Reports with charts and print support
- System settings configuration

### Teacher Dashboard
- Student list view
- Mark entry and editing (own entries only)
- Single and bulk attendance marking
- Performance analytics with Chart.js
- Reports and student performance insights
- Notifications and profile management

### Student Dashboard
- Personal attendance statistics and trend charts
- Marks and grades view (own data only)
- Published results with grade cards
- Performance analytics with subject-wise charts
- Printable academic reports
- Notifications and profile management

## Technology Stack

| Component | Technology |
|-----------|-----------|
| Backend | PHP 8+ |
| Database | MySQL 8.0+ / MariaDB 10.3+ |
| Server | Apache (WAMP recommended) |
| Frontend | HTML5, CSS3, Bootstrap 5, Chart.js |
| Fonts | Google Fonts (Inter) |
| Icons | Bootstrap Icons |

## Requirements

- PHP 8.0 or higher
- MySQL 8.0+ or MariaDB 10.3+
- Apache with `mod_rewrite` enabled
- WAMP Server 3.x+ (recommended for Windows)

## Installation (WAMP)

### Step 1: Set Up the Project

1. Copy/clone the `ssmpas` folder into your WAMP `www` directory:
   ```
   C:\wamp64\www\ssmpas\
   ```

### Step 2: Create the Database

1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Click the **SQL** tab
3. Open the `database.sql` file from the project folder and paste the contents
4. Click **Go** to execute

Alternatively, via command line:
```bash
mysql -u root -p < C:\wamp64\www\ssmpas\database.sql
```

### Step 3: Configure Database Credentials

Edit `config/db.php` if your MySQL setup differs:
```php
define('DB_HOST', 'localhost');  // or '127.0.0.1'
define('DB_NAME', 'ssmpas');
define('DB_USER', 'root');
define('DB_PASS', '');           // your MySQL password
define('DB_CHARSET', 'utf8mb4');
```

### Step 4: Start the Application

1. Start WAMP Server
2. Open your browser and go to:
   ```
   http://localhost/ssmpas/
   ```
3. You should see the landing page

## Default Test Accounts

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@ssmpas.com | Admin@123 |
| Teacher | teacher@ssmpas.com | Teacher@123 |
| Student | student@ssmpas.com | Student@123 |

> **Note:** All default passwords are `Admin@123` / `Teacher@123` / `Student@123` - change these after first login.

## Roles & Permissions

### Administrator
- Full access to all modules
- Manage all users, departments, classes, subjects, exams
- Add/edit/delete marks and attendance
- Generate and publish results
- Send notifications to all roles
- View activity logs and system settings

### Teacher
- View all students
- Add/edit/delete own marks entries
- Mark attendance (single and bulk)
- View performance analytics
- Generate reports
- View and receive notifications
- Manage own profile

### Student
- View own dashboard only (never sees other students)
- View own marks, attendance, and results
- View personal performance analytics
- Print academic reports
- View notifications
- Update profile and change password

## Folder Structure

```
ssmpas/
├── admin/                  # Admin module pages
│   ├── dashboard.php       # Admin dashboard
│   ├── students.php        # Student management (CRUD)
│   ├── teachers.php        # Teacher management
│   ├── departments.php     # Department management
│   ├── classes.php         # Class management
│   ├── subjects.php        # Subject management
│   ├── exams.php           # Exam management
│   ├── marks.php           # Marks management
│   ├── attendance.php      # Attendance records
│   ├── results.php         # Results generation/publishing
│   ├── notifications.php   # Send/manage notifications
│   ├── reports.php         # System reports and charts
│   ├── activity_logs.php   # Audit activity log
│   ├── settings.php        # System settings
│   └── profile.php         # Admin profile
├── teacher/                # Teacher module pages
│   ├── dashboard.php       # Teacher dashboard
│   ├── students.php        # View students
│   ├── marks.php           # Mark entry/edit
│   ├── attendance.php      # Mark attendance
│   ├── performance.php     # Performance analytics
│   ├── reports.php         # Teacher reports
│   ├── notifications.php   # View notifications
│   └── profile.php         # Teacher profile
├── student/                # Student module pages
│   ├── dashboard.php       # Student dashboard (own data only)
│   ├── marks.php           # View own marks
│   ├── attendance.php      # View own attendance
│   ├── results.php         # View own results
│   ├── performance.php     # Personal performance analytics
│   ├── reports.php         # Personal printable reports
│   ├── notifications.php   # View notifications
│   └── profile.php         # Student profile
├── auth/                   # Authentication
│   ├── login.php           # Unified login (all roles)
│   ├── register.php        # Account registration
│   └── logout.php          # Session termination
├── config/                 # Configuration
│   ├── db.php              # Database connection (PDO)
│   └── index.php           # Landing page
├── includes/               # Shared components
│   ├── helpers.php         # Utility and security functions
│   ├── header.php          # HTML head template
│   ├── footer.php          # HTML footer template
│   ├── sidebar_admin.php   # Admin sidebar
│   ├── sidebar_teacher.php # Teacher sidebar
│   ├── sidebar_student.php # Student sidebar
│   ├── topbar.php          # Top navigation bar
│   ├── alerts.php          # Flash message display
│   ├── error_403.php       # Forbidden error page
│   ├── error_404.php       # Not found error page
│   └── error_500.php       # Server error page
├── middlewares/             # Middleware
│   └── auth_guard.php      # Authentication guard
├── assets/                 # Static assets
│   ├── css/
│   │   ├── dashboard.css   # Dashboard layout CSS
│   │   └── login.css       # Login page CSS
│   └── js/
│       └── sidebar.js      # Sidebar toggle JS
├── uploads/                # Uploaded files (profile photos)
├── logs/                   # Error logs
├── database.sql            # Complete database schema
└── README.md               # This file
```

## Security Improvements

- **SQL Injection Protection**: All queries use PDO prepared statements
- **CSRF Protection**: All forms include CSRF token validation
- **Password Hashing**: All passwords hashed with `password_hash()` (bcrypt)
- **Session Security**: `session_regenerate_id()` on login, httpOnly cookies
- **Input Validation**: Server-side validation on all inputs
- **Output Escaping**: All user output escaped with `htmlspecialchars()`
- **Role-Based Authorization**: Every page verifies login + correct role
- **Data Isolation**: Students see ONLY their own data
- **File Upload Validation**: MIME type, extension, and size checking
- **Activity Logging**: All significant actions logged with timestamp and IP
- **Error Handling**: PHP/MySQL errors hidden from users, logged to file
- **Security Headers**: Proper Content-Security-Policy headers

## Key Design Decisions

1. **Unified Authentication**: Single login system for all three roles
2. **Prepared Statements**: All database queries use parameterized statements
3. **Bootstrap 5**: Modern responsive UI framework
4. **Chart.js**: Professional data visualization
5. **Procedural PHP**: No complex framework required, WAMP compatible
6. **No JavaScript Frameworks**: Server-rendered pages, minimal JS

## Troubleshooting

### "System temporarily unavailable" error
- Check `config/db.php` for correct MySQL credentials
- Ensure MySQL is running in WAMP
- Ensure the `ssmpas` database exists (run `database.sql`)

### Blank page or 500 error
- Check PHP error log in WAMP: `C:\wamp64\logs\php_error.log`
- Check Apache error log: `C:\wamp64\logs\apache_error.log`

### Login not working
- Ensure you've created the database with `database.sql`
- Default passwords: `Admin@123`, `Teacher@123`, `Student@123`

### Charts not showing
- Ensure you have internet access (Chart.js loads from CDN)
- Check browser console for JavaScript errors

## Development Notes

- The project uses **PDO** (not mysqli) for all database operations
- All database credentials are centralized in `config/db.php`
- All helper functions are in `includes/helpers.php`
- CSRF tokens are generated per-session and validated on all POST requests
- Flash messages are used for success/error feedback across redirects

## License

This project is intended for educational purposes (final-year college project).

---

**Built with PHP 8+, MySQL 8, Bootstrap 5, Chart.js**
