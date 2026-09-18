========================================================================
BUSHENYI HOSTEL BOOKING & MANAGEMENT SYSTEM - QUICK REFERENCE GUIDE
========================================================================

1. ACCESS URLS (XAMPP LOCALHOST)
------------------------------------------------------------------------
- Database Initializer: http://localhost/bushenyi_hostels/seed.php
- Public Homepage:       http://localhost/bushenyi_hostels/index.php
- Login Portal:          http://localhost/bushenyi_hostels/login.php
- Register Account:      http://localhost/bushenyi_hostels/register.php

2. DEFAULT DEMO LOGIN CREDENTIALS
------------------------------------------------------------------------
[ROLE 1] SYSTEM ADMINISTRATOR
- Email:    admin@gmail.com
- Password: admin123
- Portal:   http://localhost/bushenyi_hostels/admin/dashboard.php

[ROLE 2] HOSTEL OWNER / LANDLORD
- Email:    owner@gmail.com
- Password: owner123
- Portal:   http://localhost/bushenyi_hostels/owner/dashboard.php

[ROLE 3] STUDENT (KIU / BUSHENYI)
- Email:    student@gmail.com
- Password: student123
- Portal:   http://localhost/bushenyi_hostels/student/dashboard.php

3. DATABASE SPECIFICATIONS (MySQL: mydb)
------------------------------------------------------------------------
- DB Name: mydb
- Connection file: /db.php
- Table `users`: Stores admin, owner, and student accounts.
- Table `hostels`: Stores hostel listings (Status: pending / approved).
- Table `rooms`: Stores room numbers and status (available / booked).
- Table `bookings`: Tracks room bookings and payment status (pending / paid).
- Table `reviews`: Stores student ratings (1-5 stars) and comments.

4. MTN MOBILE MONEY (MoMo) MOCKUP SIMULATION
------------------------------------------------------------------------
- Gateway page: /student/pay.php?booking_id=X
- Valid Phone Prefix: 077..., 078..., 070..., 076..., 075...
- Demo MoMo PIN: 1234 (or any 4-digit PIN)

5. FOLDER DIRECTORY MAP
------------------------------------------------------------------------
bushenyi_hostels/
├── db.php                     <- MySQL connection configuration
├── seed.php                   <- 1-Click Database & dummy data generator
├── index.php                  <- Main public search page
├── login.php                  <- Sign in page
├── register.php               <- Account registration page
├── logout.php                 <- Session logout handler
├── README.md                  <- Full technical documentation
├── REFERENCE.md               <- Quick reference file
├── css/style.css              <- Styling (#1a73e8 blue theme & MTN MoMo)
├── js/script.js               <- Form validation & interactive scripts
├── uploads/                   <- Uploaded hostel photos
├── includes/                  <- Shared templates (header, footer, auth)
├── student/                   <- Student portal pages
├── owner/                     <- Hostel owner portal pages
└── admin/                     <- Admin approval panel & user manager

========================================================================
