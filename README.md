# Student Management System

A complete Student Management System built with Core PHP, MySQLi, Bootstrap 5, HTML, CSS, JavaScript, and AJAX.

## Features

- **Admin Login** — Secure session-based authentication for administrators.
- **Student Login** — Email/password authentication for students, secured with PHP sessions.
- **Student Registration** — Full registration form with profile image upload.
- **Admin Dashboard** — Overview cards (total/active/inactive students) and latest 5 registrations.
- **Student Dashboard** — Welcome screen with profile details.
- **Student Management (CRUD)** — Add, edit, view, delete students with image upload.
- **AJAX Search & Pagination** — Live search and paginated student list without page reloads.
- **CSV Export** — Export all student records to CSV.
- **Profile Management** — Students can update phone, address, and profile picture.
- **Security** — Password hashing (bcrypt), prepared statements, XSS protection via `htmlspecialchars()`, session-based role authorization.

## Installation (XAMPP)

1. **Copy Project**
   - Place the `student_portal` folder inside `C:\xampp\htdocs\` (Windows) or `/Applications/XAMPP/htdocs/` (Mac).

2. **Create Database**
   - Open phpMyAdmin (`http://localhost/phpmyadmin`).
   - Click **Import** and select `database.sql`, OR run the SQL queries from `database.sql` in the SQL tab.
   - This creates the `student_portal` database with all required tables and a default admin account.

3. **Configure Database Connection**
   - Open `includes/db.php` and verify the credentials match your MySQL setup:
     ```php
     $host = 'localhost';
     $dbuser = 'root';
     $dbpass = '';
     $dbname = 'student_portal';
     ```

4. **Upload Directory**
   - The `assets/uploads/` folder already contains a `default.png` placeholder avatar.
   - Ensure this folder is writable so new profile images can be saved.

5. **Default Admin Credentials**
   - **Username:** `admin`
   - **Password:** `admin123`
   - ⚠️ If login fails with these credentials due to a hash mismatch (depends on PHP/bcrypt version), generate a new hash:
     ```php
     <?php echo password_hash('admin123', PASSWORD_DEFAULT); ?>
     ```
     Then update the `password` field for the `admin` row in the `admins` table.

6. **Run the Project**
   - Start Apache and MySQL from XAMPP Control Panel.
   - Visit: `http://localhost/student_portal/`

## Folder Structure

```
student_portal/
├── index.php              # Landing page
├── login.php               # Admin/Student login
├── register.php            # Student registration
├── logout.php               # Logout (session destroy)
├── ajax_search.php         # AJAX search endpoint (admin)
├── export_csv.php          # CSV export (admin)
├── assets/
│   ├── css/style.css
│   ├── js/script.js
│   └── uploads/            # Profile images (includes default.png)
├── includes/
│   ├── db.php
│   ├── header.php
│   ├── footer.php
│   ├── auth_check.php
│   ├── admin_check.php
│   └── student_check.php
├── admin/
│   ├── dashboard.php
│   ├── students.php
│   ├── add_student.php
│   ├── edit_student.php
│   ├── view_student.php
│   └── delete_student.php
├── student/
│   ├── dashboard.php
│   └── profile.php
├── database.sql
└── README.md
```

## Security Notes

- All passwords are hashed using `password_hash()` and verified with `password_verify()`.
- All database queries use **prepared statements** to prevent SQL injection.
- All user output is sanitized with `htmlspecialchars()` to prevent XSS.
- Role-based access is enforced via `admin_check.php` and `student_check.php`.
- File uploads are restricted to `.jpg`, `.jpeg`, `.png` with a 2MB size limit and renamed using `uniqid()` to prevent overwrites and path traversal.

## Notes

- Authentication is entirely session-based (`$_SESSION`) for both admins and students; no tokens or auth cookies are used.
- Pagination shows 5 students per page on both the admin students list and AJAX search.
