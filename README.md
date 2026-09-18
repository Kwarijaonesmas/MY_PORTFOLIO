# Bushenyi Hostel Booking & Management System

> **A modern, production-ready PHP & MySQL web application tailored for student accommodation management, room bookings, and MTN Mobile Money payments in Bushenyi, Uganda (KIU Western Campus region).**

---

## 📋 Table of Contents
1. [System Features](#system-features)
2. [Technology Stack](#technology-stack)
3. [User Roles & Permissions](#user-roles--permissions)
4. [Database Architecture & Schema (`mydb`)](#database-architecture--schema-mydb)
5. [Directory & File Structure](#directory--file-structure)
6. [Installation & Local Setup Guide](#installation--local-setup-guide)
7. [Default Access Credentials](#default-access-credentials)
8. [Security & Implementation Details](#security--implementation-details)

---

## 🌟 System Features

### 1. Public Visitor & Search Portal
- **Hero Search Bar**: Filter hostels dynamically by location (`Ishaka Town`, `KIU Road`, `Katungu`, `Kashenyi`), maximum budget range (UGX), and room type (`Single`, `Double`, `Self-contained`).
- **Real-time Inventory Counters**: Live counts of available rooms per hostel.
- **Admin-Approved Listings**: Public search strictly displays verified hostels with `approved` status.

### 2. Student Portal (`/student/`)
- **Interactive Room Booking**: Instant booking of available rooms with automated status updates from `available` to `booked`.
- **MTN Mobile Money Gateway Mockup**: Uganda MTN MoMo payment simulation with phone number validation (`077...`/`078...`), 4-digit PIN authentication, transaction reference generation, and print-ready digital receipts.
- **My Bookings Dashboard**: Comprehensive booking history, payment status indicators, and manager contact links.
- **Ratings & Reviews**: Submit 1-to-5 star ratings and reviews for stayed hostels.

### 3. Hostel Owner / Manager Portal (`/owner/`)
- **Hostel Property Registration**: Register new hostels with location, category, semester pricing, cover photo uploads, and amenity checkboxes.
- **Room Inventory Management**: Add custom room numbers (e.g., *Block A-101*), view occupancy status, and manage room listings.
- **Student Bookings Tracker**: View incoming student reservations, contact details, and verified MTN MoMo payment numbers.

### 4. Administrator Panel (`/admin/`)
- **System Control Dashboard**: Platform metrics (Total Users, Total Hostels, Pending Approvals, Total Bookings, Total Revenue).
- **Pending Approvals Queue**: One-click approval mechanism to verify newly registered hostels before publication.
- **User Management**: View and manage system accounts across Students, Owners, and Admins.

---

## 🛠️ Technology Stack

- **Frontend Core**: HTML5, Vanilla JavaScript, CSS3
- **UI Framework**: Bootstrap 5 (CDN) & Bootstrap Icons
- **Backend Core**: PHP 8.x
- **Database Engine**: MySQL / MariaDB (`mydb`) via MySQLi Extension
- **Security**: Prepared Statements (`mysqli_stmt`), `password_hash(PASSWORD_BCRYPT)`, `password_verify()`, Session Guards.

---

## 👥 User Roles & Permissions

| Role | Permissions & Actions |
| :--- | :--- |
| **Student** | Search hostels, view room availability, book rooms, make MTN MoMo payments, view booking receipts, write reviews. |
| **Owner** | Register hostels (status defaults to `pending`), add/delete rooms, monitor room occupancy, view student bookings & payment details. |
| **Admin** | Approve/reject pending hostels, oversee all system hostels, view user directory, delete user accounts. |

---

## 🗄️ Database Architecture & Schema (`mydb`)

### 1. `users` Table
Stores authentication and profile information for all registered users.
- `id` (INT, Primary Key, Auto Increment)
- `name` (VARCHAR(100), Full Name)
- `email` (VARCHAR(100), Unique Email Address)
- `password` (VARCHAR(255), BCrypt Hashed Password)
- `role` (ENUM('student', 'owner', 'admin'))
- `created_at` (TIMESTAMP)

### 2. `hostels` Table
Stores hostel properties submitted by owners.
- `id` (INT, Primary Key, Auto Increment)
- `owner_id` (INT, Foreign Key -> `users.id`)
- `name` (VARCHAR(150), Hostel Name)
- `location` (VARCHAR(100), e.g., Ishaka, KIU Road, Katungu, Kashenyi)
- `price` (INT, Semester Fee in UGX)
- `type` (ENUM('Single', 'Double', 'Self-contained'))
- `facilities` (TEXT, Comma-separated amenities)
- `photo` (VARCHAR(255), Cover image filename)
- `status` (ENUM('pending', 'approved'))
- `created_at` (TIMESTAMP)

### 3. `rooms` Table
Stores individual room units belonging to each hostel.
- `id` (INT, Primary Key, Auto Increment)
- `hostel_id` (INT, Foreign Key -> `hostels.id`)
- `room_no` (VARCHAR(50), e.g., Room 101)
- `status` (ENUM('available', 'booked'))

### 4. `bookings` Table
Tracks room reservations made by students.
- `id` (INT, Primary Key, Auto Increment)
- `student_id` (INT, Foreign Key -> `users.id`)
- `room_id` (INT, Foreign Key -> `rooms.id`)
- `hostel_id` (INT, Foreign Key -> `hostels.id`)
- `booking_date` (DATETIME)
- `payment_status` (ENUM('pending', 'paid'))
- `payment_phone` (VARCHAR(20), MTN MoMo account number)

### 5. `reviews` Table
Stores ratings and reviews left by students.
- `id` (INT, Primary Key, Auto Increment)
- `student_id` (INT, Foreign Key -> `users.id`)
- `hostel_id` (INT, Foreign Key -> `hostels.id`)
- `rating` (INT, 1 to 5 Stars)
- `comment` (TEXT)
- `created_at` (TIMESTAMP)

---

## 📁 Directory & File Structure

```
bushenyi_hostels/
├── db.php                     # Central MySQLi database connection file
├── database.sql               # Full SQL database schema & initial seed query
├── seed.php                   # Browser & CLI dynamic database & sample data setup script
├── index.php                  # Public homepage & search directory
├── login.php                  # Centered login form & role-based authentication
├── register.php               # Account registration form (Student / Owner)
├── logout.php                 # Session destruction & sign-out script
├── README.md                  # Complete system documentation
├── css/
│   └── style.css              # Custom styling, #1a73e8 primary theme, MTN MoMo styles
├── js/
│   └── script.js              # Price slider updates, form validation & MoMo simulation scripts
├── uploads/                   # Hostel cover photos storage directory
├── includes/
│   ├── header.php             # Global navigation bar & header template
│   ├── footer.php             # Global footer template
│   └── auth_check.php         # Session management & role security guards
├── student/
│   ├── dashboard.php          # Student dashboard & booking statistics
│   ├── hostel_view.php        # Hostel details page & room booking trigger
│   ├── my_bookings.php        # Student room bookings listing & receipt links
│   ├── pay.php                # MTN Mobile Money UG mockup gateway page
│   └── add_review.php         # Rating & comment submission handler
├── owner/
│   ├── dashboard.php          # Owner dashboard & property overview
│   ├── add_hostel.php         # New hostel registration form with file upload
│   ├── manage_rooms.php       # Room inventory manager (Add / Delete rooms)
│   └── view_bookings.php      # Student bookings tracker for owner's hostels
└── admin/
    ├── dashboard.php          # Administrator control center & user management
    ├── approve.php            # One-click hostel approval handler
    └── delete_user.php        # User account deletion handler
```

---

## 🚀 Installation & Local Setup Guide

### Running via XAMPP Localhost (Recommended)

1. **Start XAMPP**: Open **XAMPP Control Panel** and click **Start** for **Apache** and **MySQL**.
2. **Project Directory**: Ensure the `bushenyi_hostels` folder is located in your XAMPP `htdocs` directory:
   `C:\xampp\htdocs\bushenyi_hostels`
3. **Initialize Database**: Open your web browser and navigate to:
   ```http
   http://localhost/bushenyi_hostels/seed.php
   ```
   *(This automatically creates the `mydb` database, all 5 tables, default accounts, and 6 sample hostels).*
4. **Launch Application**: Access the homepage at:
   ```http
   http://localhost/bushenyi_hostels/index.php
   ```

---

## 🔑 Default Access Credentials

| User Role | Email | Password | Access Lin |
| :--- | :--- | :--- | :--- |
| **System Admin** | `admink@gmail.com` | `admin123k` | [admin/dashboard.php](http://localhost/bushenyi_hostels/admin/dashboard.php) |
| **Hostel Owner** | `ownerk@gmail.com` | `owner123k` | [owner/dashboard.php](http://localhost/bushenyi_hostels/owner/dashboard.php) |
| **Student** | `studentk@gmail.com` | `student123k` | [student/dashboard.php](http://localhost/bushenyi_hostels/student/dashboard.php) |

---

## 🛡️ Security & Implementation Details

1. **SQL Injection Protection**: 100% of database queries use MySQLi prepared statements (`$conn->prepare()`, `bind_param()`, `execute()`).
2. **Password Hashing**: User passwords are encrypted using `password_hash($password, PASSWORD_BCRYPT)` and validated via `password_verify()`.
3. **Role-Based Authorization**: `includes/auth_check.php` verifies active user sessions and restricts cross-role route access.
4. **XSS Sanitization**: All database outputs in HTML templates are sanitized using `htmlspecialchars()`.
